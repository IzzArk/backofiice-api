<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $data = Notification::all();
        return response()->json($data);
    }

    public function getByUserId($user_id)
    {
        $data = Notification::where('user_id', $user_id)->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'No data found for this user'], 404);
        }

        return response()->json($data);
    }
}
