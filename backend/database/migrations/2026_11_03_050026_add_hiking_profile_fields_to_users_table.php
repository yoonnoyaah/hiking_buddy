<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('city')->nullable();
            $table->string('fitness_level')->default('Intermediate');
            $table->string('preferred_pace')->default('Steady');
            $table->string('role')->default('member');
            $table->string('status')->default('active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'city',
                'fitness_level',
                'preferred_pace',
                'role',
                'status',
            ]);
        });
    }
};