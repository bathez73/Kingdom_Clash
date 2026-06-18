<?php

namespace App\Jobs;

use App\Services\SoldierService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TrainSoldierJob implements ShouldQueue
{
    use Queueable;

    public int $kingdom_id;
    public string $soldier_type;

    public function __construct(int $kingdom_id, string $soldier_type)
    {
        $this->kingdom_id = $kingdom_id;
        $this->soldier_type = $soldier_type;
    }

    public function handle(SoldierService $soldierService): void
    {
        $soldierService->completeSoldierTraining($this->kingdom_id, $this->soldier_type);
    }
}
