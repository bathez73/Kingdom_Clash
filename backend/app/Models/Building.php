<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['kingdom_id', 'type', 'level', 'upgrade_ends_at'])]
class Building extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'upgrade_ends_at' => 'datetime',
    ];

    public function kingdom(): BelongsTo
    {
        return $this->belongsTo(Kingdom::class);
    }

    /**
     * Calcul du coût d'amélioration : base_cost * (level * 1.5)
     */
    public static function getUpgradeCost(string $type, int $currentLevel): array
    {
        $baseCosts = [
            'gold_mine' => ['gold' => 100, 'wood' => 50],
            'sawmill' => ['gold' => 80, 'wood' => 100],
            'farm' => ['gold' => 60, 'wood' => 60],
            'barracks' => ['gold' => 150, 'wood' => 150],
        ];

        $baseCost = $baseCosts[$type] ?? ['gold' => 0, 'wood' => 0];
        $multiplier = $currentLevel * 1.5;

        return [
            'gold' => (int) ($baseCost['gold'] * $multiplier),
            'wood' => (int) ($baseCost['wood'] * $multiplier),
        ];
    }

    /**
     * Calcul du temps d'amélioration en secondes
     */
    public static function getUpgradeTime(int $level): int
    {
        return match ($level) {
            1 => 60,           // 1 minute
            2 => 120,          // 2 minutes
            default => 300,    // 5 minutes pour niveau 3+
        };
    }
}
