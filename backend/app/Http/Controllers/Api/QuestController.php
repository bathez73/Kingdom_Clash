<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestController extends Controller
{
    /**
     * Retourne les quêtes journalières de l'utilisateur (les crée si absentes)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        Quest::ensureDailyQuests($user->id);

        $quests = Quest::where('user_id', $user->id)
            ->where('date', now()->toDateString())
            ->get();

        return response()->json(['quests' => $quests]);
    }

    /**
     * Réclamer la récompense d'une quête complétée
     */
    public function claim(Request $request, int $questId): JsonResponse
    {
        $user = $request->user();

        $quest = Quest::where('id', $questId)
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->where('claimed', false)
            ->first();

        if (!$quest) {
            return response()->json(['message' => 'Quête introuvable ou déjà réclamée'], 404);
        }

        $kingdom = $user->kingdom;
        if (!$kingdom) {
            return response()->json(['message' => 'Royaume introuvable'], 404);
        }

        $reward = $quest->reward;

        // Appliquer les ressources
        $kingdom->addResources(
            $reward['gold'] ?? 0,
            $reward['wood'] ?? 0,
            $reward['food'] ?? 0
        );

        // Appliquer l'XP et vérifier le level up
        $xpGained = $reward['xp'] ?? 0;
        $leveledUp = false;
        $newLevel = $user->level;

        if ($xpGained > 0) {
            $user->xp += $xpGained;
            $xpNeeded = $user->level * 100; // 100 XP par niveau

            if ($user->xp >= $xpNeeded) {
                $user->xp -= $xpNeeded;
                $user->level += 1;
                $leveledUp = true;
                $newLevel = $user->level;
            }
            $user->save();
        }

        $quest->claimed = true;
        $quest->save();

        return response()->json([
            'message' => 'Récompense réclamée !',
            'reward' => $reward,
            'xp_gained' => $xpGained,
            'leveled_up' => $leveledUp,
            'new_level' => $newLevel,
            'kingdom' => $kingdom->fresh(),
            'user' => $user->fresh(),
        ]);
    }
}
