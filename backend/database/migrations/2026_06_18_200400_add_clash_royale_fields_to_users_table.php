<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('trophies')->default(0);
            $table->integer('gold')->default(500);
            $table->integer('gems')->default(100);
            $table->integer('level')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['trophies', 'gold', 'gems', 'level']);
        });
    }
};
