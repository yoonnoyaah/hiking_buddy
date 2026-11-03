
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_trips', function (Blueprint $table) {
            $table->id();

            // Member who created the trip
            $table->foreignId('organizer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Selected mountain (can be empty for custom mountains)
            $table->foreignId('mountain_id')
                ->nullable()
                ->constrained('mountains')
                ->nullOnDelete();

            $table->string('mountain_name');

            // Trip information
            $table->string('title');
            $table->string('region');
            $table->string('difficulty')->default('Moderate');

            $table->date('trip_date');
            $table->time('trip_time');

            $table->string('pace')->nullable();
            $table->string('fitness')->nullable();

            // Maximum number of members allowed
            $table->unsignedInteger('spots')->default(5);

            $table->string('vehicle')->nullable();
            $table->string('meeting')->nullable();

            $table->string('telegram')->nullable();
            $table->string('phone')->nullable();
            $table->string('emergency')->nullable();

            $table->text('description')->nullable();

            $table->string('status')->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_trips');
    }
};
