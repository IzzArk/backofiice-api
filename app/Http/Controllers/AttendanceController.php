<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\User;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validasi request
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'location' => 'required|string',
                'image' => 'required|image|max:2048', // maksimal 2 MB
            ]);

            $currentTime = Carbon::now();
            $status = '';

            // Tentukan status absensi
            if ($currentTime->format('H:i') <= '08:00') {
                $status = 'on-time';
            } elseif ($currentTime->format('H:i') > '08:00' && $currentTime->format('H:i') <= '17:00') {
                $status = 'late';
            } else {
                $status = 'absent';
            }

            // Simpan gambar ke storage
            $imagePath = $request->file('image')->store('attendance_images', 'public');

            // Simpan data ke database
            $attendance = Attendance::create([
                'user_id' => $request->user_id,
                'location' => $request->location,
                'image_path' => $imagePath,
                'status' => $status,
                'checked_in_at' => $currentTime,
            ]);

            return response()->json([
                'message' => 'Attendance recorded successfully',
                'data' => $attendance,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function index()
    {
        $attendances = Attendance::with('user:id,name')->get();

        // Format data menjadi array
        $formattedAttendances = $attendances->map(function ($attendance) {
            return [
                'id' => $attendance->id,
                'user' => [
                    'id' => $attendance->user->id,
                    'name' => $attendance->user->name,
                ],
                'location' => $attendance->location,
                'image_path' => $attendance->image_path,
                'status' => $attendance->status,
                'checked_in_at' => $attendance->checked_in_at,
                'created_at' => $attendance->created_at,
                'updated_at' => $attendance->updated_at,
            ];
        });

        // Langsung kembalikan array tanpa pembungkus 'data'
        return response()->json($formattedAttendances->toArray(), 200);
    }
}
