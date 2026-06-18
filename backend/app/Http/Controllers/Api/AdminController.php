<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kingdom;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function getAllUsers(Request $request): JsonResponse
    {
        $users = User::all();

        return response()->json([
            'users' => $users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'created_at' => $user->created_at,
            ]),
        ]);
    }

    public function getTrashedUsers(Request $request): JsonResponse
    {
        $trashedUsers = User::onlyTrashed()->get();

        return response()->json([
            'trashed_users' => $trashedUsers->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'deleted_at' => $user->deleted_at,
            ]),
        ]);
    }

    public function restoreUser(int $userId): JsonResponse
    {
        $user = User::withTrashed()->find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        $user->restore();

        return response()->json([
            'message' => 'Utilisateur restauré',
        ]);
    }

    public function forceDeleteUser(int $userId): JsonResponse
    {
        $user = User::withTrashed()->find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        $user->forceDelete();

        return response()->json([
            'message' => 'Utilisateur supprimé définitivement',
        ]);
    }

    public function getAllKingdoms(Request $request): JsonResponse
    {
        $kingdoms = Kingdom::all();

        return response()->json([
            'kingdoms' => $kingdoms->map(fn ($kingdom) => [
                'id' => $kingdom->id,
                'name' => $kingdom->name,
                'level' => $kingdom->level,
                'gold' => $kingdom->gold,
                'wood' => $kingdom->wood,
                'food' => $kingdom->food,
                'user' => $kingdom->user?->name,
                'created_at' => $kingdom->created_at,
            ]),
        ]);
    }

    public function getTrashedKingdoms(Request $request): JsonResponse
    {
        $trashedKingdoms = Kingdom::onlyTrashed()->get();

        return response()->json([
            'trashed_kingdoms' => $trashedKingdoms->map(fn ($kingdom) => [
                'id' => $kingdom->id,
                'name' => $kingdom->name,
                'user' => $kingdom->user?->name,
                'deleted_at' => $kingdom->deleted_at,
            ]),
        ]);
    }

    public function restoreKingdom(int $kingdomId): JsonResponse
    {
        $kingdom = Kingdom::withTrashed()->find($kingdomId);

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

    public function forceDeleteKingdom(int $kingdomId): JsonResponse
    {
        $kingdom = Kingdom::withTrashed()->find($kingdomId);

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

    public function banUser(Request $request, int $userId): JsonResponse
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        $user->delete();

        return response()->json([
            'message' => 'Utilisateur banni (soft delete)',
            'user_id' => $user->id,
        ]);
    }

    public function unbanUser(int $userId): JsonResponse
    {
        $user = User::withTrashed()->find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        $user->restore();

        return response()->json([
            'message' => 'Utilisateur débanni',
            'user_id' => $user->id,
        ]);
    }
}

