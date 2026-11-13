<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reporter_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // community_post or group_trip
            $table->string('reportable_type');

            // ID of the reported community post or group trip.
            $table->unsignedBigInteger('reportable_id');

            $table->text('reason');

            $table->enum('status', [
                'pending',
                'resolved',
                'dismissed'
            ])->default('pending');

            $table->text('admin_note')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(
                ['reportable_type', 'reportable_id'],
                'reports_reportable_index'
            );

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};