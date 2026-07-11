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

        $threads = ChatThread::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.chat.index', compact('threads', 'status'));
    }

    public function show(ChatThread $thread)
    {
        $thread->load('messages', 'assignedAdmin');
        $thread->update(['unread_by_admin' => false]);

        return view('admin.chat.show', compact('thread'));
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
