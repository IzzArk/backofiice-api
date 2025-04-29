<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Validation\ValidationException;


class AttendanceController extends Controller
{

    public function store(Request $request)
    {
        try {
            // Validasi request
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'location' => 'required|string',
                'image' => 'required|image|max:2048', // Maksimal 2 MB
            ]);

            $userId = $request->user_id;
            $currentTime = Carbon::now();
            $today = $currentTime->toDateString(); // Format: YYYY-MM-DD

            // Cek apakah user sudah absen masuk hari ini
            $attendance = Attendance::where('user_id', $userId)
                ->whereDate('checked_in_at', $today)
                ->first();

            if ($attendance) {
                // Jika sudah absen masuk, update sebagai absen pulang
                if (!$attendance->checked_out_at) {
                    $checkoutTime = $currentTime->format('H:i');
                    $status_checkout = ($checkoutTime <= '17:00') ? 'on-time' : 'overtime';

                    // Hitung durasi kerja
                    $checkinTime = Carbon::parse($attendance->checked_in_at);
                    $workDuration = $checkinTime->diffInHours($currentTime);

                    // Status default
                    $status = null;
                    if ($workDuration > 9) {
                        $status = 'lembur'; // Jika kerja lebih dari 9 jam, update status
                    }

                    // Upload gambar check-out
                    $image = $request->file('image');
                    $imageName = time() . '_' . $image->getClientOriginalName();
                    $path = Storage::disk('google')->putFileAs('', $image, $imageName);
                    $imageCheckOutUrl = Storage::disk('google')->url($path);

                    // Update data absen
                    $attendance->update([
                        'image_check_out' => $imageCheckOutUrl,
                        'checked_out_at' => $currentTime,
                        'status_check_out' => $status_checkout,
                        'status' => $status, // Update status jika lembur
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
                // Jika belum absen masuk, simpan absen masuk
                $checkinTime = $currentTime->format('H:i');
                $status_checkin = ($checkinTime <= '08:00') ? 'on-time' : 'late';

                // Upload gambar ke Google Drive
                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    $imageName = time() . '_' . $image->getClientOriginalName();
                    $path = Storage::disk('google')->putFileAs('', $image, $imageName);
                    $imageUrl = Storage::disk('google')->url($path); // Dapatkan URL file

                    // Simpan data absensi
                    $attendance = Attendance::create([
                        'user_id' => $userId,
                        'location' => $request->location,
                        'image_path' => $imageUrl, // Simpan URL file
                        'checked_in_at' => $currentTime,
                        'status_check_in' => $status_checkin,
                    ]);

                    return response()->json([
                        'message' => 'Absen masuk berhasil',
                        'data' => $attendance,
                    ], 201);
                } else {
                    return response()->json([
                        'message' => 'Gambar tidak ditemukan, upload gagal!',
                    ], 400);
                }
            }
        } catch (ValidationException $e) {
            $errors = $e->validator->errors();

            if ($errors->has('image') && str_contains($errors->first('image'), 'greater than')) {
                return response()->json([
                    'message' => 'Ukuran gambar terlalu besar.',
                ], 422);
            }

            return response()->json([
                'message' => $errors->first(),
            ], 422);
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
                'image_check_out' => $attendance->image_check_out,
                'status' => $attendance->status,
                'status_check_in' => $attendance->status_check_in,
                'status_check_out' => $attendance->status_check_out,
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



    public function getAttendanceByUserId($userId, Request $request)
    {
        $query = Attendance::with('user:id,name')->where('user_id', $userId);

        // Ambil parameter filter dari query string
        $filter = $request->query('filter');

        if ($filter === 'daily') {
            $query->whereDate('checked_in_at', Carbon::now()->toDateString());
        } elseif ($filter === 'weekly') {
            $query->whereBetween('checked_in_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($filter === 'monthly') {
            $query->whereMonth('checked_in_at', Carbon::now()->month)
                ->whereYear('checked_in_at', Carbon::now()->year);
        }


        $attendances = $query->get()->map(function ($attendance) {
            return [
                'id' => $attendance->id,
                'user_id' => $attendance->user_id,
                'image_path' => $attendance->image_path,
                'image_check_out' => $attendance->image_check_out,
                'location' => $attendance->location,
                'status' => $attendance->status,
                'status_check_in' => $attendance->status_check_in,
                'status_check_out' => $attendance->status_check_out,
                'checked_in_at' => $attendance->checked_in_at
                    ? Carbon::parse($attendance->checked_in_at)->setTimezone('Asia/Jakarta')->toISOString()
                    : null,
                'checked_out_at' => $attendance->checked_out_at
                    ? Carbon::parse($attendance->checked_out_at)->setTimezone('Asia/Jakarta')->toISOString()
                    : null,
                'created_at' => Carbon::parse($attendance->created_at)->setTimezone('Asia/Jakarta')->toISOString(),
                'updated_at' => Carbon::parse($attendance->updated_at)->setTimezone('Asia/Jakarta')->toISOString(),
                'user' => [
                    'id' => $attendance->user->id,
                    'name' => $attendance->user->name,
                ],
            ];
        });

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
                'image_check_out' => $attendance->image_check_out,
                'status' => $attendance->status,
                'status_check_in' => $attendance->status_check_in,
                'status_check_out' => $attendance->status_check_out,
                'checked_in_at' => $checkedInAt,
                'checked_out_at' => $checkedOutAt,
                'duration' => ($checkedInAt && $checkedOutAt)
                    ? Carbon::parse($attendance->checked_in_at)->diff(Carbon::parse($attendance->checked_out_at))->format('%h hours %i minutes')
                    : null, // Hitung durasi kerja
                'created_at' => Carbon::parse($attendance->created_at)->setTimezone('Asia/Jakarta')->toISOString(),
                'updated_at' => Carbon::parse($attendance->updated_at)->setTimezone('Asia/Jakarta')->toISOString()
            ]
        ], 200);
    }
}
