<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        if (!$token = Auth::attempt($credentials)) {
            return response()->json([
                'error' => 'Invalid credentials',
                'is_success' => false,
            ]);
        }
        return response()->json([
            'token' => $token,
            'is_success' => true,
        ]);
    }

    public function logout()
    {
        Auth::logout();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function refresh()
    {
        return response()->json([
            'token' => Auth::refresh()
        ]);
    }

    public function backfromjb()
    {
        return response()->json(['message' =>'You are authenticated to pull data']);
    }
}
