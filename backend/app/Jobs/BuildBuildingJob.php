<?php

namespace App\Jobs;

use App\Models\Building;
use App\Services\KingdomService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BuildBuildingJob implements ShouldQueue
{
    use Queueable;

    public int $building_id;

    public function __construct(int $building_id)
    {
        $this->building_id = $building_id;
    }

    public function handle(KingdomService $kingdomService): void
    {
        $building = Building::find($this->building_id);

        if (!$building) {
            return;
        }

        $kingdomService->completeBuildingUpgrade($building);
    }
}
