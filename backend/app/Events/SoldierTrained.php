<?php

namespace App\Events;

use App\Models\Soldier;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SoldierTrained implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public Soldier $soldier;

    public function __construct(Soldier $soldier)
    {
        $this->soldier = $soldier;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("kingdom.{$this->soldier->kingdom_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'soldier.trained';
    }

    public function broadcastWith(): array
    {
        return [
            'soldier_id' => $this->soldier->id,
            'soldier_type' => $this->soldier->type,
            'quantity' => $this->soldier->quantity,
            'kingdom_id' => $this->soldier->kingdom_id,
            'message' => "Vous avez maintenant {$this->soldier->quantity} {$this->soldier->type}(s)",
        ];
    }
}
