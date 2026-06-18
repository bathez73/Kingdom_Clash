<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'name', 'level', 'gold', 'wood', 'food'])]
class Kingdom extends Model
{
    use HasFactory, SoftDeletes;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function soldiers(): HasMany
    {
        return $this->hasMany(Soldier::class);
    }

    public function attackBattles(): HasMany
    {
        return $this->hasMany(Battle::class, 'attacker_id');
    }

    public function defenseBattles(): HasMany
    {
        return $this->hasMany(Battle::class, 'defender_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Calcul de la puissance de défense du royaume
     */
    public function getDefensePower(): int
    {
        $basePower = $this->level * 10;
        
        $soldierPower = $this->soldiers()->sum(\DB::raw("
            CASE 
                WHEN type = 'swordsman' THEN quantity * 10
                WHEN type = 'archer' THEN quantity * 15
                WHEN type = 'cavalry' THEN quantity * 25
                ELSE 0
            END
        "));

        return $basePower + $soldierPower;
    }

    /**
     * Vérifier si les ressources suffisent
     */
    public function hasResources(int $gold = 0, int $wood = 0, int $food = 0): bool
    {
        return $this->gold >= $gold && $this->wood >= $wood && $this->food >= $food;
    }

    /**
     * Déduire des ressources
     */
    public function deductResources(int $gold = 0, int $wood = 0, int $food = 0): bool
    {
        if (!$this->hasResources($gold, $wood, $food)) {
            return false;
        }

        $this->update([
            'gold' => \DB::raw("gold - {$gold}"),
            'wood' => \DB::raw("wood - {$wood}"),
            'food' => \DB::raw("food - {$food}"),
        ]);

        return true;
    }

    /**
     * Ajouter des ressources
     */
    public function addResources(int $gold = 0, int $wood = 0, int $food = 0): void
    {
        $this->update([
            'gold' => \DB::raw("gold + {$gold}"),
            'wood' => \DB::raw("wood + {$wood}"),
            'food' => \DB::raw("food + {$food}"),
        ]);
    }
}
