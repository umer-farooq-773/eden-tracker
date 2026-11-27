<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_devices', function (Blueprint $table): void {
            $table->boolean('is_trip_tracking')->default(true);
        });

        Schema::create('vehicle_parked', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tracker_device_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedBigInteger('last_location_id')->nullable();
            $table->json('location_data');
            $table->timestamps();
            $table->index(['tracker_device_id', 'ended_at']);
        });

        Schema::create('vehicle_trips', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tracker_device_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->json('location_points');
            $table->timestamps();
            $table->index(['tracker_device_id', 'ended_at']);
        });

        Schema::create('tracker_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tracker_device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['read_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_notifications');
        Schema::dropIfExists('vehicle_trips');
        Schema::dropIfExists('vehicle_parked');
        Schema::table('tracker_devices', function (Blueprint $table): void {
            $table->dropColumn('is_trip_tracking');
        });
    }
};
