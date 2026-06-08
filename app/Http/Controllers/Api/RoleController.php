<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * نمایش لیست نقش‌ها با صفحه‌بندی
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10); // تعداد رکورد در هر صفحه (پیش‌فرض 10)

        $query = Role::withCount('users');

        // جستجو بر اساس کد یا نام نقش
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $roles = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $roles->items(),
            'pagination' => [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
                'next_page_url' => $roles->nextPageUrl(),
                'prev_page_url' => $roles->previousPageUrl(),
            ]
        ]);
    }

    /**
     * ذخیره نقش جدید
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:roles,code',
            'name' => 'required|string|max:100',
            'type' => 'required|in:مدیریتی,کاربری,نظارتی,اجرایی',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $role = Role::create([
            'code' => $request->code,
            'name' => $request->name,
            'type' => $request->type,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'نقش با موفقیت ایجاد شد',
            'data' => $role
        ], 201);
    }

    /**
     * نمایش یک نقش مشخص
     */
    public function show($id)
    {
        $role = Role::with('users')->find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'نقش مورد نظر یافت نشد'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $role
        ]);
    }

    /**
     * بروزرسانی نقش
     */
    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'نقش مورد نظر یافت نشد'
            ], 404);
        }

        $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('roles')->ignore($id)],
            'name' => 'required|string|max:100',
            'type' => 'required|in:مدیریتی,کاربری,نظارتی,اجرایی',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $role->update($request->only(['code', 'name', 'type', 'description', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'نقش با موفقیت بروزرسانی شد',
            'data' => $role
        ]);
    }

    /**
     * حذف نقش
     */
    public function destroy($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'نقش مورد نظر یافت نشد'
            ], 404);
        }

        // جلوگیری از حذف نقش مدیر سیستم
        if ($role->code === 'ADMIN') {
            return response()->json([
                'success' => false,
                'message' => 'نقش مدیر سیستم قابل حذف نیست'
            ], 422);
        }

        // بررسی وجود کاربران مرتبط
        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'این نقش دارای کاربران مرتبط است و قابل حذف نمی‌باشد'
            ], 422);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'نقش با موفقیت حذف شد'
        ]);
    }
}
