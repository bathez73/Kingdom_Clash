<?php

namespace App\Services;

use App\Models\Battle;
use App\Models\Kingdom;
use App\Models\Quest;
use App\Models\Soldier;
use Illuminate\Support\Facades\DB;

class BattleService
{
    /**
     * Lancer une attaque
     */
    public function attack(Kingdom $attacker, Kingdom $defender, array $troops, ?int $miniGameScore = 0, array $deckCards = []): array
    {
        if ($attacker->id === $defender->id) {
            return ['success' => false, 'message' => 'Vous ne pouvez pas attaquer votre propre royaume'];
        }

        // Vérifier le bouclier du défenseur
        if ($defender->shield_ends_at && $defender->shield_ends_at->isFuture()) {
            $minutesLeft = (int) now()->diffInMinutes($defender->shield_ends_at);
            return [
                'success' => false,
                'message' => "Ce royaume est protégé par un bouclier encore {$minutesLeft} minute(s)",
                'shielded' => true,
            ];
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

        return DB::transaction(function () use ($attacker, $defender, $availableTroops, $miniGameScore, $deckCards) {
            // --- PUISSANCE D'ATTAQUE ---
            $attackPower = 0;

            // 1) Soldats classiques
            foreach ($availableTroops as $type => $quantity) {
                $attackPower += $quantity * Soldier::getAttackPower($type);
            }

            // 2) Bonus des cartes du deck (base_damage / 10 par carte)
            $cardBonus = 0;
            foreach ($deckCards as $card) {
                $cardBonus += ($card['base_damage'] ?? 0) * ($card['level'] ?? 1) / 10;
            }
            $attackPower += $cardBonus;

            // 3) Bonus du mini-jeu (max +100%)
            $attackBonus = min(1.0, $miniGameScore / 200);
            $attackPower *= (1 + $attackBonus);

            // --- PUISSANCE DE DÉFENSE ---
            $defensePower = $defender->getDefensePower();

            // Facteur aléatoire réduit (0.9 – 1.1) pour que la stratégie compte
            $randomFactor = 0.9 + (lcg_value() * 0.2);
            $finalAttack = $attackPower * $randomFactor;
            $attacker_won = $finalAttack > $defensePower;

            // --- CALCUL DES PERTES ---
            if ($attacker_won) {
                $attackerLossPercentage = 0.30;
                $defenderLossPercentage = 0.80;
                $goldStolen = (int) ($defender->gold * 0.5);
            } else {
                $attackerLossPercentage = 0.80;
                $defenderLossPercentage = 0.30;
                $goldStolen = 0;
            }

            $attackerLosses = [];
            foreach ($availableTroops as $type => $quantity) {
                $losses = (int) ($quantity * $attackerLossPercentage);
                if ($losses > 0) $attackerLosses[$type] = $losses;
            }

            $defenderLosses = [];
            foreach ($defender->soldiers()->get() as $soldier) {
                $losses = (int) ($soldier->quantity * $defenderLossPercentage);
                if ($losses > 0) $defenderLosses[$soldier->type] = $losses;
            }

            // --- ENREGISTREMENT BATAILLE ---
            $battle = Battle::create([
                'attacker_id'      => $attacker->id,
                'defender_id'      => $defender->id,
                'result'           => $attacker_won ? 'attacker_won' : 'defender_won',
                'gold_stolen'      => $goldStolen,
                'attacker_losses'  => $attackerLosses,
                'defender_losses'  => $defenderLosses,
            ]);

            // --- APPLIQUER LES PERTES ---
            foreach ($attackerLosses as $type => $losses) {
                $soldier = Soldier::where('kingdom_id', $attacker->id)->where('type', $type)->first();
                if ($soldier) {
                    $soldier->quantity = max(0, $soldier->quantity - $losses);
                    $soldier->save();
                }
            }

            foreach ($defenderLosses as $type => $losses) {
                $soldier = Soldier::where('kingdom_id', $defender->id)->where('type', $type)->first();
                if ($soldier) {
                    $soldier->quantity = max(0, $soldier->quantity - $losses);
                    $soldier->save();
                }
            }

            // --- TRANSFERT D'OR ---
            if ($attacker_won) {
                $defender->update(['gold' => max(0, $defender->gold - $goldStolen)]);
                $attacker->addResources($goldStolen);
            }

            // --- BOUCLIER POUR LA VICTIME (30 minutes) ---
            $loser = $attacker_won ? $defender : $attacker;
            $loser->update(['shield_ends_at' => now()->addMinutes(30)]);

            // --- XP & LEVEL UP POUR L'ATTAQUANT ---
            $xpGained = $attacker_won ? 80 : 20;
            $attackerUser = $attacker->user;
            if ($attackerUser) {
                $attackerUser->xp += $xpGained;
                $xpNeeded = $attackerUser->level * 100;
                $leveledUp = false;
                if ($attackerUser->xp >= $xpNeeded) {
                    $attackerUser->xp -= $xpNeeded;
                    $attackerUser->level += 1;
                    $leveledUp = true;
                }
                $attackerUser->save();
            }

            // --- PROGRESSION DES QUÊTES ---
            if ($attackerUser) {
                if ($attacker_won) {
                    Quest::addProgress($attackerUser->id, 'win_battle', 1);
                }
            }

            // --- NOTIFICATIONS ---
            $defenderMessage = $attacker_won
                ? "Le royaume {$attacker->name} vous a attaqué et a gagné ! Vous avez perdu {$goldStolen} or."
                : "Le royaume {$attacker->name} vous a attaqué mais a échoué !";

            $attacker->notifications()->create([
                'type'    => 'attack_sent',
                'title'   => 'Attaque lancée',
                'message' => $attacker_won
                    ? "Vous avez attaqué {$defender->name} et avez remporté {$goldStolen} or !"
                    : "Vous avez attaqué {$defender->name} mais avez été repoussé.",
                'data' => [
                    'battle_id'       => $battle->id,
                    'target_kingdom'  => $defender->name,
                    'result'          => $battle->result,
                ],
            ]);

            $defender->notifications()->create([
                'type'    => 'attack_received',
                'title'   => 'Attaque reçue',
                'message' => $defenderMessage,
                'data' => [
                    'battle_id'        => $battle->id,
                    'attacker_kingdom' => $attacker->name,
                    'result'           => $battle->result,
                    'shield_minutes'   => 30,
                ],
            ]);

            broadcast(new \App\Events\BattleOccurred($battle));
            broadcast(new \App\Events\RankingUpdated());

            return [
                'success'       => true,
                'battle'        => $battle,
                'attacker_won'  => $attacker_won,
                'gold_stolen'   => $goldStolen,
                'xp_gained'     => $xpGained,
                'card_bonus'    => (int) $cardBonus,
                'attack_power'  => (int) $finalAttack,
                'defense_power' => $defensePower,
                'message'       => $attacker_won ? 'Victoire !' : 'Défaite !',
            ];
        });
    }

    /**
     * Obtenir l'historique des batailles
     */
    public function getBattleHistory(Kingdom $kingdom, int $limit = 50): array
    {
        return Battle::where(function ($query) use ($kingdom) {
            $query->where('attacker_id', $kingdom->id)
                  ->orWhere('defender_id', $kingdom->id);
        })
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($battle) use ($kingdom) {
                $isAttacker  = $battle->attacker_id === $kingdom->id;
                $attackerWon = $battle->result === 'attacker_won';
                $weWon       = $isAttacker ? $attackerWon : !$attackerWon;
                $opponentKingdom = $isAttacker ? $battle->defender : $battle->attacker;

                return [
                    'id'           => $battle->id,
                    'attacker_won' => $weWon,
                    'defender_user' => [
                        'name' => $opponentKingdom?->name ?? 'Inconnu',
                    ],
                    'gold_stolen'  => $isAttacker ? $battle->gold_stolen : 0,
                    'trophy_change' => 0,
                    'created_at'   => $battle->created_at,
                ];
            })
            ->toArray();
    }
}
