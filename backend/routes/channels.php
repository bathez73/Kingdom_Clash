<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal privé pour les notifications d'un royaume
Broadcast::channel('kingdom.{kingdomId}', function ($user, $kingdomId) {
    $kingdom = $user->kingdom;
    return $kingdom && (int) $kingdom->id === (int) $kingdomId;
});

// Canal public pour le chat global
Broadcast::channel('global-chat', function ($user) {
    return $user;
});

// Canal public pour le classement
Broadcast::channel('global-rankings', function ($user) {
    return $user;
});

