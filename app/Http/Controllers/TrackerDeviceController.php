<?php

namespace App\Http\Controllers;

use App\Models\TrackerDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TrackerDeviceController extends Controller
{
    public function index(): View
    {
        $devices = TrackerDevice::query()
            ->with('latestLocation')
            ->orderBy('name')
            ->paginate(20);

        return view('auth.pages.tracker_devices.index', compact('devices'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:tracker_devices,device_id'],
            'name' => ['required', 'string', 'max:120'],
        ]);

        $token = Str::random(64);
        TrackerDevice::query()->create([
            'device_id' => $data['device_id'],
            'name' => $data['name'],
            'token_hash' => Hash::make($token),
            'is_active' => true,
        ]);

        return redirect()->route('tracker_device.index')
            ->with('success', 'Tracker registered. Copy the token now; it will not be shown again.')
            ->with('issued_token', $token);
    }

    public function update(Request $request, TrackerDevice $trackerDevice): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $trackerDevice->update(['name' => $data['name']]);

        return redirect()->route('tracker_device.index')->with('success', 'Tracker name updated.');
    }

    public function updateStatus(TrackerDevice $trackerDevice): RedirectResponse
    {
        $trackerDevice->update(['is_active' => !$trackerDevice->is_active]);

        return redirect()->route('tracker_device.index')->with(
            'success',
            $trackerDevice->is_active ? 'Tracker activated.' : 'Tracker deactivated.'
        );
    }

    public function rotateToken(TrackerDevice $trackerDevice): RedirectResponse
    {
        $token = Str::random(64);
        $trackerDevice->forceFill(['token_hash' => Hash::make($token)])->save();

        return redirect()->route('tracker_device.index')
            ->with('success', 'Token rotated. The previous token is now invalid; copy the new token now.')
            ->with('issued_token', $token);
    }

    public function destroy(TrackerDevice $trackerDevice): RedirectResponse
    {
        if ($trackerDevice->locations()->exists()) {
            return redirect()->route('tracker_device.index')
                ->with('error', 'This tracker has location history. Deactivate it to preserve its records.');
        }

        $trackerDevice->delete();

        return redirect()->route('tracker_device.index')->with('success', 'Tracker deleted.');
    }
}
