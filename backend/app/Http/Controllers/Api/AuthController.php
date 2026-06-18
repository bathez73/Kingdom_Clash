<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Chest;
use App\Models\Deck;
use App\Models\User;
use App\Services\KingdomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    protected KingdomService $kingdomService;

    public function __construct(KingdomService $kingdomService)
    {
        $this->kingdomService = $kingdomService;
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'trophies' => 0,
            'gold' => 500,
            'gems' => 100,
            'level' => 1,
        ]);

        $user->assignRole('player');

        $kingdom = $this->kingdomService->createKingdom($user->id, "Royaume de {$user->name}");
        $this->kingdomService->initializeBuildings($kingdom);
        
        $this->initializeClashRoyaleUser($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inscription réussie',
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'is_admin' => $user->hasRole('admin'),
            'kingdom' => $kingdom,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Identifiants invalides',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $kingdom = $user->kingdom;
        
        // If no kingdom exists (like for admin), create one
        if (!$kingdom) {
            $kingdomService = $this->kingdomService;
            $kingdom = $kingdomService->createKingdom($user->id, "Royaume de {$user->name}");
            $kingdomService->initializeBuildings($kingdom);
        }
        
        // Initialize Clash Royale data if not already done
        if ($user->cards()->count() === 0) {
            $this->initializeClashRoyaleUser($user);
        }

        return response()->json([
            'message' => 'Connexion réussie',
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'is_admin' => $user->hasRole('admin'),
            'kingdom' => $kingdom,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $request->user()->id,
            'password' => 'string|min:8|nullable'
        ]);

        $user = $request->user();
        $user->fill($request->only(['name', 'email']));
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => $user
        ]);
    }
    
    private function initializeClashRoyaleUser(User $user): void
    {
        // Give all cards to player with some copies
        $allCards = Card::all();
        foreach ($allCards as $card) {
            $user->cards()->syncWithoutDetaching([
                $card->id => [
                    'level' => 1,
                    'copies_count' => rand(5, 15),
                ]
            ]);
        }
        
        // Create initial deck with 8 cards
        $initialDeckCards = $allCards->take(8)->pluck('id')->toArray();
        Deck::firstOrCreate(
            ['user_id' => $user->id],
            ['slots' => $initialDeckCards]
        );
        
        // Give some chests
        Chest::firstOrCreate(
            ['user_id' => $user->id, 'type' => 'silver', 'status' => 'locked'],
            ['type' => 'silver', 'status' => 'locked']
        );
        Chest::firstOrCreate(
            ['user_id' => $user->id, 'type' => 'gold', 'status' => 'locked'],
            ['type' => 'gold', 'status' => 'locked']
        );
    }
}
