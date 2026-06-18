<?php

namespace App\Events;

use App\Models\Building;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BuildingUpgraded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public Building $building;

    public function __construct(Building $building)
    {
        $this->building = $building;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("kingdom.{$this->building->kingdom_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'building.upgraded';
    }

    public function broadcastWith(): array
    {
        return [
            'building_id' => $this->building->id,
            'building_type' => $this->building->type,
            'level' => $this->building->level,
            'kingdom_id' => $this->building->kingdom_id,
            'message' => "Votre {$this->building->type} est maintenant niveau {$this->building->level}",
        ];
    }
}
