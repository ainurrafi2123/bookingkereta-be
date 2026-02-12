<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
// use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // REGISTER
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role'     => 'in:penumpang,petugas',
            'profile_photo' => 'nullable|image|max:2048'
        ]);

        // Upload foto jika ada
        $profilePhoto = null;
        if ($request->hasFile('profile_photo')) {
            $profilePhoto = $request->file('profile_photo')->store('profile_photos', 'public');
        }

        // Create User
        $user = User::create([
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role ?? 'penumpang',
            'profile_photo' => $profilePhoto,
        ]);

        // Buat record role secara otomatis
        // Buat record role secara otomatis
        $roleModels = [
            'penumpang' => \App\Models\Penumpang::class,
            'petugas' => \App\Models\Petugas::class,
        ];

        if (isset($roleModels[$user->role])) {
            $roleModels[$user->role]::create(['user_id' => $user->id]);
        }
        
        return response()->json([
            'message' => 'User registered successfully',
            'data' => $user
        ], 201);
    }

    // // LOGIN
    // public function login(Request $request)
    // {
    //     $request->validate([
    //         'email'    => 'required|email',
    //         'password' => 'required'
    //     ]);

    //     if (!Auth::attempt($request->only('email', 'password'))) {
    //         return response()->json([
    //             'message' => 'Invalid credentials'
    //         ], 401);
    //     }

    //     // 🔐 penting untuk security
    //     $request->session()->regenerate();

    //     return response()->json([
    //         'message' => 'Login success',
    //         'user' => Auth::user(),
    //     ]);
    // }
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()
            ->json([
                'message' => 'Login success',
                'token' => $token, 
                'user' => $user
            ])
            ->cookie(
                'auth_token', // nama cookie
                $token,
                60 * 24 * 7,   // 7 hari
                '/',
                null,
                true,         // secure (HTTPS)
                true          // httpOnly
            );
    }

    // LOGOUT
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()
            ->json([
                'message' => 'Logged out successfully'
            ])
            ->cookie(
                'auth_token',
                '',
                -1 // hapus cookie
            );
    }

}
