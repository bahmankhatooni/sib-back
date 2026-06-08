<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * نمایش لیست کاربران با صفحه‌بندی
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10); // تعداد رکورد در هر صفحه (پیش‌فرض 10)

        $query = User::with(['role', 'unit']);

        // جستجو بر اساس نام، نام خانوادگی یا نام کاربری
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // فیلتر بر اساس نقش
        if ($request->has('role_id') && !empty($request->role_id)) {
            $query->where('role_id', $request->role_id);
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'next_page_url' => $users->nextPageUrl(),
                'prev_page_url' => $users->previousPageUrl(),
            ]
        ]);
    }

    /**
     * ذخیره کاربر جدید
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:6',
            'email' => 'nullable|email|max:100|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'role_id' => 'required|exists:roles,id',
            'unit_id' => 'nullable|exists:units,id',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'email' => $request->email,
            'phone' => $request->phone,
            'role_id' => $request->role_id,
            'unit_id' => $request->unit_id,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'کاربر با موفقیت ایجاد شد',
            'data' => $user->load(['role', 'unit'])
        ], 201);
    }

    /**
     * نمایش یک کاربر مشخص
     */
    public function show($id)
    {
        $user = User::with(['role', 'unit'])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر مورد نظر یافت نشد'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * بروزرسانی کاربر
     *
     * دو حالت:
     * 1. بروزرسانی کامل (همه فیلدها)
     * 2. فقط تغییر رمز عبور (فقط فیلد password)
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر مورد نظر یافت نشد'
            ], 404);
        }

        // ============================================================
        // حالت 1: فقط تغییر رمز عبور (تنها فیلد password ارسال شده)
        // ============================================================
        if ($request->has('password') && count($request->all()) === 1) {
            $request->validate([
                'password' => 'required|string|min:6'
            ]);

            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'رمز عبور با موفقیت تغییر کرد',
                'data' => $user->load(['role', 'unit'])
            ]);
        }

        // ============================================================
        // حالت 2: بروزرسانی کامل اطلاعات کاربر
        // ============================================================
        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'username' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($id)],
            'email' => ['nullable', 'email', 'max:100', Rule::unique('users')->ignore($id)],
            'phone' => 'nullable|string|max:20',
            'role_id' => 'required|exists:roles,id',
            'unit_id' => 'nullable|exists:units,id',
            'is_active' => 'boolean',
        ]);

        $updateData = $request->only(['first_name', 'last_name', 'username', 'email', 'phone', 'role_id', 'unit_id', 'is_active']);

        // اگر رمز عبور جدید ارائه شده باشد (در حالت بروزرسانی کامل)
        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6']);
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'کاربر با موفقیت بروزرسانی شد',
            'data' => $user->load(['role', 'unit'])
        ]);
    }

    /**
     * حذف کاربر
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر مورد نظر یافت نشد'
            ], 404);
        }

        // جلوگیری از حذف خود کاربر جاری
        if (auth()->id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'نمی‌توانید حساب کاربری خود را حذف کنید'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'کاربر با موفقیت حذف شد'
        ]);
    }

    /**
     * تغییر وضعیت فعال/غیرفعال کاربر
     */
    public function toggleStatus($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر مورد نظر یافت نشد'
            ], 404);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->is_active ? 'کاربر فعال شد' : 'کاربر غیرفعال شد',
            'data' => ['is_active' => $user->is_active]
        ]);
    }
}
