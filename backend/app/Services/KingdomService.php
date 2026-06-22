<?php

namespace App\Services;

use App\Jobs\BuildBuildingJob;
use App\Models\Building;
use App\Models\Kingdom;
use App\Models\Quest;
use Illuminate\Support\Facades\DB;

class KingdomService
{
    /**
     * Créer un nouveau royaume pour un utilisateur
     */
    public function createKingdom(int $userId, string $name): Kingdom
    {
        return Kingdom::create([
            'user_id' => $userId,
            'name' => $name,
            'level' => 1,
            'gold' => 1000,
            'wood' => 500,
            'food' => 500,
        ]);
    }

    /**
     * Initialiser les bâtiments d'un nouveau royaume
     */
    public function initializeBuildings(Kingdom $kingdom): void
    {
        DB::transaction(function () use ($kingdom) {
            foreach (['gold_mine', 'sawmill', 'farm', 'barracks'] as $type) {
                Building::create([
                    'kingdom_id' => $kingdom->id,
                    'type' => $type,
                    'level' => 1,
                    'upgrade_ends_at' => null,
                ]);
            }
        });
        
        $this->initializeSoldiers($kingdom);
    }
    
    /**
     * Initialiser les soldats d'un nouveau royaume
     */
    private function initializeSoldiers(Kingdom $kingdom): void
    {
        DB::transaction(function () use ($kingdom) {
            $soldiers = [
                ['type' => 'swordsman', 'quantity' => 10],
                ['type' => 'archer', 'quantity' => 5],
                ['type' => 'cavalry', 'quantity' => 2],
            ];
            
            foreach ($soldiers as $soldierData) {
                \App\Models\Soldier::create([
                    'kingdom_id' => $kingdom->id,
                    'type' => $soldierData['type'],
                    'quantity' => $soldierData['quantity'],
                ]);
            }
        });
    }

    /**
     * Améliorer un bâtiment
     */
    public function upgradeBuilding(Building $building): array
    {
        $kingdom = $building->kingdom;
        $currentLevel = $building->level;
        $cost = Building::getUpgradeCost($building->type, $currentLevel);

        if (!$kingdom->hasResources($cost['gold'], $cost['wood'])) {
            return ['success' => false, 'message' => 'Ressources insuffisantes'];
        }

        return DB::transaction(function () use ($building, $kingdom, $cost) {
            $kingdom->deductResources($cost['gold'], $cost['wood']);

            $upgradeTime = Building::getUpgradeTime($building->level);
            $upgradeEndsAt = now()->addSeconds($upgradeTime);

            $building->update(['upgrade_ends_at' => $upgradeEndsAt]);

            BuildBuildingJob::dispatch($building->id)->delay($upgradeTime);

            // Progression de la quête d'amélioration
            if ($kingdom->user) {
                Quest::addProgress($kingdom->user->id, 'upgrade_building', 1);
            }

            return [
                'success' => true,
                'building' => $building,
                'upgrade_ends_at' => $upgradeEndsAt,
                'message' => 'Amélioration lancée avec succès',
            ];
        });
    }

    /**
     * Finir l'amélioration d'un bâtiment
     */
    public function completeBuildingUpgrade(Building $building): void
    {
        DB::transaction(function () use ($building) {
            $building->update([
                'level' => $building->level + 1,
                'upgrade_ends_at' => null,
            ]);

            $building->kingdom->notifications()->create([
                'type' => 'construction_completed',
                'title' => 'Construction terminée',
                'message' => "Votre {$building->type} a atteint le niveau {$building->level}",
                'data' => [
                    'building_id' => $building->id,
                    'building_type' => $building->type,
                    'new_level' => $building->level,
                ],
            ]);

            broadcast(new \App\Events\BuildingUpgraded($building));
        });
    }

