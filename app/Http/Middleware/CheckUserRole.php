<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     * بررسی می‌کند که کاربر نقش مورد نظر را داشته باشد
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $roleCode  // کد نقش (مثال: ADMIN, UNIT_USER)
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $roleCode)
    {
        $user = Auth::user();

        // اگر کاربر لاگین نکرده باشد
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login.'
            ], 401);
        }

        // بررسی نقش کاربر (از طریق رابطه role)
        if (!$user->role || $user->role->code !== $roleCode) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی لازم برای این عملیات را ندارید.'
            ], 403);
        }

        return $next($request);
    }
}
