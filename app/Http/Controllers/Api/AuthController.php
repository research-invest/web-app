<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (auth()->attempt($request->only('email', 'password'))) {
            $user = auth()->user();
            $token = Str::random(60);
            
            $user->forceFill([
                'api_token' => $token,
            ])->save();

            return response()->json([
                'token' => $token,
                'user' => $user
            ]);
        }

        return response()->json([
            'message' => 'Неверные учетные данные'
        ], 401);
    }

    public function logout(Request $request)
    {
        $user = auth()->user();
        $user->api_token = null;
        $user->save();
        
        return response()->json([
            'message' => 'Выход выполнен успешно'
        ]);
    }
}
