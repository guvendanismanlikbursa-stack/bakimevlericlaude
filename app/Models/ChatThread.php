<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatThread extends Model
{
    protected $fillable = [
        'brand', 'guest_token', 'guest_name', 'guest_age', 'guest_avatar_url', 'family_user_id', 'intent', 'operator_gender_preference',
        'status', 'assigned_admin_id', 'lat', 'lng', 'city_name',
        'last_message_at', 'last_message_preview', 'unread_by_admin', 'unread_by_guest',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'last_message_at' => 'datetime',
            'unread_by_admin' => 'boolean',
            'unread_by_guest' => 'boolean',
        ];
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function familyUser()
    {
        return $this->belongsTo(FamilyUser::class);
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    /**
     * Ayni misafirin (guest_token) TUM thread'leri (kendisi dahil) - admin
     * listesinde "bu ziyaretcinin kac bolumde sohbeti var" gostermek icin.
     */
    public function siblingThreads()
    {
        return $this->hasMany(self::class, 'guest_token', 'guest_token');
    }
}
