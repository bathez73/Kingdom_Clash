<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class TrainingCompleted extends Notification
{
    public string $soldier_type;
    public int $quantity;

    public function __construct(string $soldier_type, int $quantity)
    {
        $this->soldier_type = $soldier_type;
        $this->quantity = $quantity;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'soldier_type' => $this->soldier_type,
            'quantity' => $this->quantity,
            'message' => "Vous avez maintenant {$this->quantity} {$this->soldier_type}(s)",
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return [
            'soldier_type' => $this->soldier_type,
            'quantity' => $this->quantity,
            'message' => "Vous avez maintenant {$this->quantity} {$this->soldier_type}(s)",
        ];
    }
}
