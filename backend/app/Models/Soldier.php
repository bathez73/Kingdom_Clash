<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kingdom_id', 'type', 'quantity'])]
class Soldier extends Model
{
    use HasFactory;

    public function kingdom(): BelongsTo
    {
        return $this->belongsTo(Kingdom::class);
    }

    /**
     * Coûts d'entraînement par soldat
     */
    public static function getTrainingCost(string $type): array
    {
        return match ($type) {
            'swordsman' => ['gold' => 20, 'food' => 10],
            'archer' => ['gold' => 30, 'food' => 15],
            'cavalry' => ['gold' => 50, 'food' => 25],
            default => ['gold' => 0, 'food' => 0],
        };
    }

    /**
     * Temps d'entraînement d'un soldat en secondes
     */
    public static function getTrainingTime(string $type): int
    {
        return match ($type) {
            'swordsman' => 5,     // 5 secondes par épéiste
            'archer' => 8,        // 8 secondes par archer
            'cavalry' => 12,      // 12 secondes par cavalier
            default => 5,
        };
    }

    /**
     * Puissance d'attaque du soldat
     */
    public static function getAttackPower(string $type): int
    {
        return match ($type) {
            'swordsman' => 10,
            'archer' => 15,
            'cavalry' => 25,
            default => 0,
        };
    }

    /**
     * Récupérer ou créer un soldat pour un royaume
     */
    public static function findOrCreateForKingdom(int $kingdomId, string $type): self
    {
        return self::firstOrCreate(
            ['kingdom_id' => $kingdomId, 'type' => $type],
            ['quantity' => 0]
        );
    }
}
