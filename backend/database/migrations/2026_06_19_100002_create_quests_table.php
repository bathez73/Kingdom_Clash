<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // train_soldiers, win_battle, upgrade_building, collect_resources
            $table->string('label');
            $table->integer('target');        // objectif à atteindre
            $table->integer('progress')->default(0);
            $table->boolean('completed')->default(false);
            $table->boolean('claimed')->default(false);
            $table->json('reward');            // { gold, wood, food, xp }
            $table->date('date');              // date de la quête (reset quotidien)
            $table->timestamps();

            $table->unique(['user_id', 'type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quests');
    }
};
