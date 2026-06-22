<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chest;
use App\Models\Quest;
use App\Models\User;
use App\Models\Kingdom;
use App\Services\BattleService;
use App\Services\KingdomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BattleController extends Controller
{
    protected $battleService;
    protected $kingdomService;

    public function __construct(BattleService $battleService, KingdomService $kingdomService)
    {
        $this->battleService = $battleService;
        $this->kingdomService = $kingdomService;
    }

    public function start(Request $request)
    {
        $user = $request->user();
        
        // Vérifie si l'utilisateur a un royaume, sinon le crée
        $userKingdom = $user->kingdom()->with('soldiers')->first();
        if (!$userKingdom) {
            $userKingdom = $this->kingdomService->createKingdom($user->id, "Royaume de {$user->name}");
            $this->kingdomService->initializeBuildings($userKingdom);
            $userKingdom = $user->kingdom()->with('soldiers')->first();
        }
        
        // Find an opponent with kingdom
        $opponent = User::where('id', '!=', $user->id)
            ->whereHas('kingdom')
            ->where('trophies', '>=', $user->trophies - 100)
            ->where('trophies', '<=', $user->trophies + 100)
            ->inRandomOrder()
            ->with('kingdom')
            ->first();
        
        if (!$opponent) {
            $opponent = User::where('id', '!=', $user->id)
                ->whereHas('kingdom')
                ->inRandomOrder()
                ->with('kingdom')
                ->first();
        }
        
        if (!$opponent) {
            return response()->json(['error' => 'Aucun adversaire trouvé'], 404);
        }
        
        return response()->json([
            'opponent' => $opponent,
            'opponent_kingdom' => $opponent->kingdom,
            'user_kingdom' => $userKingdom,
            'battle_id' => uniqid('battle_'),
        ]);
    }

    public function attack(Request $request, $opponentId)
    {
        $user = $request->user();
        
        // Vérifie si l'utilisateur a un royaume
        $userKingdom = $user->kingdom;
        if (!$userKingdom) {
            $userKingdom = $this->kingdomService->createKingdom($user->id, "Royaume de {$user->name}");
            $this->kingdomService->initializeBuildings($userKingdom);
        }
        
        // Trouve le royaume adverse
        $opponentKingdom = Kingdom::find($opponentId);
        if (!$opponentKingdom) {
            return response()->json(['error' => 'Royaume adverse non trouvé'], 404);
        }
        
        $miniGameScore = $request->input('score', 0);
        $deckCards     = $request->input('deck_cards', []);

        $result = $this->battleService->attack($userKingdom, $opponentKingdom, $request->input('troops', []), $miniGameScore, $deckCards);
        
        if (!$result['success']) {
            return response()->json(['error' => $result['message'], 'shielded' => $result['shielded'] ?? false], 400);
        }
        
        // Mettre à jour les trophées en fonction du score
        $baseTrophyChange = $result['attacker_won'] ? 5 : -3;
        $trophyBonus = floor($miniGameScore / 30); // +1 trophée par 30 points
        $trophyChange = $baseTrophyChange + ($result['attacker_won'] ? $trophyBonus : -$trophyBonus);
        $user->trophies = max(0, $user->trophies + $trophyChange);
        $user->save();
        
        // Vérifier si le roi adverse a un royaume pour mettre à jour ses trophées
        $opponentUser = $opponentKingdom->user;
        if ($opponentUser) {
            $opponentUser->trophies = max(0, $opponentUser->trophies - $trophyChange);
            $opponentUser->save();
        }
        
        // Gagner un coffre en fonction du score
        $chest = null;
        if ($result['attacker_won'] && ($miniGameScore > 100 || rand(1, 3) === 1)) {
            if ($miniGameScore > 100) {
                $chestType = 'magical';
            } else if ($miniGameScore > 50) {
                $chestType = 'gold';
            } else {
                $chestType = 'silver';
            }
            $chest = $user->chests()->create([
                'type' => $chestType,
                'status' => 'locked',
            ]);
        }

        // Quête win_battle déjà incrémentée dans BattleService
        // Détecter si l'utilisateur vient de progresser en arène
        $arena = $this->getArena($user->fresh()->trophies);
        
        return response()->json([
            ...$result,
            'user'         => $user->fresh(),
            'trophy_change' => $trophyChange,
            'arena'        => $arena,
            'chest'        => $chest,
        ]);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        $userKingdom = $user->kingdom;
        if (!$userKingdom) {
            return response()->json(['battles' => []]);
        }
        $battles = $this->battleService->getBattleHistory($userKingdom);
        return response()->json(['battles' => $battles]);
    }

    /**
     * Retourne le nom de l'arène selon les trophées
     */
    private function getArena(int $trophies): array
    {
        $arenas = [
            ['name' => 'Camp d\'Entraînement', 'icon' => '🏕️', 'min' => 0,    'max' => 299],
            ['name' => 'Goblin Stadium',        'icon' => '👺', 'min' => 300,  'max' => 599],
            ['name' => 'Bone Pit',              'icon' => '💀', 'min' => 600,  'max' => 999],
            ['name' => 'Barbarian Bowl',        'icon' => '⚔️', 'min' => 1000, 'max' => 1399],
            ['name' => 'P.E.K.K.A\'s Playhouse','icon' => '🤖', 'min' => 1400, 'max' => 1799],
            ['name' => 'Spell Valley',          'icon' => '✨', 'min' => 1800, 'max' => 2299],
            ['name' => 'Légendaire',            'icon' => '🏆', 'min' => 2300, 'max' => PHP_INT_MAX],
        ];

        foreach ($arenas as $arena) {
            if ($trophies >= $arena['min'] && $trophies <= $arena['max']) {
                return $arena;
            }
        }

        return $arenas[0];
    }
}
