<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactInquiry extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'is_replied',
        'reply_message',
        'replied_at',
    ];

    protected $casts = [
        'is_replied' => 'boolean',
        'replied_at' => 'datetime',
    ];
}
