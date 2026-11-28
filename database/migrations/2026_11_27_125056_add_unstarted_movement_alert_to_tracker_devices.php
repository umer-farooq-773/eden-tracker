<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tracker_devices', function (Blueprint $table): void {
            $table->timestamp('moving_without_trip_alerted_at')->nullable();
            $table->boolean('is_trip_tracking')->default(false)->change();
        });

        DB::table('tracker_devices')->update([
            'is_trip_tracking' => false,
            'moving_without_trip_alerted_at' => null,
        ]);

        DB::table('tracker_devices')
            ->whereIn('id', DB::table('vehicle_trips')
                ->select('tracker_device_id')
                ->whereNull('ended_at'))
            ->update(['is_trip_tracking' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracker_devices', function (Blueprint $table): void {
            $table->dropColumn('moving_without_trip_alerted_at');
            $table->boolean('is_trip_tracking')->default(true)->change();
        });
    }
};
