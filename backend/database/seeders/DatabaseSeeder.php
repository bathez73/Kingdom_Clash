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
        // ── Rôles ─────────────────────────────────────────────────────────────
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'moderator']);
        Role::firstOrCreate(['name' => 'player']);

        // ── Cartes de combat (12 cartes avec stats et image_path) ─────────────
        $cards = [
            [
                'name'        => 'Chevalier Bleu',
                'type'        => 'common',
                'base_damage' => 50,
                'base_hp'     => 300,
                'elixir_cost' => 3,
                'icon'        => '⚔️',
                'image_path'  => 'cards/chevalier_bleu.png',
            ],
            [
                'name'        => 'Archer des Bois',
                'type'        => 'common',
                'base_damage' => 40,
                'base_hp'     => 120,
                'elixir_cost' => 3,
                'icon'        => '🏹',
                'image_path'  => 'cards/archer_bois.png',
            ],
            [
                'name'        => 'Gobelin Rapide',
                'type'        => 'common',
                'base_damage' => 60,
                'base_hp'     => 100,
                'elixir_cost' => 2,
                'icon'        => '👺',
                'image_path'  => 'cards/gobelin_rapide.png',
            ],
            [
                'name'        => 'Lancier Éclair',
                'type'        => 'common',
                'base_damage' => 45,
                'base_hp'     => 180,
                'elixir_cost' => 3,
                'icon'        => '🗡️',
                'image_path'  => 'cards/lancier_eclair.png',
            ],
            [
                'name'        => 'Géant Armure',
                'type'        => 'rare',
                'base_damage' => 80,
                'base_hp'     => 1200,
                'elixir_cost' => 5,
                'icon'        => '👹',
                'image_path'  => 'cards/geant_armure.png',
            ],
            [
                'name'        => 'Mage de Feu',
                'type'        => 'rare',
                'base_damage' => 120,
                'base_hp'     => 200,
                'elixir_cost' => 5,
                'icon'        => '🧙',
                'image_path'  => 'cards/mage_feu.png',
            ],
            [
                'name'        => 'Golem de Pierre',
                'type'        => 'rare',
                'base_damage' => 65,
                'base_hp'     => 900,
                'elixir_cost' => 4,
                'icon'        => '🪨',
                'image_path'  => 'cards/golem_pierre.png',
            ],
            [
                'name'        => 'Dragon Bébé',
                'type'        => 'epic',
                'base_damage' => 100,
                'base_hp'     => 500,
                'elixir_cost' => 4,
                'icon'        => '🐉',
                'image_path'  => 'cards/dragon_bebe.png',
            ],
            [
                'name'        => 'Barbare Hache',
                'type'        => 'epic',
                'base_damage' => 250,
                'base_hp'     => 1500,
                'elixir_cost' => 7,
                'icon'        => '🪓',
                'image_path'  => 'cards/barbare_hache.png',
            ],
            [
                'name'        => 'Sorcier des Ombres',
                'type'        => 'epic',
                'base_damage' => 180,
                'base_hp'     => 350,
                'elixir_cost' => 6,
                'icon'        => '🌑',
                'image_path'  => 'cards/sorcier_ombres.png',
            ],
            [
                'name'        => 'Sparky Légendaire',
                'type'        => 'legendary',
                'base_damage' => 500,
                'base_hp'     => 800,
                'elixir_cost' => 6,
                'icon'        => '⚡',
                'image_path'  => 'cards/sparky_legendaire.png',
            ],
            [
                'name'        => 'Nécromancien Céleste',
                'type'        => 'legendary',
                'base_damage' => 380,
                'base_hp'     => 650,
                'elixir_cost' => 7,
                'icon'        => '💀',
                'image_path'  => 'cards/necromancien_celeste.png',
            ],
        ];

        foreach ($cards as $cardData) {
            Card::firstOrCreate(['name' => $cardData['name']], $cardData);
        }

        // ── Utilisateur Admin ─────────────────────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@clash.com'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('password123'),
                'trophies' => 1000,
                'gold'     => 5000,
                'gems'     => 500,
                'level'    => 5,
                'xp'       => 0,
            ]
        );
        $admin->assignRole('admin');
        $this->initializePlayer($admin);

        // ── Utilisateur Joueur de test ────────────────────────────────────────
        $player = User::firstOrCreate(
            ['email' => 'player@clash.com'],
            [
                'name'     => 'Player',
                'password' => Hash::make('password123'),
                'trophies' => 400,
                'gold'     => 500,
                'gems'     => 100,
                'level'    => 3,
                'xp'       => 0,
            ]
        );
        $player->assignRole('player');
        $this->initializePlayer($player);

        // ── Royaumes NPC conquérables (7 royaumes, difficulté 1→5) ────────────
        // Chaque NPC dispose d'un user fantôme dédié pour satisfaire la FK user_id.
        // Les stats de combat des vagues ennemies sont dérivées de `difficulty`
        // directement dans KingdomWarArena.vue : hp = (60 + diff*20) * wave_mult.
        $npcKingdoms = [
            // ── Difficulté 1 : Initiation ──────────────────────────────────────
            [
                'npc_email'     => 'npc_gobelin@clash.com',
                'npc_name'      => 'Seigneur Gobelin',
                'kingdom_name'  => 'Cavernes des Gobelins',
                'level'         => 2,
                'gold'          => 1800,
                'wood'          => 600,
                'food'          => 600,
                'status'        => 'enemy',
                'defense_power' => 120,
                'gold_capacity' => 3000,
                'difficulty'    => 1,
            ],
            // ── Difficulté 2 : Guerrier ────────────────────────────────────────
            [
                'npc_email'     => 'npc_barbare@clash.com',
                'npc_name'      => 'Roi Barbare',
                'kingdom_name'  => 'Forteresse Barbare',
                'level'         => 4,
                'gold'          => 4500,
                'wood'          => 1200,
                'food'          => 900,
                'status'        => 'enemy',
                'defense_power' => 280,
                'gold_capacity' => 6000,
                'difficulty'    => 2,
            ],
            // ── Difficulté 3 : Conquérant ──────────────────────────────────────
            [
                'npc_email'     => 'npc_dragon@clash.com',
                'npc_name'      => 'Seigneur Dragon',
                'kingdom_name'  => 'Repaire du Dragon',
                'level'         => 6,
                'gold'          => 9000,
                'wood'          => 2500,
                'food'          => 2000,
                'status'        => 'enemy',
                'defense_power' => 520,
                'gold_capacity' => 12000,
                'difficulty'    => 3,
            ],
            // ── Difficulté 3 bis : Maître de Guerre ───────────────────────────
            [
                'npc_email'     => 'npc_hydra@clash.com',
                'npc_name'      => 'Seigneur Hydra',
                'kingdom_name'  => 'Marécages de l\'Hydra',
                'level'         => 7,
                'gold'          => 13000,
                'wood'          => 3200,
                'food'          => 2800,
                'status'        => 'enemy',
                'defense_power' => 680,
                'gold_capacity' => 16000,
                'difficulty'    => 3,
            ],
            // ── Difficulté 4 : Légende ─────────────────────────────────────────
            [
                'npc_email'     => 'npc_necro@clash.com',
                'npc_name'      => 'Nécromancien Maudit',
                'kingdom_name'  => 'Citadelle des Morts',
                'level'         => 8,
                'gold'          => 16000,
                'wood'          => 4000,
                'food'          => 3500,
                'status'        => 'enemy',
                'defense_power' => 850,
                'gold_capacity' => 20000,
                'difficulty'    => 4,
            ],
            // ── Difficulté 4 bis : Avant-Garde ────────────────────────────────
            [
                'npc_email'     => 'npc_titan@clash.com',
                'npc_name'      => 'Titan de Fer',
                'kingdom_name'  => 'Forteresse de Fer Noir',
                'level'         => 9,
                'gold'          => 23000,
                'wood'          => 6000,
                'food'          => 5200,
                'status'        => 'enemy',
                'defense_power' => 1200,
                'gold_capacity' => 30000,
                'difficulty'    => 4,
            ],
            // ── Difficulté 5 : Ultime ──────────────────────────────────────────
            [
                'npc_email'     => 'npc_shadow@clash.com',
                'npc_name'      => 'Shadow Lord',
                'kingdom_name'  => 'Trône des Ombres',
                'level'         => 10,
                'gold'          => 30000,
                'wood'          => 8000,
                'food'          => 7000,
                'status'        => 'enemy',
                'defense_power' => 1500,
                'gold_capacity' => 40000,
                'difficulty'    => 5,
            ],
        ];

        foreach ($npcKingdoms as $data) {
            $npc = User::firstOrCreate(
                ['email' => $data['npc_email']],
                [
                    'name'     => $data['npc_name'],
                    'password' => Hash::make('npc_' . sha1($data['npc_email'])),
                    'trophies' => $data['level'] * 100,
                    'gold'     => 0,
                    'gems'     => 0,
                    'level'    => $data['level'],
                    'xp'       => 0,
                ]
            );

            if (!$npc->hasRole('player')) {
                $npc->assignRole('player');
            }

            Kingdom::firstOrCreate(
                ['user_id' => $npc->id],
                [
                    'name'          => $data['kingdom_name'],
                    'level'         => $data['level'],
                    'gold'          => $data['gold'],
                    'wood'          => $data['wood'],
                    'food'          => $data['food'],
                    'status'        => $data['status'],
                    'defense_power' => $data['defense_power'],
                    'gold_capacity' => $data['gold_capacity'],
                    'difficulty'    => $data['difficulty'],
                ]
            );
        }
    }

    private function initializePlayer(User $user): void
    {
        $allCards = Card::all();

        foreach ($allCards as $card) {
            $user->cards()->syncWithoutDetaching([
                $card->id => [
                    'level'        => 1,
                    'copies_count' => rand(5, 20),
                ]
            ]);
        }

        $initialDeckCards = $allCards->take(8)->pluck('id')->toArray();
        Deck::firstOrCreate(
            ['user_id' => $user->id],
            ['slots' => $initialDeckCards]
        );

        if (!$user->kingdom) {
            $service  = app(KingdomService::class);
            $kingdom  = $service->createKingdom($user->id, "Royaume de {$user->name}");
            $service->initializeBuildings($kingdom);
        }

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
