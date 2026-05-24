<?php

namespace App\Http\Controllers;

use App\Models\VehicleTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TrackerController extends Controller
{
    /**
     * POST /tracker/ping
     * Dipanggil oleh HP tersembunyi di dalam mobil untuk mengirim lokasi.
     * Tidak butuh auth — pakai device_token sebagai identifikasi.
     */
    public function ping(Request $request)
    {
        $request->merge([
            'device_token' => $request->device_token ?? $request->token
        ]);

        $request->validate([
            'device_token' => 'required|string',
            'lat'          => 'required|numeric|between:-90,90',
            'lng'          => 'required|numeric|between:-180,180',
            'car_id'       => 'nullable|string',
            'car_label'    => 'nullable|string',
            'speed'        => 'nullable|numeric',
            'accuracy'     => 'nullable|numeric',
            'battery'      => 'nullable|integer|between:0,100',
        ]);

        $tracker = VehicleTracker::firstOrNew(['device_token' => $request->device_token]);
        
        if ($request->has('car_id')) $tracker->car_id = $request->car_id;
        if ($request->has('car_label')) $tracker->car_label = $request->car_label;
        
        $tracker->lat = $request->lat;
        $tracker->lng = $request->lng;
        $tracker->speed = $request->speed ?? 0;
        $tracker->accuracy = $request->accuracy ?? 0;
        $tracker->battery = $request->battery;
        $tracker->is_active = true;
        $tracker->last_seen_at = now();
        $tracker->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Location updated',
            'tracker' => $tracker->only(['id', 'car_id', 'car_label', 'last_seen_at']),
        ]);
    }

    public function stop(Request $request)
    {
        $request->merge([
            'device_token' => $request->device_token ?? $request->token
        ]);

        $request->validate([
            'device_token' => 'required|string',
        ]);

        $tracker = VehicleTracker::where('device_token', $request->device_token)->first();
        if ($tracker) {
            // Set last_seen_at to 2 minutes ago to make it instantly offline (red)
            $tracker->last_seen_at = now()->subMinutes(2);
            // We DO NOT set is_active = false, so it stays on the map list!
            $tracker->save();
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * GET /tracker/locations
     * Diambil oleh admin panel untuk menampilkan semua tracker di peta.
     */
    public function locations()
    {
        $trackers = VehicleTracker::where('is_active', true)
            ->orderBy('last_seen_at', 'desc')
            ->get();

        // Mark offline jika tidak ping dalam 60 detik
        $trackers->each(function ($t) {
            $t->is_online_computed = $t->last_seen_at
                ? $t->last_seen_at->diffInSeconds(now()) < 60
                : false;
        });

        return response()->json([
            'status' => 'success',
            'data'   => $trackers,
        ]);
    }

    /**
     * GET /tracker/locations/{car_id}
     * Ambil lokasi tracker untuk mobil tertentu.
     */
    public function locationByCar($carId)
    {
        $tracker = VehicleTracker::where('car_id', $carId)
            ->where('is_active', true)
            ->latest('last_seen_at')
            ->first();

        if (!$tracker) {
            return response()->json(['status' => 'not_found', 'data' => null], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $tracker,
        ]);
    }

    /**
     * POST /tracker/generate-token
     * Generate device token baru untuk HP tersembunyi.
     * Dipanggil dari admin panel saat mau setup HP baru.
     */
    public function generateToken(Request $request)
    {
        $request->validate([
            'car_id'    => 'required|string',
            'car_label' => 'nullable|string',
        ]);

        $token = Str::random(32);

        // Buat atau update tracker untuk mobil ini
        $tracker = VehicleTracker::updateOrCreate(
            ['car_id' => $request->car_id],
            [
                'device_token' => $token,
                'car_label'    => $request->car_label ?? 'Mobil #' . $request->car_id,
                'is_active'    => true,
                'lat'          => null,
                'lng'          => null,
                'last_seen_at' => null,
            ]
        );

        $baseUrl = rtrim(config('app.url'), '/');

        return response()->json([
            'status'       => 'success',
            'device_token' => $token,
            'tracker_url'  => $baseUrl . '/tracker.html?token=' . $token . '&car_id=' . $request->car_id . '&label=' . urlencode($request->car_label ?? '') . '&autostart=1',
            'tracker'      => $tracker,
        ]);
    }

    /**
     * DELETE /tracker/{id}
     * Nonaktifkan tracker.
     */
    public function destroy($id)
    {
        $tracker = VehicleTracker::findOrFail($id);
        $tracker->update(['is_active' => false]);

        return response()->json([
            'status' => 'success', 
            'message' => 'Tracker deactivated']);
    }
}
