<?php

namespace App\Services;

use App\Models\Battle;
use App\Models\Kingdom;
use App\Models\Soldier;
use Illuminate\Support\Facades\DB;

class BattleService
{
    /**
     * Lancer une attaque
     */
    public function attack(Kingdom $attacker, Kingdom $defender, array $troops, ?int $miniGameScore = 0): array
    {
        if ($attacker->id === $defender->id) {
            return ['success' => false, 'message' => 'Vous ne pouvez pas attaquer votre propre royaume'];
        }

        // Vérifier que les troupes existent
        $availableTroops = [];
        foreach ($troops as $type => $quantity) {
            $soldier = $attacker->soldiers()->where('type', $type)->first();
            if (!$soldier || $soldier->quantity < $quantity) {
                return ['success' => false, 'message' => "Vous n'avez pas assez de {$type}"];
            }
            $availableTroops[$type] = $quantity;
        }

        return DB::transaction(function () use ($attacker, $defender, $availableTroops, $miniGameScore) {
            // Calcul de la puissance d'attaque + bonus du mini-jeu
            $attackPower = 0;
            foreach ($availableTroops as $type => $quantity) {
                $attackPower += $quantity * Soldier::getAttackPower($type);
            }
            
            // Ajouter le bonus du mini-jeu (max +100%)
            $attackBonus = min(1.0, $miniGameScore / 200);
            $attackPower *= (1 + $attackBonus);

            // Calcul de la puissance de défense
            $defensePower = $defender->getDefensePower();

            // Déterminer le vainqueur (avec facteur aléatoire pour l'excitation!)
            $randomFactor = 0.8 + (lcg_value() * 0.4); // entre 0.8 et 1.2
            $finalAttack = $attackPower * $randomFactor;
            $attacker_won = $finalAttack > $defensePower;

            // Calculer les pertes
            $totalAttackerTroops = array_sum($availableTroops);
            $totalDefenderTroops = $defender->soldiers()->sum('quantity');

            if ($attacker_won) {
                $attackerLossPercentage = 0.30;
                $defenderLossPercentage = 0.80;
                $goldStolen = (int) ($defender->gold * 0.5);
            } else {
                $attackerLossPercentage = 0.80;
                $defenderLossPercentage = 0.30;
                $goldStolen = 0;
            }

            // Calculer les pertes par type
            $attackerLosses = [];
            foreach ($availableTroops as $type => $quantity) {
                $losses = (int) ($quantity * $attackerLossPercentage);
                if ($losses > 0) {
                    $attackerLosses[$type] = $losses;
                }
            }

            $defenderLosses = [];
            $defenderSoldiers = $defender->soldiers()->get();
            foreach ($defenderSoldiers as $soldier) {
                $losses = (int) ($soldier->quantity * $defenderLossPercentage);
                if ($losses > 0) {
                    $defenderLosses[$soldier->type] = $losses;
                }
            }

            // Créer l'enregistrement de la bataille
            $battle = Battle::create([
                'attacker_id' => $attacker->id,
                'defender_id' => $defender->id,
                'result' => $attacker_won ? 'attacker_won' : 'defender_won',
                'gold_stolen' => $goldStolen,
                'attacker_losses' => $attackerLosses,
                'defender_losses' => $defenderLosses,
            ]);

            // Appliquer les pertes aux troupes attaquantes
            foreach ($attackerLosses as $type => $losses) {
                $soldier = Soldier::where('kingdom_id', $attacker->id)
                    ->where('type', $type)
                    ->first();
                if ($soldier) {
                    $newQuantity = max(0, $soldier->quantity - $losses);
                    $soldier->quantity = $newQuantity;
                    $soldier->save();
                }
            }

            // Appliquer les pertes aux troupes défensives et voler l'or
            foreach ($defenderLosses as $type => $losses) {
                $soldier = Soldier::where('kingdom_id', $defender->id)
                    ->where('type', $type)
                    ->first();
                if ($soldier) {
                    $newQuantity = max(0, $soldier->quantity - $losses);
                    $soldier->quantity = $newQuantity;
                    $soldier->save();
                }
            }

            if ($attacker_won) {
                $defender->update(['gold' => max(0, $defender->gold - $goldStolen)]);
                $attacker->addResources($goldStolen);
            }

            // Créer les notifications
            $defenderMessage = $attacker_won 
                ? "Le royaume {$attacker->name} vous a attaqué et a gagné ! Vous avez perdu {$goldStolen} or."
                : "Le royaume {$attacker->name} vous a attaqué mais a échoué !";

            $attacker->notifications()->create([
                'type' => 'attack_sent',
                'title' => 'Attaque lancée',
                'message' => $attacker_won 
                    ? "Vous avez attaqué {$defender->name} et avez remporté {$goldStolen} or !"
                    : "Vous avez attaqué {$defender->name} mais avez été repoussé.",
                'data' => [
                    'battle_id' => $battle->id,
                    'target_kingdom' => $defender->name,
                    'result' => $battle->result,
                ],
            ]);

            $defender->notifications()->create([
                'type' => 'attack_received',
                'title' => 'Attaque reçue',
                'message' => $defenderMessage,
                'data' => [
                    'battle_id' => $battle->id,
                    'attacker_kingdom' => $attacker->name,
                    'result' => $battle->result,
                ],
            ]);

            // Broadcast l'événement
            broadcast(new \App\Events\BattleOccurred($battle));
            broadcast(new \App\Events\RankingUpdated());

            return [
                'success' => true,
                'battle' => $battle,
                'attacker_won' => $attacker_won,
                'gold_stolen' => $goldStolen,
                'message' => $attacker_won ? 'Victoire !' : 'Défaite !',
            ];
        });
    }

    /**
     * Obtenir l'historique des batailles
     */
    public function getBattleHistory(Kingdom $kingdom, int $limit = 50): array
    {
        $battles = Battle::where(function ($query) use ($kingdom) {
            $query->where('attacker_id', $kingdom->id)
                  ->orWhere('defender_id', $kingdom->id);
        })
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($battle) => [
                'id' => $battle->id,
                'attacker' => [
                    'id' => $battle->attacker->id,
                    'name' => $battle->attacker->name,
                ],
                'defender' => [
                    'id' => $battle->defender->id,
                    'name' => $battle->defender->name,
                ],
                'result' => $battle->result,
                'gold_stolen' => $battle->gold_stolen,
                'attacker_losses' => $battle->attacker_losses,
                'defender_losses' => $battle->defender_losses,
                'created_at' => $battle->created_at,
            ])
            ->toArray();

        return $battles;
    }
}
