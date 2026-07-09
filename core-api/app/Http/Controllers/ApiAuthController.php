<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiAuthController extends Controller
{
    // 1. Register a new user with a type
    public function register(Request $request) {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string',
            'type' => 'nullable|string' // can send 'admin', 'vendor', etc.
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => $request->type ?? 'user' // defaults to 'user' if empty
        ]);

        $token = $user->createToken('mytoken')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    // 2. Login
    public function login(Request $request) {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Wrong email or password'], 401);
        }

        $token = $user->createToken('mytoken')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], 200);
    }
}
