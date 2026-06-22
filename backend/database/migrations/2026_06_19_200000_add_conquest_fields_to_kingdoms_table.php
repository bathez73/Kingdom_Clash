<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kingdoms', function (Blueprint $table) {
            // Statut du royaume : 'player' = appartient à un joueur, 'enemy' = NPC conquérable
            $table->enum('status', ['player', 'enemy'])->default('player')->after('food');
            // Puissance de défense fixe pour les royaumes NPC
            $table->unsignedInteger('defense_power')->default(100)->after('status');
            // Capacité d'or maximale (butin disponible)
            $table->unsignedBigInteger('gold_capacity')->default(5000)->after('defense_power');
            // Niveau de difficulté (1-5) des vagues ennemies
            $table->unsignedTinyInteger('difficulty')->default(1)->after('gold_capacity');
        });
    }

    public function down(): void
    {
        Schema::table('kingdoms', function (Blueprint $table) {
            $table->dropColumn(['status', 'defense_power', 'gold_capacity', 'difficulty']);
        });
    }
};
