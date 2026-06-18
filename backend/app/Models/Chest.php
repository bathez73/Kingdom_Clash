<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chest extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'status',
        'unlock_ends_at',
    ];

    protected $casts = [
        'unlock_ends_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
