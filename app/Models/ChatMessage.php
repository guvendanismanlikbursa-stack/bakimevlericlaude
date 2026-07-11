<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_thread_id', 'sender_type', 'sender_admin_id', 'body',
        'attachment_path', 'attachment_type', 'attachment_mime', 'attachment_size', 'read_at',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function thread()
    {
        return $this->belongsTo(ChatThread::class, 'chat_thread_id');
    }

    public function senderAdmin()
    {
        return $this->belongsTo(Admin::class, 'sender_admin_id');
    }
}
