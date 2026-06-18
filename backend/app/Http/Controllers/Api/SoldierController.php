<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Soldier;
use App\Services\SoldierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SoldierController extends Controller
{
    protected SoldierService $soldierService;

    public function __construct(SoldierService $soldierService)
    {
        $this->soldierService = $soldierService;
    }

    public function index(Request $request): JsonResponse
    {
        $soldiers = $request->user()->kingdom->soldiers()->get();

        return response()->json([
            'soldiers' => $soldiers->map(fn ($soldier) => [
                'id' => $soldier->id,
                'type' => $soldier->type,
                'quantity' => $soldier->quantity,
                'created_at' => $soldier->created_at,
            ]),
        ]);
    }

    public function train(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:swordsman,archer,cavalry',
            'quantity' => 'required|integer|min:1',
        ]);

        $kingdom = $request->user()->kingdom;

        $result = $this->soldierService->trainSoldiers(
            $kingdom,
            $validated['type'],
            $validated['quantity']
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 400);
        }

        return response()->json($result);
    }
}
