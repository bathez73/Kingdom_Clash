<?php

namespace App\Console\Commands;

use App\Models\Kingdom;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetDailyChests extends Command
{
    protected $signature = 'app:reset-daily-chests';
    protected $description = 'Réinitialise les coffres quotidiens pour tous les royaumes';

    public function handle(): int
    {
        DB::transaction(function () {
            $kingdoms = Kingdom::all();
            foreach ($kingdoms as $kingdom) {
                \Cache::forget("daily_chest_claimed_{$kingdom->id}");
            }
        });

        $this->info('Les coffres quotidiens ont été réinitialisés.');
        return 0;
    }
}
