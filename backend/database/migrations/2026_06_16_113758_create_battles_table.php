<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('battles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attacker_id')->constrained('kingdoms')->onDelete('cascade');
            $table->foreignId('defender_id')->constrained('kingdoms')->onDelete('cascade');
            $table->enum('result', ['attacker_won', 'defender_won', 'pending']);
            $table->unsignedBigInteger('gold_stolen')->default(0);
            $table->json('attacker_losses'); // Exemple stocké : {"swordsman": 5, "archer": 2}
            $table->json('defender_losses'); // Exemple stocké : {"swordsman": 10, "cavalry": 1}
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('battles');
    }
};