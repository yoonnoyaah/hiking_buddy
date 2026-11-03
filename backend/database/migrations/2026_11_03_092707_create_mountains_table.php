
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mountains', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('region');

            $table->string('difficulty')->default('Moderate');

            $table->string('distance')->nullable();
            $table->string('duration')->nullable();
            $table->string('pace')->nullable();

            $table->text('description')->nullable();
            $table->text('image')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['name', 'region']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mountains');
    }
};
