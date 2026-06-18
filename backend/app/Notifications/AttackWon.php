<?php

namespace App\Notifications;

use App\Models\Battle;
use Illuminate\Notifications\Notification;

class AttackWon extends Notification
{
    public Battle $battle;

    public function __construct(Battle $battle)
    {
        $this->battle = $battle;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'battle_id' => $this->battle->id,
            'defender_name' => $this->battle->defender->name,
            'gold_stolen' => $this->battle->gold_stolen,
            'message' => "Vous avez attaqué {$this->battle->defender->name} et avez remporté {$this->battle->gold_stolen} or !",
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return [
            'battle_id' => $this->battle->id,
            'defender_name' => $this->battle->defender->name,
            'gold_stolen' => $this->battle->gold_stolen,
            'message' => "Vous avez attaqué {$this->battle->defender->name} et avez remporté {$this->battle->gold_stolen} or !",
        ];
    }
}
