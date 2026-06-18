<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UnlockChestJob;
use App\Models\Card;
use App\Models\Chest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ChestController extends Controller
{
    public function index(Request $request)
    {
        $chests = $request->user()->chests()->take(4)->get();
        return response()->json(['chests' => $chests]);
    }

    public function startUnlock(Request $request, Chest $chest)
    {
        $user = $request->user();
        
        // Verify this chest belongs to user
        if ($chest->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if ($chest->status !== 'locked') {
            return response()->json(['error' => 'Chest is not locked'], 422);
        }
        
        // Check if any other chest is unlocking
        $unlockingChest = $user->chests()->where('status', 'unlocking')->first();
        if ($unlockingChest) {
            return response()->json(['error' => 'Another chest is already unlocking'], 422);
        }
        
        // Determine unlock time
        $unlockTime = match ($chest->type) {
            'silver' => 60, // 1 minute
            'gold' => 300, // 5 minutes
            'magical' => 600, // 10 minutes
        };
        
        $unlockEndsAt = Carbon::now()->addSeconds($unlockTime);
        
        $chest->update([
            'status' => 'unlocking',
            'unlock_ends_at' => $unlockEndsAt,
        ]);
        
        // Dispatch job to mark as ready
        UnlockChestJob::dispatch($chest)->delay($unlockTime);
        
        return response()->json(['chest' => $chest]);
    }

    public function open(Request $request, Chest $chest)
    {
        $user = $request->user();
        
        // Verify this chest belongs to user
        if ($chest->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if ($chest->status !== 'ready') {
            return response()->json(['error' => 'Chest is not ready to open'], 422);
        }
        
        // Generate rewards
        $goldReward = match ($chest->type) {
            'silver' => rand(50, 100),
            'gold' => rand(100, 250),
            'magical' => rand(250, 500),
        };
        
        // Give gold
        $user->gold += $goldReward;
        $user->save();
        
        // Give card copies
        $cardCount = match ($chest->type) {
            'silver' => rand(2, 4),
            'gold' => rand(4, 8),
            'magical' => rand(8, 15),
        };
        
        $rewardedCards = [];
        for ($i = 0; $i < $cardCount; $i++) {
            $randomCard = Card::inRandomOrder()->first();
            $userCard = $user->cards()->find($randomCard->id);
            
            if ($userCard) {
                $user->cards()->updateExistingPivot($randomCard->id, [
                    'copies_count' => $userCard->pivot->copies_count + 1,
                ]);
            } else {
                $user->cards()->attach($randomCard->id, [
                    'level' => 1,
                    'copies_count' => 1,
                ]);
            }
            
            $rewardedCards[] = $randomCard;
        }
        
        // Delete the chest
        $chest->delete();
        
        return response()->json([
            'message' => 'Chest opened successfully!',
            'gold' => $goldReward,
            'cards' => $rewardedCards,
            'user' => $user,
        ]);
    }
}
