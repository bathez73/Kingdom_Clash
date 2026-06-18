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
}
