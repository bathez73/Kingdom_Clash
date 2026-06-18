<?php

namespace App\Events;

use App\Models\Battle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BattleOccurred implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public Battle $battle;

    public function __construct(Battle $battle)
    {
        $this->battle = $battle;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("kingdom.{$this->battle->attacker_id}"),
            new PrivateChannel("kingdom.{$this->battle->defender_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'battle.occurred';
    }

    public function broadcastWith(): array
    {
        return [
            'battle_id' => $this->battle->id,
            'attacker_id' => $this->battle->attacker_id,
            'defender_id' => $this->battle->defender_id,
            'attacker_name' => $this->battle->attacker->name,
            'defender_name' => $this->battle->defender->name,
            'result' => $this->battle->result,
            'gold_stolen' => $this->battle->gold_stolen,
            'attacker_losses' => $this->battle->attacker_losses,
            'defender_losses' => $this->battle->defender_losses,
        ];
    }
}
