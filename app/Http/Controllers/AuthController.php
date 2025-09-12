<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Team;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
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
        $request->session()->regenerate();

        return response()->json(['user' => $user], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // IMPORTANTE: antes, o front deve chamar GET /sanctum/csrf-cookie
        if (! Auth::attempt($credentials, true)) {
            return response()->json(['message' => 'Credenciais inválidas'], 422);
        }

        $request->session()->regenerate();
        return response()->json(['user' => Auth::user()]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logout realizado']);
    }

    public function me(Request $request)
    {
        $authUser = $request->user();
        $teams = $authUser->teams();
        $response = [
            "id"=>$authUser->id,
            "name"=>$authUser->name,
            "email"=>$authUser->email,
            "teams"=>$teams
        ];

        return response()->json($response);
    }
}
