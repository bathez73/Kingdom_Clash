<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kingdom_id', 'type', 'title', 'message', 'data', 'read_at'])]
class Notification extends Model
{
    use HasFactory;

    protected $casts = [
        'data' => 'json',
        'read_at' => 'datetime',
    ];

    public function kingdom(): BelongsTo
    {
        return $this->belongsTo(Kingdom::class);
    }

    /**
     * Marquer la notification comme lue
     */
    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
