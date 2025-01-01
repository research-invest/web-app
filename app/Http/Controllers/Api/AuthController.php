<?php

namespace App\Http\Controllers\Api;

use App\Helpers\UserHelper;
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
            $user = UserHelper::get();

            if (!$user->api_token) {
                $user->forceFill([
                    'api_token' => Str::random(60),
                ])->save();
            }

            return response()->json([
                'token' => $user->api_token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
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
