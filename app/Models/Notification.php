<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'message',
        'sent_at',
        'read_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    // Relationship with the user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
