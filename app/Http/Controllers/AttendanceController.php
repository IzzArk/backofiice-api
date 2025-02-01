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

            $userId = $request->user_id;
            $currentTime = Carbon::now();
            $today = $currentTime->toDateString(); // Format: YYYY-MM-DD

            // Cek apakah user sudah absen masuk hari ini
            $attendance = Attendance::where('user_id', $userId)
                ->whereDate('checked_in_at', $today)
                ->first();

            if ($attendance) {
                // Jika sudah absen masuk, maka update sebagai absen pulang
                if (!$attendance->checked_out_at) {
                    $attendance->update([
                        'checked_out_at' => $currentTime,
                        'status' => 'hadir', // bisa disesuaikan dengan kondisi lain
                    ]);

                    return response()->json([
                        'message' => 'Absen pulang berhasil',
                        'data' => $attendance,
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'Anda sudah absen masuk dan pulang hari ini!',
                    ], 400);
                }
            } else {
                // Jika belum absen masuk, maka simpan absen masuk
                $status = ($currentTime->format('H:i') <= '08:00') ? 'on-time' : 'late';
                $imagePath = $request->file('image')->getClientOriginalName();

                $attendance = Attendance::create([
                    'user_id' => $userId,
                    'location' => $request->location,
                    'image_path' => $imagePath,
                    'checked_in_at' => $currentTime,
                    'status' => $status,
                ]);

                return response()->json([
                    'message' => 'Absen masuk berhasil',
                    'data' => $attendance,
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function index()
    {
        $attendances = Attendance::with('user:id,name')->get();

        // Format data menjadi array
        $formattedAttendances = $attendances->map(function ($attendance) {
            $checkedInAt = $attendance->checked_in_at ? Carbon::parse($attendance->checked_in_at)->setTimezone('Asia/Jakarta')->toISOString() : null;
            $checkedOutAt = $attendance->checked_out_at ? Carbon::parse($attendance->checked_out_at)->setTimezone('Asia/Jakarta')->toISOString() : null;

            return [
                'id' => $attendance->id,
                'user' => [
                    'id' => $attendance->user->id,
                    'name' => $attendance->user->name,
                ],
                'location' => $attendance->location,
                'image_path' => $attendance->image_path,
                'status' => $attendance->status,
                'checked_in_at' => $checkedInAt,
                'checked_out_at' => $checkedOutAt,
                'duration' => ($checkedInAt && $checkedOutAt)
                    ? Carbon::parse($attendance->checked_in_at)->diff(Carbon::parse($attendance->checked_out_at))->format('%h hours %i minutes')
                    : null, // Hitung durasi kerja
                'created_at' => Carbon::parse($attendance->created_at)->setTimezone('Asia/Jakarta')->toISOString(),
                'updated_at' => Carbon::parse($attendance->updated_at)->setTimezone('Asia/Jakarta')->toISOString()
            ];
        });

        return response()->json([
            'message' => 'Data absensi berhasil diambil',
            'data' => $formattedAttendances->toArray()
        ], 200);
    }



    public function getAttendanceByUserId($userId)
    {
        $attendances = Attendance::with('user:id,name')
            ->where('user_id', $userId)
            ->get();

        return response()->json($attendances, 200);
    }

    public function show($id)
    {
        $attendance = Attendance::with('user:id,name')->find($id);

        if (!$attendance) {
            return response()->json([
                'message' => 'Data absensi tidak ditemukan'
            ], 404);
        }

        $checkedInAt = $attendance->checked_in_at ? Carbon::parse($attendance->checked_in_at)->setTimezone('Asia/Jakarta')->toISOString() : null;
        $checkedOutAt = $attendance->checked_out_at ? Carbon::parse($attendance->checked_out_at)->setTimezone('Asia/Jakarta')->toISOString() : null;

        return response()->json([
            'message' => 'Data absensi berhasil diambil',
            'data' => [
                'id' => $attendance->id,
                'user' => [
                    'id' => $attendance->user->id,
                    'name' => $attendance->user->name,
                ],
                'location' => $attendance->location,
                'image_path' => $attendance->image_path,
                'status' => $attendance->status,
                'checked_in_at' => $checkedInAt,
                'checked_out_at' => $checkedOutAt,
                'duration' => ($checkedInAt && $checkedOutAt)
                    ? Carbon::parse($attendance->checked_in_at)->diff(Carbon::parse($attendance->checked_out_at))->format('%h hours %i minutes')
                    : null,
                'created_at' => Carbon::parse($attendance->created_at)->setTimezone('Asia/Jakarta')->toISOString(),
                'updated_at' => Carbon::parse($attendance->updated_at)->setTimezone('Asia/Jakarta')->toISOString()
            ]
        ], 200);
    }
}
