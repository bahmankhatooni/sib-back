<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;

class UnitController extends Controller
{
    /**
     * نمایش لیست واحدها با صفحه‌بندی
     */
    public function index(Request $request)
    {
        // بررسی دسترسی با Policy
        Gate::authorize('viewAny', Unit::class);

        $perPage = $request->get('per_page', 10); // تعداد رکورد در هر صفحه (پیش‌فرض 10)

        $query = Unit::query();

        // جستجو بر اساس کد یا نام واحد
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $units = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $units->items(),
            'pagination' => [
                'current_page' => $units->currentPage(),
                'last_page' => $units->lastPage(),
                'per_page' => $units->perPage(),
                'total' => $units->total(),
                'next_page_url' => $units->nextPageUrl(),
                'prev_page_url' => $units->previousPageUrl(),
            ]
        ]);
    }

    /**
     * ذخیره واحد جدید
     */
    public function store(Request $request)
    {
        // بررسی دسترسی با Policy
        Gate::authorize('create', Unit::class);

        // اعتبارسنجی
        $request->validate([
            'code' => 'required|string|max:50|unique:units,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $unit = Unit::create([
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'واحد با موفقیت ایجاد شد',
            'data' => $unit
        ], 201);
    }

    /**
     * نمایش یک واحد مشخص
     */
    public function show($id)
    {
        $unit = Unit::with('users')->find($id);

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'واحد مورد نظر یافت نشد'
            ], 404);
        }

        // بررسی دسترسی با Policy
        Gate::authorize('view', $unit);

        return response()->json([
            'success' => true,
            'data' => $unit
        ]);
    }

    /**
     * بروزرسانی واحد
     */
    public function update(Request $request, $id)
    {
        $unit = Unit::find($id);

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'واحد مورد نظر یافت نشد'
            ], 404);
        }

        // بررسی دسترسی با Policy
        Gate::authorize('update', $unit);

        $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('units')->ignore($id)],
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $unit->update($request->only(['code', 'name', 'description', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'واحد با موفقیت بروزرسانی شد',
            'data' => $unit
        ]);
    }

    /**
     * حذف واحد
     */
    public function destroy($id)
    {
        $unit = Unit::find($id);

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'واحد مورد نظر یافت نشد'
            ], 404);
        }

        // بررسی دسترسی با Policy
        Gate::authorize('delete', $unit);

        if ($unit->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'این واحد دارای کاربران مرتبط است و قابل حذف نمی‌باشد'
            ], 422);
        }

        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'واحد با موفقیت حذف شد'
        ]);
    }
}
