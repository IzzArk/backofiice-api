<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Laravolt\Avatar\Avatar;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index()
    {
        $users = User::with('roles')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'alias' => $user->alias,
                'email' => $user->email,
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                    ];
                }),
            ];
        });

        return response()->json(['data' => $users], 200);
    }


    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        // Membuat avatar dari nama
        $avatar = new Avatar();
        $image = $avatar->create($request->name)->getImageObject(); // Mengambil gambar sebagai objek

        // Simpan gambar avatar ke disk static_images (atau disk lain)
        $filename = time() . ".png";
        // Storage::disk('static_images')->put($filename, (string) $image->encode('png')); // Encode gambar sebagai PNG

        // Simpan nama file ke dalam database
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->email),
            'image' => $filename
        ]);

        // Assign role to user
        $user->assignRole($request->roles);

        return response()->json(['message' => 'User created successfully', 'data' => $user], 201);
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);
        return response()->json(['data' => $user], 200);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'alias' => 'nullable|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'gender' => 'nullable|string|in:male,female',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:500',
            'phone_number' => 'nullable|string|max:15',
            'image' => 'nullable|string|max:255',
            'signature' => 'nullable|string|max:255',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json(['message' => 'User updated successfully', 'data' => $user], 200);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}
