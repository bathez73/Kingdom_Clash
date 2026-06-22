<?php

namespace App\Console\Commands;

use App\Models\Building;
use App\Models\Kingdom;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProduceResources extends Command
{
    protected $signature = 'game:produce-resources';
    protected $description = 'Génère des ressources pour tous les royaumes en fonction du niveau des bâtiments';

    /**
     * Production par cycle de 15 minutes par niveau de bâtiment
     */
    private array $productionRates = [
        'gold_mine' => 30,   // or par niveau par cycle
        'sawmill'   => 20,   // bois par niveau par cycle
        'farm'      => 20,   // nourriture par niveau par cycle
        'barracks'  => 0,    // la caserne ne produit pas de ressources
    ];

    public function handle(): void
    {
        $kingdoms = Kingdom::whereNull('deleted_at')->get();
        $count = 0;

        foreach ($kingdoms as $kingdom) {
            $this->produceForKingdom($kingdom);
            $count++;
        }

        $this->info("Production effectuée pour {$count} royaumes.");
    }

    private function produceForKingdom(Kingdom $kingdom): void
    {
        $buildings = $kingdom->buildings()->whereNull('upgrade_ends_at')->get();

        $gold = 0;
        $wood = 0;
        $food = 0;

        foreach ($buildings as $building) {
            $rate = $this->productionRates[$building->type] ?? 0;
            $production = $rate * $building->level;

            if ($building->type === 'gold_mine') $gold += $production;
            if ($building->type === 'sawmill')   $wood += $production;
            if ($building->type === 'farm')       $food += $production;
        }

        if ($gold > 0 || $wood > 0 || $food > 0) {
            DB::transaction(function () use ($kingdom, $gold, $wood, $food) {
                $kingdom->update([
                    'gold' => DB::raw("gold + {$gold}"),
                    'wood' => DB::raw("wood + {$wood}"),
                    'food' => DB::raw("food + {$food}"),
                    'last_resource_production_at' => now(),
                ]);
            });
        }
    }
}
