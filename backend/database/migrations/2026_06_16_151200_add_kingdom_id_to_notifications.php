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
        Schema::table('notifications', function (Blueprint $table) {
            // Ajouter kingdom_id si la colonne n'existe pas
            if (!Schema::hasColumn('notifications', 'kingdom_id')) {
                $table->unsignedBigInteger('kingdom_id')->nullable()->after('id');
                $table->foreign('kingdom_id')->references('id')->on('kingdoms')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'kingdom_id')) {
                $table->dropForeign(['kingdom_id']);
                $table->dropColumn('kingdom_id');
            }
        });
    }
};
