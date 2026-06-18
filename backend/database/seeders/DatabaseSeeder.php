<?php

namespace Database\Seeders;

use App\Models\Card;
use App\Models\Chest;
use App\Models\Deck;
use App\Models\Kingdom;
use App\Models\User;
use App\Services\KingdomService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Créer les rôles
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'moderator']);
        Role::firstOrCreate(['name' => 'player']);

        // Créer des cartes initiales
        $cards = [
            ['name' => 'Knight', 'type' => 'common', 'base_damage' => 50, 'base_hp' => 300, 'elixir_cost' => 3, 'icon' => '⚔️'],
            ['name' => 'Archers', 'type' => 'common', 'base_damage' => 40, 'base_hp' => 120, 'elixir_cost' => 3, 'icon' => '🏹'],
            ['name' => 'Goblins', 'type' => 'common', 'base_damage' => 60, 'base_hp' => 100, 'elixir_cost' => 2, 'icon' => '👺'],
            ['name' => 'Giant', 'type' => 'rare', 'base_damage' => 80, 'base_hp' => 1200, 'elixir_cost' => 5, 'icon' => '👹'],
            ['name' => 'Wizard', 'type' => 'rare', 'base_damage' => 120, 'base_hp' => 200, 'elixir_cost' => 5, 'icon' => '🧙'],
            ['name' => 'Baby Dragon', 'type' => 'epic', 'base_damage' => 100, 'base_hp' => 500, 'elixir_cost' => 4, 'icon' => '🐉'],
            ['name' => 'P.E.K.K.A', 'type' => 'epic', 'base_damage' => 250, 'base_hp' => 1500, 'elixir_cost' => 7, 'icon' => '🤖'],
            ['name' => 'Sparky', 'type' => 'legendary', 'base_damage' => 500, 'base_hp' => 800, 'elixir_cost' => 6, 'icon' => '⚡'],
        ];

        foreach ($cards as $cardData) {
            Card::firstOrCreate(['name' => $cardData['name']], $cardData);
        }

        // Créer l'utilisateur admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@clash.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password123'),
                'trophies' => 1000,
                'gold' => 5000,
                'gems' => 500,
                'level' => 5,
            ]
        );
        $admin->assignRole('admin');
        
        $this->initializeClashRoyaleUser($admin);

        // Créer l'utilisateur joueur de test
        $player = User::firstOrCreate(
            ['email' => 'player@clash.com'],
            [
                'name' => 'Player',
                'password' => Hash::make('password123'),
                'trophies' => 400,
                'gold' => 500,
                'gems' => 100,
                'level' => 3,
            ]
        );
        $player->assignRole('player');
        
        $this->initializeClashRoyaleUser($player);
    }
    
    private function initializeClashRoyaleUser(User $user): void
    {
        // Donner toutes les cartes au joueur avec quelques copies
        $allCards = Card::all();
        foreach ($allCards as $card) {
            $user->cards()->syncWithoutDetaching([
                $card->id => [
                    'level' => 1,
                    'copies_count' => rand(5, 20),
                ]
            ]);
        }
        
        // Créer un deck initial avec 8 cartes
        $initialDeckCards = $allCards->take(8)->pluck('id')->toArray();
        Deck::firstOrCreate(
            ['user_id' => $user->id],
            ['slots' => $initialDeckCards]
        );
        
        // Donner quelques coffres
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
