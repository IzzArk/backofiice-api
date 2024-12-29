<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class ValidateRememberToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        $user = User::where('remember_token', $token)->first();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->merge(['user' => $user]); // Set user ke request

        return $next($request);
    }
}
