<?php

namespace App\Notifications;

use App\Models\Building;
use Illuminate\Notifications\Notification;

class ConstructionCompleted extends Notification
{
    public Building $building;

    public function __construct(Building $building)
    {
        $this->building = $building;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'building_id' => $this->building->id,
            'building_type' => $this->building->type,
            'level' => $this->building->level,
            'message' => "Votre {$this->building->type} a atteint le niveau {$this->building->level}",
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return [
            'building_id' => $this->building->id,
            'building_type' => $this->building->type,
            'level' => $this->building->level,
            'message' => "Votre {$this->building->type} a atteint le niveau {$this->building->level}",
        ];
    }
}
