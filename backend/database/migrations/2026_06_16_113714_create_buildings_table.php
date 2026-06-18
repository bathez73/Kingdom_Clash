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
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kingdom_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['gold_mine', 'sawmill', 'farm', 'barracks']);
            $table->integer('level')->default(1);
            $table->timestamp('upgrade_ends_at')->nullable(); // Gestion du temps de construction (Jobs)
            $table->softDeletes(); // Pour la corbeille
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};