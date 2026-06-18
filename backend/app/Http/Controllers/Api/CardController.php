<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get user's cards with pivot data
        $userCards = $user->cards()->withPivot('level', 'copies_count')->get();
        
        // Get deck
        $deck = $user->deck;
        
        return response()->json([
            'cards' => $userCards,
            'deck' => $deck,
        ]);
    }

    public function updateDeck(Request $request)
    {
        $validated = $request->validate([
            'slots' => 'required|array|max:8',
            'slots.*' => 'exists:cards,id',
        ]);

        $user = $request->user();
        
        // Check for duplicates
        if (count($validated['slots']) !== count(array_unique($validated['slots']))) {
            return response()->json(['error' => 'Deck cannot have duplicate cards'], 422);
        }

        // Update or create deck
        $deck = $user->deck()->updateOrCreate(
            ['user_id' => $user->id],
            ['slots' => $validated['slots']]
        );

        return response()->json(['deck' => $deck]);
    }

    public function upgradeCard(Request $request, Card $card)
    {
        $user = $request->user();
        $userCard = $user->cards()->findOrFail($card->id);
        
        $currentLevel = $userCard->pivot->level;
        $currentCopies = $userCard->pivot->copies_count;
        
        $copiesNeeded = $currentLevel * 10;
        $goldNeeded = $currentLevel * 150;
        
        if ($currentCopies < $copiesNeeded) {
            return response()->json(['error' => 'Not enough copies'], 422);
        }
        
        if ($user->gold < $goldNeeded) {
            return response()->json(['error' => 'Not enough gold'], 422);
        }
        
        // Upgrade
        $user->gold -= $goldNeeded;
        $user->save();
        
        $user->cards()->updateExistingPivot($card->id, [
            'level' => $currentLevel + 1,
            'copies_count' => $currentCopies - $copiesNeeded,
        ]);
        
        $userCard = $user->cards()->withPivot('level', 'copies_count')->findOrFail($card->id);
        
        return response()->json([
            'message' => 'Card upgraded successfully',
            'card' => $userCard,
            'user' => $user,
        ]);
    }
}
