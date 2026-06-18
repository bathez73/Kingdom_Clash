<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attacker_id', 'defender_id', 'result', 'gold_stolen', 'attacker_losses', 'defender_losses'])]
class Battle extends Model
{
    use HasFactory;

    protected $casts = [
        'attacker_losses' => 'json',
        'defender_losses' => 'json',
    ];

    public function attacker(): BelongsTo
    {
        return $this->belongsTo(Kingdom::class, 'attacker_id');
    }

    public function defender(): BelongsTo
    {
        return $this->belongsTo(Kingdom::class, 'defender_id');
    }
}
