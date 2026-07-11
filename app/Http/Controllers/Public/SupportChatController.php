<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\ChatWorkingHour;
use App\Models\Setting;
use App\Services\GeoLookupService;
use App\Services\ImageCompressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Canli destek sohbeti - anonim misafir (guest_token, localStorage) <-> admin.
// Gercek zamanli degil, istemci tarafi polling ile calisir (bkz. poll()) -
// bu barindirmada kalici surec/WebSocket destegi olmadigi icin bilincli tercih.
class SupportChatController extends Controller
{
    private const DEFAULT_OFFLINE_MESSAGE = 'Şu an çevrimdışıyız. Mesajınızı bırakın, size en kısa sürede döneriz.';

    public function start(Request $request, GeoLookupService $geo)
    {
        $brand = current_brand();

        $data = $request->validate([
            'guest_token' => 'nullable|string|max:64',
            'intent' => 'required_without:guest_token|nullable|in:sohbet,dertlesme,fikir,temsilci',
            'operator_gender_preference' => 'nullable|in:erkek,kadin,farketmez',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        $thread = null;
        if (! empty($data['guest_token'])) {
            $thread = ChatThread::where('brand', $brand['slug'])
                ->where('guest_token', $data['guest_token'])
                ->where('status', '!=', 'closed')
                ->first();
        }

        if (! $thread) {
            $cityName = null;
            if (($data['lat'] ?? null) !== null && ($data['lng'] ?? null) !== null) {
                $nearest = $geo->nearestCity((float) $data['lat'], (float) $data['lng']);
                $cityName = $nearest['city'] ?? null;
            }

            $thread = ChatThread::create([
                'brand' => $brand['slug'],
                'guest_token' => Str::random(40),
                'intent' => $data['intent'] ?? 'sohbet',
                'operator_gender_preference' => $data['operator_gender_preference'] ?? null,
                'status' => 'open',
                'lat' => $data['lat'] ?? null,
                'lng' => $data['lng'] ?? null,
                'city_name' => $cityName,
                'unread_by_admin' => true,
            ]);
        }

        return response()->json([
            'thread_id' => $thread->id,
            'guest_token' => $thread->guest_token,
            'is_online' => ChatWorkingHour::isCurrentlyOpen(),
            'offline_message' => Setting::get('chat_offline_message', self::DEFAULT_OFFLINE_MESSAGE),
            'messages' => $thread->messages()->orderBy('id')->get()->map(fn ($m) => $this->formatMessage($m))->values(),
        ]);
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

        $thread->update([
            'last_message_at' => now(),
            'last_message_preview' => $data['body'] ? Str::limit($data['body'], 80) : ('['.($attachmentType ?? 'dosya').']'),
            'unread_by_admin' => true,
            'status' => $thread->status === 'closed' ? 'open' : $thread->status,
        ]);

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