    /**
     * Échanger des ressources au marché noir
     * Taux : 2 Or pour 1 Bois, 2 Bois pour 1 Nourriture, etc.
     */
    public function exchangeResources(Kingdom $kingdom, string $from, string $to, int $quantity): array
    {
        $exchangeRates = [
            'gold_to_wood' => ['cost' => 2, 'gain' => 1],      // 2 Or -> 1 Bois
            'wood_to_gold' => ['cost' => 2, 'gain' => 1],      // 2 Bois -> 1 Or
            'wood_to_food' => ['cost' => 2, 'gain' => 1],      // 2 Bois -> 1 Nourriture
            'food_to_wood' => ['cost' => 2, 'gain' => 1],      // 2 Nourriture -> 1 Bois
            'gold_to_food' => ['cost' => 3, 'gain' => 1],      // 3 Or -> 1 Nourriture
            'food_to_gold' => ['cost' => 3, 'gain' => 1],      // 3 Nourriture -> 1 Or
        ];

        $exchangeKey = "{$from}_to_{$to}";
        if (!isset($exchangeRates[$exchangeKey])) {
            return ['success' => false, 'message' => 'Échange invalide'];
        }

        $rate = $exchangeRates[$exchangeKey];
        $cost = $quantity * $rate['cost'];
        $gain = $quantity * $rate['gain'];

        $resourceField = 'gold';
        if ($from === 'wood') $resourceField = 'wood';
        if ($from === 'food') $resourceField = 'food';

        if ($kingdom->{$resourceField} < $cost) {
            return ['success' => false, 'message' => 'Ressources insuffisantes'];
        }

        return DB::transaction(function () use ($kingdom, $resourceField, $from, $to, $cost, $gain) {
            $kingdom->update([$resourceField => DB::raw("{$resourceField} - {$cost}")]);

            $gainField = 'gold';
            if ($to === 'wood') $gainField = 'wood';
            if ($to === 'food') $gainField = 'food';

            $kingdom->update([$gainField => DB::raw("{$gainField} + {$gain}")]);

            return [
                'success' => true,
                'message' => 'Échange effectué',
                'kingdom' => $kingdom->fresh(),
            ];
        });
    }

    /**
     * Donner le coffre quotidien
     */
    public function claimDailyChest(Kingdom $kingdom): array
    {
        $cacheKey = "daily_chest_claimed_{$kingdom->id}";

        if (\Cache::has($cacheKey)) {
            return ['success' => false, 'message' => 'Vous avez déjà récupéré votre coffre aujourd\'hui'];
        }

        $goldReward = rand(50, 200);
        $woodReward = rand(30, 100);
        $foodReward = rand(30, 100);

        DB::transaction(function () use ($kingdom, $goldReward, $woodReward, $foodReward, $cacheKey) {
            $kingdom->addResources($goldReward, $woodReward, $foodReward);
            \Cache::put($cacheKey, true, now()->endOfDay());
        });

        return [
            'success' => true,
            'message' => 'Coffre récupéré',
            'reward' => [
                'gold' => $goldReward,
                'wood' => $woodReward,
                'food' => $foodReward,
            ],
            'kingdom' => $kingdom->fresh(),
        ];
    }

    /**
     * Obtenir le classement des royaumes
     */
    public function getRanking(int $limit = 100): array
    {
        return Kingdom::with(['user'])
            ->withCount([
                'soldiers as total_soldiers' => function ($query) {
                    $query->select(DB::raw('COALESCE(SUM(quantity), 0)'));
                },
            ])
            ->whereHas('user')
            ->get()
            ->sortByDesc(fn ($kingdom) => $kingdom->user?->trophies ?? 0)
            ->values()
            ->take($limit)
            ->map(fn ($kingdom) => [
                'id' => $kingdom->id,
                'name' => $kingdom->name,
                'level' => $kingdom->level,
                'gold' => $kingdom->gold,
                'defense_power' => $kingdom->getDefensePower(),
                'user' => $kingdom->user ? [
                    'id' => $kingdom->user->id,
                    'name' => $kingdom->user->name,
                    'trophies' => $kingdom->user->trophies,
                ] : null,
                'total_soldiers' => $kingdom->total_soldiers ?? 0,
            ])
            ->toArray();
    }
}
