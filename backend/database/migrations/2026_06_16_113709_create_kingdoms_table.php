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
        Schema::create('kingdoms', function (Blueprint $table) {
            $table->id();
            // Liaison avec la table users (si un utilisateur est supprimé, son royaume aussi)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Infos du Royaume
            $table->string('name');
            $table->integer('level')->default(1);
            
            // Ressources de départ
            $table->unsignedBigInteger('gold')->default(1000);
            $table->unsignedBigInteger('wood')->default(500);
            $table->unsignedBigInteger('food')->default(500);
            
            $table->softDeletes(); // Pour la gestion de la corbeille (Soft Delete)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kingdoms');
    }
};