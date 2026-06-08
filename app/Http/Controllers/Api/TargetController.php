<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Target;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TargetController extends Controller
{
    /**
     * نمایش لیست اهداف با صفحه‌بندی
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10); // تعداد رکورد در هر صفحه (پیش‌فرض 10)

        $query = Target::withCount('programs');

        // جستجو بر اساس کد یا عنوان هدف
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        // فیلتر بر اساس سال
        if ($request->has('year') && !empty($request->year)) {
            $query->where('year', $request->year);
        }

        $targets = $query->orderBy('year', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $targets->items(),
            'pagination' => [
                'current_page' => $targets->currentPage(),
                'last_page' => $targets->lastPage(),
                'per_page' => $targets->perPage(),
                'total' => $targets->total(),
                'next_page_url' => $targets->nextPageUrl(),
                'prev_page_url' => $targets->previousPageUrl(),
            ]
        ]);
    }

    /**
     * ذخیره هدف جدید
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:targets,code',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'year' => 'required|digits:4',
            'is_active' => 'boolean',
        ]);

        $target = Target::create([
            'code' => $request->code,
            'title' => $request->title,
            'description' => $request->description,
            'year' => (string) $request->year,
            'progress' => 0,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'هدف با موفقیت ایجاد شد',
            'data' => $target
        ], 201);
    }

    /**
     * نمایش یک هدف مشخص
     */
    public function show($id)
    {
        $target = Target::with('programs')->find($id);

        if (!$target) {
            return response()->json([
                'success' => false,
                'message' => 'هدف مورد نظر یافت نشد'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $target
        ]);
    }

    /**
     * بروزرسانی هدف
     */
    public function update(Request $request, $id)
    {
        $target = Target::find($id);

        if (!$target) {
            return response()->json([
                'success' => false,
                'message' => 'هدف مورد نظر یافت نشد'
            ], 404);
        }

        $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('targets')->ignore($id)],
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'year' => 'required|digits:4',
            'is_active' => 'boolean',
        ]);

        $target->update([
            'code' => $request->code,
            'title' => $request->title,
            'description' => $request->description,
            'year' => (string) $request->year,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'هدف با موفقیت بروزرسانی شد',
            'data' => $target
        ]);
    }

    /**
     * حذف هدف
     */
    public function destroy($id)
    {
        $target = Target::find($id);

        if (!$target) {
            return response()->json([
                'success' => false,
                'message' => 'هدف مورد نظر یافت نشد'
            ], 404);
        }

        // بررسی وجود برنامه‌های مرتبط
        if ($target->programs()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'این هدف دارای برنامه‌های مرتبط است و قابل حذف نمی‌باشد'
            ], 422);
        }

        $target->delete();

        return response()->json([
            'success' => true,
            'message' => 'هدف با موفقیت حذف شد'
        ]);
    }
}
