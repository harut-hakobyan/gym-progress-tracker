<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTelegramState extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'state',
        'payload',
        'expires_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'payload' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
