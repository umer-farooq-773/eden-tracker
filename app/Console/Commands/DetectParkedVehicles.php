<?php

namespace App\Console\Commands;

use App\Services\TrackerActivityService;
use Illuminate\Console\Command;

class DetectParkedVehicles extends Command
{
    protected $signature = 'app:detect-parked-vehicles';

    protected $description = 'Create parking records and notifications for stale tracker devices.';

    public function handle(TrackerActivityService $activityService): int
    {
        $createdCount = $activityService->detectParkedVehicles();
        $this->info("Created {$createdCount} parking record(s).");

        return self::SUCCESS;
    }
}
