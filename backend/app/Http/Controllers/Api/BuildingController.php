<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Services\KingdomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    protected KingdomService $kingdomService;

    public function __construct(KingdomService $kingdomService)
    {
        $this->kingdomService = $kingdomService;
    }

    public function index(Request $request): JsonResponse
    {
        $buildings = $request->user()->kingdom->buildings()->get();

        return response()->json([
            'buildings' => $buildings->map(fn ($building) => [
                'id' => $building->id,
                'type' => $building->type,
                'level' => $building->level,
                'upgrade_ends_at' => $building->upgrade_ends_at,
                'created_at' => $building->created_at,
            ]),
        ]);
    }

    public function upgrade(Request $request, int $buildingId): JsonResponse
    {
        $validated = $request->validate([]);
        $kingdom = $request->user()->kingdom;
        $building = $kingdom->buildings()->find($buildingId);

        if (!$building) {
            return response()->json([
                'message' => 'Bâtiment non trouvé',
            ], 404);
        }

        if ($building->upgrade_ends_at !== null) {
            return response()->json([
                'message' => 'Ce bâtiment est déjà en cours de construction',
            ], 400);
        }

        $result = $this->kingdomService->upgradeBuilding($building);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 400);
        }

        return response()->json($result);
    }

    public function destroy(int $buildingId): JsonResponse
    {
        $building = Building::find($buildingId);

        if (!$building) {
            return response()->json([
                'message' => 'Bâtiment non trouvé',
            ], 404);
        }

        $building->delete();

        return response()->json([
            'message' => 'Bâtiment supprimé (corbeille)',
        ]);
    }

    public function restore(int $buildingId): JsonResponse
    {
        $building = Building::withTrashed()->find($buildingId);

        if (!$building) {
            return response()->json([
                'message' => 'Bâtiment non trouvé',
            ], 404);
        }

        $building->restore();

        return response()->json([
            'message' => 'Bâtiment restauré',
        ]);
    }

    public function forceDelete(int $buildingId): JsonResponse
    {
        $building = Building::withTrashed()->find($buildingId);

        if (!$building) {
            return response()->json([
                'message' => 'Bâtiment non trouvé',
            ], 404);
        }

        $building->forceDelete();

        return response()->json([
            'message' => 'Bâtiment supprimé définitivement',
        ]);
    }
}
