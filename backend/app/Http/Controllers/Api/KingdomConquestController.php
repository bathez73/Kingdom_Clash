<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kingdom;
use App\Models\Chest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KingdomConquestController extends Controller
{
    /**
     * Retourne la liste des royaumes NPC conquérables, triés par difficulté.
     * Inclut un aperçu des récompenses calculées pour le joueur connecté.
     */
    public function index(Request $request): JsonResponse
    {
        $user          = $request->user();
        $playerKingdom = $user->kingdom;

        $kingdoms = Kingdom::where('status', 'enemy')
            ->whereNull('deleted_at')
            ->orderBy('difficulty')
            ->orderBy('level')
            ->get()
            ->map(fn ($k) => [
                'id'             => $k->id,
                'name'           => $k->name,
                'level'          => $k->level,
                'difficulty'     => $k->difficulty,
                'defense_power'  => $k->defense_power,
                'gold_capacity'  => $k->gold_capacity,
                'gold'           => $k->gold,
                'status'         => $k->status,
                'reward_preview' => $this->computeRewards($k, $user),
            ]);

        return response()->json([
            'kingdoms'       => $kingdoms,
            'player_kingdom' => $playerKingdom,
        ]);
    }

    /**
     * Valide la fin de partie et transfère la propriété du royaume conquis.
     *
     * Payload attendu :
     *   kingdom_id  int  — ID du royaume à conquérir
     *   score       int  — Score de jeu (kills × bonus)
     *   kills       int  — Nombre d'ennemis éliminés
     *   time_sec    int  — Durée totale de la bataille en secondes
     */
    public function conquer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kingdom_id' => 'required|integer|exists:kingdoms,id',
            'score'      => 'required|integer|min:0|max:9999999',
            'kills'      => 'required|integer|min:0|max:500',
            'time_sec'   => 'required|integer|min:1|max:7200',
        ]);

        $user = $request->user();

        // Vérification anti-cheat : ratio kills/temps physiquement impossible
        // On autorise au maximum 5 kills par seconde (rythme humainement impossible)
        if ($validated['kills'] > 0 && $validated['time_sec'] > 0) {
            $killRate = $validated['kills'] / $validated['time_sec'];
            if ($killRate > 5.0) {
                Log::warning('Possible cheat attempt', [
                    'user_id'    => $user->id,
                    'kingdom_id' => $validated['kingdom_id'],
                    'kills'      => $validated['kills'],
                    'time_sec'   => $validated['time_sec'],
                    'kill_rate'  => $killRate,
                ]);
                return response()->json([
                    'message' => 'Données de jeu incohérentes. La conquête ne peut pas être validée.',
                ], 422);
            }
        }

        return DB::transaction(function () use ($user, $validated) {
            // Verrouillage pessimiste : empêche la double-conquête par race condition
            $targetKingdom = Kingdom::lockForUpdate()->findOrFail($validated['kingdom_id']);

            if ($targetKingdom->status !== 'enemy') {
                return response()->json([
                    'message' => 'Ce royaume a déjà été conquis ou n\'est pas disponible.',
                ], 422);
            }

            if ($targetKingdom->user_id === $user->id) {
                return response()->json([
                    'message' => 'Vous possédez déjà ce royaume.',
                ], 422);
            }

            $rewards = $this->computeRewards($targetKingdom, $user, $validated);

            // ── Transfert de propriété du royaume ─────────────────────────────
            $targetKingdom->update([
                'user_id' => $user->id,
                'status'  => 'player',
            ]);

            // ── Distribution des ressources pillées au royaume principal ───────
            // On exclut le royaume qu'on vient de conquérir (il appartient maintenant au joueur)
            $playerKingdom = Kingdom::where('user_id', $user->id)
                ->where('id', '!=', $targetKingdom->id)
                ->where('status', 'player')
                ->whereNull('deleted_at')
                ->first();

            if ($playerKingdom) {
                $playerKingdom->addResources(
                    $rewards['gold'],
                    $rewards['wood'],
                    $rewards['food']
                );
            }

            // ── Trophées ───────────────────────────────────────────────────────
            $user->trophies = $user->trophies + $rewards['trophies'];

            // ── XP et level-up (une seule itération par conquête) ──────────────
            $user->xp  += $rewards['xp'];
            $xpNeeded   = $user->level * 100;
            $leveledUp  = false;
            if ($user->xp >= $xpNeeded) {
                $user->xp    -= $xpNeeded;
                $user->level += 1;
                $leveledUp    = true;
            }
            $user->save();

            // ── Coffre de butin de guerre ──────────────────────────────────────
            $chest = null;
            if ($rewards['chest_type']) {
                $chest = Chest::create([
                    'user_id' => $user->id,
                    'type'    => $rewards['chest_type'],
                    'status'  => 'locked',
                ]);
            }

            // ── Notification dans le royaume principal ─────────────────────────
            if ($playerKingdom) {
                $playerKingdom->notifications()->create([
                    'type'    => 'kingdom_conquered',
                    'title'   => '⚔️ Royaume Conquis !',
                    'message' => "Vous avez conquis \"{$targetKingdom->name}\" !" .
                                 " +{$rewards['gold']} 💰 +{$rewards['trophies']} 🏆",
                    'data'    => [
                        'conquered_kingdom_id'   => $targetKingdom->id,
                        'conquered_kingdom_name' => $targetKingdom->name,
                        'rewards'                => $rewards,
                    ],
                ]);
            }

            // ── Audit log ──────────────────────────────────────────────────────
            Log::info('Kingdom conquered', [
                'user_id'            => $user->id,
                'kingdom_id'         => $targetKingdom->id,
                'kingdom_name'       => $targetKingdom->name,
                'difficulty'         => $targetKingdom->difficulty,
                'score'              => $validated['score'],
                'kills'              => $validated['kills'],
                'time_sec'           => $validated['time_sec'],
                'gold_rewarded'      => $rewards['gold'],
                'trophies_rewarded'  => $rewards['trophies'],
                'chest_type'         => $rewards['chest_type'],
                'leveled_up'         => $leveledUp,
            ]);

            return response()->json([
                'success'           => true,
                'message'           => "Royaume \"{$targetKingdom->name}\" conquis avec succès !",
                'rewards'           => $rewards,
                'leveled_up'        => $leveledUp,
                'new_level'         => $user->level,
                'chest'             => $chest,
                'conquered_kingdom' => $targetKingdom->fresh(),
                'user'              => $user->fresh(),
            ]);
        });
    }

    /**
     * Calcule les récompenses de conquête.
     *
     * Quand $gameData est fourni (fin de partie réelle), les récompenses
     * sont ajustées en fonction du score, des kills et du temps.
     * Sans $gameData, retourne une preview indicative pour le lobby.
     *
     * @param Kingdom $kingdom   Le royaume conquis (ou à apercevoir)
     * @param object  $user      Le joueur
     * @param array   $gameData  Données de fin de partie [score, kills, time_sec]
     */
    private function computeRewards(Kingdom $kingdom, object $user, array $gameData = []): array
    {
        $score   = $gameData['score']    ?? 0;
        $kills   = $gameData['kills']    ?? 0;
        $timeSec = $gameData['time_sec'] ?? 0;

        // ── Or pillé ───────────────────────────────────────────────────────────
        // Base : 40% → 60% de la gold_capacity selon la difficulté
        $goldPct  = 0.40 + ($kingdom->difficulty * 0.04);
        $baseGold = (int) ($kingdom->gold_capacity * $goldPct);

        // Bonus score : jusqu'à +50% (atteint à 2000 points)
        $scoreBonus = min(0.50, $score / 2000);

        // Bonus vitesse : +20% si la bataille dure moins de 2 minutes
        $speedBonus = ($timeSec > 0 && $timeSec < 120) ? 0.20 : 0.0;

        $totalGoldMultiplier = 1.0 + $scoreBonus + $speedBonus;
        $gold = (int) ($baseGold * $totalGoldMultiplier);

        // ── Autres ressources ──────────────────────────────────────────────────
        $wood      = (int) ($kingdom->level * 80  * (1 + $scoreBonus));
        $food      = (int) ($kingdom->level * 60  * (1 + $scoreBonus));
        $trophies  = $kingdom->difficulty * 30 + (int) ($score / 100);
        $xp        = $kingdom->difficulty * 150 + $kills * 10;

        // ── Type de coffre selon la difficulté ────────────────────────────────
        $chestType = match (true) {
            $kingdom->difficulty >= 5 => 'magical',
            $kingdom->difficulty >= 3 => 'gold',
            default                   => 'silver',
        };

        return compact('gold', 'wood', 'food', 'trophies', 'xp', 'chestType', 'score', 'kills');
    }
}
