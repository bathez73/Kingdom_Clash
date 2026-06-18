<?php

namespace App\Notifications;

use App\Models\Battle;
use Illuminate\Notifications\Notification;

class AttackReceived extends Notification
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
        $message = $this->battle->result === 'attacker_won'
            ? "Le royaume {$this->battle->attacker->name} vous a attaqué et a gagné ! Vous avez perdu {$this->battle->gold_stolen} or."
            : "Le royaume {$this->battle->attacker->name} vous a attaqué mais a échoué !";

        return [
            'battle_id' => $this->battle->id,
            'attacker_name' => $this->battle->attacker->name,
            'result' => $this->battle->result,
            'gold_stolen' => $this->battle->gold_stolen,
            'message' => $message,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        $message = $this->battle->result === 'attacker_won'
            ? "Le royaume {$this->battle->attacker->name} vous a attaqué et a gagné ! Vous avez perdu {$this->battle->gold_stolen} or."
            : "Le royaume {$this->battle->attacker->name} vous a attaqué mais a échoué !";

        return [
            'battle_id' => $this->battle->id,
            'attacker_name' => $this->battle->attacker->name,
            'result' => $this->battle->result,
            'gold_stolen' => $this->battle->gold_stolen,
            'message' => $message,
        ];
    }
}
