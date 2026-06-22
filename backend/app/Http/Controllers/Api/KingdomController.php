<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kingdom;
use App\Services\KingdomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KingdomController extends Controller
{
    protected KingdomService $kingdomService;

    public function __construct(KingdomService $kingdomService)
    {
        $this->kingdomService = $kingdomService;
    }

    public function show(Request $request): JsonResponse
    {
        $kingdom = $request->user()->kingdom()->with(['buildings', 'soldiers'])->first();

        if (!$kingdom) {
            return response()->json([
                'message' => 'Royaume non trouvé',
            ], 404);
        }

        return response()->json([
            'kingdom' => [
                'id' => $kingdom->id,
                'name' => $kingdom->name,
                'level' => $kingdom->level,
                'gold' => $kingdom->gold,
                'wood' => $kingdom->wood,
                'food' => $kingdom->food,
                'defense_power' => $kingdom->getDefensePower(),
                'shield_ends_at' => $kingdom->shield_ends_at,
                'is_shielded' => $kingdom->shield_ends_at && $kingdom->shield_ends_at->isFuture(),
                'created_at' => $kingdom->created_at,
                'owner' => $kingdom->user,
                'buildings' => $kingdom->buildings,
                'soldiers' => $kingdom->soldiers,
            ],
        ]);
    }

    public function index(): JsonResponse
    {
        $ranking = $this->kingdomService->getRanking();

        return response()->json([
            'kingdoms' => $ranking,
        ]);
    }

    public function getById(int $id): JsonResponse
    {
        $kingdom = Kingdom::where('id', $id)->first();

        if (!$kingdom) {
            return response()->json([
                'message' => 'Royaume non trouvé',
            ], 404);
        }

        return response()->json([
            'kingdom' => [
                'id' => $kingdom->id,
                'name' => $kingdom->name,
                'level' => $kingdom->level,
                'user' => $kingdom->user?->name,
                'defense_power' => $kingdom->getDefensePower(),
            ],
        ]);
    }

    public function exchangeResources(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|in:gold,wood,food',
            'to' => 'required|in:gold,wood,food',
            'quantity' => 'required|integer|min:1',
        ]);

        $kingdom = $request->user()->kingdom;

        $result = $this->kingdomService->exchangeResources(
            $kingdom,
            $validated['from'],
            $validated['to'],
            $validated['quantity']
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 400);
        }

        return response()->json($result);
    }

    public function claimDailyChest(Request $request): JsonResponse
    {
        $kingdom = $request->user()->kingdom;
        $result = $this->kingdomService->claimDailyChest($kingdom);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 400);
        }

        return response()->json($result);
    }

    public function softDelete(int $id): JsonResponse
    {
        $kingdom = Kingdom::find($id);

        if (!$kingdom) {
            return response()->json([
                'message' => 'Royaume non trouvé',
            ], 404);
        }

        $kingdom->delete();

        return response()->json([
            'message' => 'Royaume supprimé (corbeille)',
        ]);
    }

    public function restore(int $id): JsonResponse
    {
        $kingdom = Kingdom::withTrashed()->find($id);

        if (!$kingdom) {
            return response()->json([
                'message' => 'Royaume non trouvé',
            ], 404);
        }

        $kingdom->restore();

        return response()->json([
            'message' => 'Royaume restauré',
        ]);
    }

    public function forceDelete(int $id): JsonResponse
    {
        $kingdom = Kingdom::withTrashed()->find($id);

        if (!$kingdom) {
            return response()->json([
                'message' => 'Royaume non trouvé',
            ], 404);
        }

        $kingdom->forceDelete();

        return response()->json([
            'message' => 'Royaume supprimé définitivement',
        ]);
    }

    public function conquerKingdom(Request $request, int $id): JsonResponse
    {
        $defenderKingdom = Kingdom::find($id);
        $attackerKingdom = $request->user()->kingdom;

        if (!$defenderKingdom) {
            return response()->json([
                'message' => 'Royaume cible non trouvé',
            ], 404);
        }

        if (!$attackerKingdom) {
            return response()->json([
                'message' => 'Votre royaume n\'existe pas',
            ], 400);
        }

        // Calculer les récompenses
        $goldStolen = $defenderKingdom->gold / 2;
        $trophiesEarned = $defenderKingdom->level * 10;

        // Transférer le royaume (changer de propriétaire)
        $defenderKingdom->update([
            'user_id' => $request->user()->id,
        ]);

        // Récompenser le joueur
        $attackerKingdom->update([
            'gold' => \DB::raw("gold + {$goldStolen}"),
        ]);

        $request->user()->update([
            'trophies' => \DB::raw("trophies + {$trophiesEarned}"),
        ]);

        return response()->json([
            'message' => 'Royaume conquis avec succès !',
            'kingdom' => $defenderKingdom,
            'rewards' => [
                'gold' => $goldStolen,
                'trophies' => $trophiesEarned,
            ],
        ], 200);
    }
}
