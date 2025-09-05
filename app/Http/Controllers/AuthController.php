<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $req)
    {
        $data = $req->validate([
            'name'     => 'required|string|min:2',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // faz login logo após registro
        Auth::login($user);
        $req->session()->regenerate();

        return response()->json(['user' => $user], 201);
    }

    public function login(Request $req)
    {
        $credentials = $req->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

        // IMPORTANTE: antes, o front deve chamar GET /sanctum/csrf-cookie
        if (! Auth::attempt($credentials, true)) {
            return response()->json(['message' => 'Credenciais inválidas'], 422);
        }

        $req->session()->regenerate();
        return response()->json(['user' => Auth::user()]);
    }

    public function logout(Request $req)
    {
        Auth::guard('web')->logout();
        $req->session()->invalidate();
        $req->session()->regenerateToken();

        return response()->json(['message' => 'Logout realizado']);
    }

    public function me(Request $req)
    {
        return response()->json($req->user());
    }
}
