<?php

namespace App\Services;

use App\Jobs\TrainSoldierJob;
use App\Models\Kingdom;
use App\Models\Quest;
use App\Models\Soldier;
use Illuminate\Support\Facades\DB;

class SoldierService
{
    /**
     * Entraîner des soldats
     */
    public function trainSoldiers(Kingdom $kingdom, string $type, int $quantity): array
    {
        $cost = Soldier::getTrainingCost($type);
        $totalCost = [
            'gold' => $cost['gold'] * $quantity,
            'food' => $cost['food'] * $quantity,
        ];

        if (!$kingdom->hasResources($totalCost['gold'], 0, $totalCost['food'])) {
            return ['success' => false, 'message' => 'Ressources insuffisantes'];
        }

        return DB::transaction(function () use ($kingdom, $type, $quantity, $totalCost) {
            $kingdom->deductResources($totalCost['gold'], 0, $totalCost['food']);

            $soldier = Soldier::findOrCreateForKingdom($kingdom->id, $type);
            $trainingTime = Soldier::getTrainingTime($type);
            $totalTime = $trainingTime * $quantity;

            for ($i = 0; $i < $quantity; $i++) {
                TrainSoldierJob::dispatch($kingdom->id, $type)->delay($trainingTime * ($i + 1));
            }

            // Progression de la quête d'entraînement
            if ($kingdom->user) {
                Quest::addProgress($kingdom->user->id, 'train_soldiers', $quantity);
            }

            return [
                'success' => true,
                'message' => "Entraînement de {$quantity} {$type}(s) lancé",
                'training_time' => $totalTime,
                'completion_time' => now()->addSeconds($totalTime),
            ];
        });
    }

    /**
     * Finir l'entraînement d'un soldat
     */
    public function completeSoldierTraining(int $kingdomId, string $type): void
    {
        DB::transaction(function () use ($kingdomId, $type) {
            $soldier = Soldier::findOrCreateForKingdom($kingdomId, $type);
            $soldier->update(['quantity' => $soldier->quantity + 1]);

            $kingdom = $soldier->kingdom;
            $kingdom->notifications()->create([
                'type' => 'training_completed',
                'title' => 'Entraînement terminé',
                'message' => "Un {$type} a terminé son entraînement. Vous en avez maintenant {$soldier->quantity}.",
                'data' => [
                    'soldier_type' => $type,
                    'new_quantity' => $soldier->quantity,
                ],
            ]);

            broadcast(new \App\Events\SoldierTrained($soldier));
        });
    }
}
