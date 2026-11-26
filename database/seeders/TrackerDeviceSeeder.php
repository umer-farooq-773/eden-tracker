<?php

namespace Database\Seeders;

use App\Models\TrackerDevice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TrackerDeviceSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('TrackerDeviceSeeder is only allowed in local or testing environments.');
        }

        $token = (string) env('TRACKER_DEMO_TOKEN', 'local-only-eden-tracker-demo-token-change-me');
        if (strlen($token) < 32) {
            throw new \RuntimeException('TRACKER_DEMO_TOKEN must contain at least 32 characters.');
        }

        TrackerDevice::query()->updateOrCreate(
            ['device_id' => 'EDEN-TRACKER-001'],
            [
                'name' => 'Eden Demo Tracker',
                'token_hash' => Hash::make($token),
                'is_active' => true,
            ]
        );

        $this->command?->info('Demo tracker EDEN-TRACKER-001 is ready.');
        $this->command?->line("Demo bearer token: {$token}");
        $this->command?->warn('Use only for local/testing API checks. Do not deploy this token.');
    }
}
