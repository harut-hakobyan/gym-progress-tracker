<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessedTelegramUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'update_id',
        'telegram_id',
        'action_type',
        'status',
        'attempts',
        'last_error',
        'raw_payload',
        'received_at',
        'processing_started_at',
        'processed_at',
    ];

    protected $casts = [
        'update_id' => 'integer',
        'telegram_id' => 'integer',
        'attempts' => 'integer',
        'raw_payload' => 'array',
        'received_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
