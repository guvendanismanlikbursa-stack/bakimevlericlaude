<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Services\ImageCompressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'open');
        $city = $request->get('city');
        $intent = $request->get('intent');
        $brand = $request->get('brand');

        $threads = ChatThread::query()
            ->withCount('siblingThreads')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($city, fn ($q) => $q->where('city_name', $city))
            ->when($intent, fn ($q) => $q->where('intent', $intent))
            ->when($brand, fn ($q) => $q->where('brand', $brand))
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $cities = ChatThread::whereNotNull('city_name')->distinct()->orderBy('city_name')->pluck('city_name');
        $brands = ChatThread::distinct()->orderBy('brand')->pluck('brand');

        return view('admin.chat.index', compact('threads', 'status', 'city', 'intent', 'brand', 'cities', 'brands'));
    }

    public function show(ChatThread $thread)
    {
        $thread->load('messages', 'assignedAdmin');
        $thread->update(['unread_by_admin' => false]);

        // Ayni misafirin (guest_token) baska bolumlerde acilmis sohbetleri
        // varsa admin gorsun - her biri ayri thread/gecmis, ama ayni kisi.
        $siblingThreads = ChatThread::where('guest_token', $thread->guest_token)
            ->where('id', '!=', $thread->id)
            ->orderByDesc('last_message_at')
            ->get();

        return view('admin.chat.show', compact('thread', 'siblingThreads'));
    }

    public function reply(Request $request, ChatThread $thread)
    {
        $data = $request->validate([
            'body' => 'nullable|string|max:4000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,mp4,pdf,doc,docx|max:20480',
        ]);

        abort_if(empty($data['body']) && ! $request->hasFile('attachment'), 422, 'Mesaj veya dosya gerekli.');

        [$attachmentPath, $attachmentType, $attachmentMime, $attachmentSize] = $this->storeAttachmentIfAny($request);

        ChatMessage::create([
            'chat_thread_id' => $thread->id,
            'sender_type' => 'admin',
            'sender_admin_id' => session('admin_id'),
            'body' => $data['body'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
            'attachment_mime' => $attachmentMime,
            'attachment_size' => $attachmentSize,
        ]);

        $thread->update([
            'last_message_at' => now(),
            'last_message_preview' => $data['body'] ? \Illuminate\Support\Str::limit($data['body'], 80) : ('['.($attachmentType ?? 'dosya').']'),
            'unread_by_guest' => true,
            'status' => 'assigned',
            'assigned_admin_id' => $thread->assigned_admin_id ?? session('admin_id'),
        ]);

        return back();
    }

    public function poll(Request $request, ChatThread $thread)
    {
        $afterId = (int) $request->query('after_id', 0);
        $messages = $thread->messages()->where('id', '>', $afterId)->orderBy('id')->get();

        return response()->json([
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id,
                'sender_type' => $m->sender_type,
                'body' => $m->body,
                'attachment_url' => $m->attachment_path ? Storage::disk('public')->url($m->attachment_path) : null,
                'attachment_type' => $m->attachment_type,
                'created_at' => $m->created_at->toIso8601String(),
            ])->values(),
        ]);
    }

    public function stats()
    {
        $totalGuests = ChatThread::distinct('guest_token')->count('guest_token');
        $totalThreads = ChatThread::count();

        $byCity = ChatThread::whereNotNull('city_name')
            ->selectRaw('city_name, count(distinct guest_token) as guest_count')
            ->groupBy('city_name')
            ->orderByDesc('guest_count')
            ->limit(15)
            ->get();

        $byIntent = ChatThread::selectRaw('intent, count(*) as thread_count')
            ->groupBy('intent')
            ->orderByDesc('thread_count')
            ->get();

        $byBrand = ChatThread::selectRaw('brand, count(distinct guest_token) as guest_count')
            ->groupBy('brand')
            ->orderByDesc('guest_count')
            ->get();

        $ageBuckets = [
            '0-17' => ChatThread::whereNotNull('guest_age')->where('guest_age', '<', 18)->distinct('guest_token')->count('guest_token'),
            '18-34' => ChatThread::whereBetween('guest_age', [18, 34])->distinct('guest_token')->count('guest_token'),
            '35-54' => ChatThread::whereBetween('guest_age', [35, 54])->distinct('guest_token')->count('guest_token'),
            '55-74' => ChatThread::whereBetween('guest_age', [55, 74])->distinct('guest_token')->count('guest_token'),
            '75+' => ChatThread::where('guest_age', '>=', 75)->distinct('guest_token')->count('guest_token'),
        ];

        return view('admin.chat.stats', compact('totalGuests', 'totalThreads', 'byCity', 'byIntent', 'byBrand', 'ageBuckets'));
    }

    public function close(ChatThread $thread)
    {
        $thread->update(['status' => 'closed']);

        return back()->with('success', 'Sohbet kapatıldı.');
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
}
