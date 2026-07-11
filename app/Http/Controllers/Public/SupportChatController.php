<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\ChatWorkingHour;
use App\Models\Setting;
use App\Services\ImageCompressionService;
use App\Services\IpGeoLookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Canli destek sohbeti - anonim misafir (guest_token, localStorage) <-> admin.
// Gercek zamanli degil, istemci tarafi polling ile calisir (bkz. poll()) -
// bu barindirmada kalici surec/WebSocket destegi olmadigi icin bilincli tercih.
class SupportChatController extends Controller
{
    private const DEFAULT_OFFLINE_MESSAGE = 'Şu an çevrimdışıyız. Mesajınızı bırakın, size en kısa sürede döneriz.';

    public function start(Request $request, IpGeoLookupService $ipGeo)
    {
        $brand = current_brand();

        $data = $request->validate([
            'guest_token' => 'nullable|string|max:64',
            'intent' => 'nullable|in:sohbet,dertlesme,fikir,temsilci',
            'operator_gender_preference' => 'nullable|in:erkek,kadin,farketmez',
            'guest_name' => 'nullable|string|max:80',
            'guest_age' => 'nullable|integer|min:1|max:120',
            'guest_avatar_url' => 'nullable|url|max:500',
        ]);

        // guest_token BIR KISIYI (tarayiciyi) tanimlar, thread'i degil. Ayni
        // kisinin farkli niyetleri (sohbet/dertlesme/fikir/temsilci) HER ZAMAN
        // ayri thread'lerdir - kendi mesaj gecmisiyle, birbirine karismaz.
        // "Konu değiştir" ile baska bir niyete gecince, o niyetin thread'i
        // varsa devam eder, yoksa yeni acilir - ama ESKI niyetin mesajlari
        // asla yeni bolume sizmaz.
        $guestToken = ($data['guest_token'] ?? null) ?: Str::random(40);
        $intent = $data['intent'] ?? 'sohbet';

        $thread = ChatThread::where('brand', $brand['slug'])
            ->where('guest_token', $guestToken)
            ->where('intent', $intent)
            ->where('status', '!=', 'closed')
            ->first();

        if (! $thread) {
            // Ayni misafirin (guest_token) baska bir bolumde acilmis onceki
            // thread'i varsa isim/yas/sehir bilgisini oradan devral - her
            // bolum degisiminde tekrar sormaya/IP sorgulamaya gerek kalmaz.
            $siblingThread = ChatThread::where('guest_token', $guestToken)->latest()->first();

            $cityName = $siblingThread->city_name
                ?? $ipGeo->cityFromIp($request->ip());

            $thread = ChatThread::create([
                'brand' => $brand['slug'],
                'guest_token' => $guestToken,
                'intent' => $intent,
                'operator_gender_preference' => $data['operator_gender_preference'] ?? null,
                'status' => 'open',
                'city_name' => $cityName,
                'guest_name' => $data['guest_name'] ?? $siblingThread?->guest_name,
                'guest_age' => $data['guest_age'] ?? $siblingThread?->guest_age,
                'guest_avatar_url' => $data['guest_avatar_url'] ?? $siblingThread?->guest_avatar_url,
                'unread_by_admin' => true,
            ]);

            $this->notifyAdminsOfNewThread($thread);
        }

        return response()->json([
            'thread_id' => $thread->id,
            'intent' => $thread->intent,
            'guest_token' => $thread->guest_token,
            'is_online' => ChatWorkingHour::isCurrentlyOpen(),
            'offline_message' => Setting::get('chat_offline_message', self::DEFAULT_OFFLINE_MESSAGE),
            'messages' => $thread->messages()->orderBy('id')->get()->map(fn ($m) => $this->formatMessage($m))->values(),
        ]);
    }

    private function notifyAdminsOfNewThread(ChatThread $thread): void
    {
        $label = ['sohbet' => 'Sohbet', 'dertlesme' => 'Dertleşme', 'fikir' => 'Fikir', 'temsilci' => 'Temsilci'][$thread->intent] ?? $thread->intent;
        $name = $thread->guest_name ? $thread->guest_name.' isimli ziyaretçi' : 'Yeni bir ziyaretçi';
        $brandName = config("brands.brands.{$thread->brand}.name", $thread->brand);

        foreach (Admin::all() as $admin) {
            notify_user($admin, 'chat_message', "Yeni canlı sohbet · {$brandName}", "{$name} {$brandName} üzerinden \"{$label}\" için yazmaya başladı.", ['chat_thread_id' => $thread->id]);
        }
    }

