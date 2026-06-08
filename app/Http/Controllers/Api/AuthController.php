<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)
            ->with(['role', 'unit'])
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['نام کاربری یا رمز عبور اشتباه است.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'username' => ['حساب کاربری شما غیرفعال شده است.'],
            ]);
        }

        // حذف توکن‌های قبلی
        $user->tokens()->delete();

        // ایجاد توکن جدید
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'ورود با موفقیت انجام شد',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role ? [
                        'id' => $user->role->id,
                        'name' => $user->role->name,
                        'code' => $user->role->code,
                        'type' => $user->role->type,
                    ] : null,
                    'unit' => $user->unit ? [
                        'id' => $user->unit->id,
                        'name' => $user->unit->name,
                        'code' => $user->unit->code,
                    ] : null,
                    'is_active' => $user->is_active,
                ],
                'token' => $token,
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'خروج با موفقیت انجام شد',
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['role', 'unit']);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }
}
