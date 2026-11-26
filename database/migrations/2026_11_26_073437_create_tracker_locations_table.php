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
        Schema::create('tracker_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracker_device_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 11, 7)->nullable();
            $table->decimal('altitude_m', 10, 2)->nullable();
            $table->decimal('speed_kmh', 8, 2)->nullable();
            $table->unsignedTinyInteger('satellites')->nullable();
            $table->decimal('hdop', 5, 2)->nullable();
            $table->boolean('gps_fix');
            $table->string('source');
            $table->timestamp('recorded_at');
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index(['tracker_device_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracker_locations');
    }
};