    public function send(Request $request)
    {
        $brand = current_brand();
        $thread = $this->threadFromRoute($request);
        abort_unless($thread->brand === $brand['slug'], 403);

        $data = $request->validate([
            'guest_token' => 'required|string',
            'body' => 'nullable|string|max:4000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,mp4,pdf,doc,docx|max:20480',
        ]);

        abort_unless($data['guest_token'] === $thread->guest_token, 403);
        abort_if(empty($data['body']) && ! $request->hasFile('attachment'), 422, 'Mesaj veya dosya gerekli.');

        [$attachmentPath, $attachmentType, $attachmentMime, $attachmentSize] = $this->storeAttachmentIfAny($request);

        $message = ChatMessage::create([
            'chat_thread_id' => $thread->id,
            'sender_type' => 'guest',
            'body' => $data['body'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
            'attachment_mime' => $attachmentMime,
            'attachment_size' => $attachmentSize,
        ]);

        // Zaten "okunmamis" isaretliyse (admin onceki mesaji da henuz gormedi)
        // tekrar bildirim atmiyoruz - hizli art arda mesajlarda admini
        // bildirim yagmuruna tutmamak icin sadece "okunmus -> okunmamis"
        // gecisinde (bkz. unread_by_admin false idi) yeni bildirim gonderilir.
        $shouldNotify = ! $thread->unread_by_admin;

        $thread->update([
            'last_message_at' => now(),
            'last_message_preview' => $data['body'] ? Str::limit($data['body'], 80) : ('['.($attachmentType ?? 'dosya').']'),
            'unread_by_admin' => true,
            'status' => $thread->status === 'closed' ? 'open' : $thread->status,
        ]);

        if ($shouldNotify) {
            $assignedAdmin = $thread->assigned_admin_id ? Admin::find($thread->assigned_admin_id) : null;
            $recipients = $assignedAdmin ? collect([$assignedAdmin]) : Admin::all();
            $preview = $data['body'] ? Str::limit($data['body'], 80) : ('['.($attachmentType ?? 'dosya').']');
            $brandName = config("brands.brands.{$thread->brand}.name", $thread->brand);

            foreach ($recipients as $admin) {
                notify_user($admin, 'chat_message', "Yeni sohbet mesajı · {$brandName}", $preview, ['chat_thread_id' => $thread->id]);
            }
        }

        return response()->json([
            'message' => $this->formatMessage($message),
            'suggested_section' => detect_chat_section($data['body'] ?? null),
        ]);
    }

    public function poll(Request $request)
    {
        $brand = current_brand();
        $thread = $this->threadFromRoute($request);
        abort_unless($thread->brand === $brand['slug'], 403);
        abort_unless($request->query('guest_token') === $thread->guest_token, 403);

        $afterId = (int) $request->query('after_id', 0);
        $messages = $thread->messages()->where('id', '>', $afterId)->orderBy('id')->get();

        if ($messages->isNotEmpty()) {
            $thread->update(['unread_by_guest' => false]);
        }

        return response()->json([
            'messages' => $messages->map(fn ($m) => $this->formatMessage($m))->values(),
            'is_online' => ChatWorkingHour::isCurrentlyOpen(),
        ]);
    }

    private function threadFromRoute(Request $request): ChatThread
    {
        $value = $request->route('thread');

        return $value instanceof ChatThread ? $value : ChatThread::findOrFail($value);
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string, 3: ?int}
     */
    private function storeAttachmentIfAny(Request $request): array
    {
        if (! $request->hasFile('attachment')) {
            return [null, null, null, null];
        }

        $file = $request->file('attachment');
        $mime = $file->getMimeType();
        $size = $file->getSize();

        if (str_starts_with($mime, 'image/')) {
            $path = app(ImageCompressionService::class)->store($file, 'chat-attachments');

            return [$path, 'image', $mime, $size];
        }

        $type = str_starts_with($mime, 'video/') ? 'video' : 'document';
        $path = $file->store('chat-attachments', 'public');

        return [$path, $type, $mime, $size];
    }

    private function formatMessage(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_type' => $message->sender_type,
            'body' => $message->body,
            'attachment_url' => $message->attachment_path ? Storage::disk('public')->url($message->attachment_path) : null,
            'attachment_type' => $message->attachment_type,
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }
}
