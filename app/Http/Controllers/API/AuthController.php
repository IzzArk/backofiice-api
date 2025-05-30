<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Handle user login.
     */

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                // Ambil pesan pertama dari error validasi
                return response()->json([
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $credentials = $request->only('email', 'password');

            $user = User::with('roles')->where('email', $credentials['email'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return response()->json(['message' => 'Invalid email or password'], 401);
            }

            // Generate token
            $token = Str::random(80);

            // Simpan token di kolom remember_token
            $user->update(['remember_token' => $token]);

            // Format data user
            $formattedUser = [
                'id' => $user->id,
                'name' => $user->name,
                'alias' => $user->alias,
                'email' => $user->email,
                'image' => $user->image,
                'roles' => $user->roles->map(fn($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ]),
                'token' => $token,
            ];

            return response()->json([
                'message' => 'Login successful',
                'data' => $formattedUser,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }




    /**
     * Handle user logout.
     */
    public function logout(Request $request)
    {
        $user = User::where('remember_token', $request->bearerToken())->first();

        if ($user) {
            $user->update(['remember_token' => null]);
        }

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}
