<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgramController extends Controller
{
    /**
     * نمایش لیست برنامه‌ها با صفحه‌بندی
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10); // تعداد رکورد در هر صفحه (پیش‌فرض 10)

        $query = Program::with(['target']);

        // فیلتر بر اساس هدف
        if ($request->has('target_id')) {
            $query->where('target_id', $request->target_id);
        }

        // جستجو بر اساس کد یا عنوان
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        $programs = $query->withCount('tasks')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $programs->items(),
            'pagination' => [
                'current_page' => $programs->currentPage(),
                'last_page' => $programs->lastPage(),
                'per_page' => $programs->perPage(),
                'total' => $programs->total(),
                'next_page_url' => $programs->nextPageUrl(),
                'prev_page_url' => $programs->previousPageUrl(),
            ]
        ]);
    }

    /**
     * ذخیره برنامه جدید
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:programs,code',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'target_id' => 'required|exists:targets,id',
            'progress' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $program = Program::create([
            'code' => $request->code,
            'title' => $request->title,
            'description' => $request->description,
            'target_id' => $request->target_id,
            'progress' => $request->progress ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'برنامه با موفقیت ایجاد شد',
            'data' => $program->load('target')
        ], 201);
    }

    /**
     * نمایش یک برنامه مشخص
     */
    public function show($id)
    {
        $program = Program::with(['target', 'tasks'])->find($id);

        if (!$program) {
            return response()->json([
                'success' => false,
                'message' => 'برنامه مورد نظر یافت نشد'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $program
        ]);
    }

    /**
     * بروزرسانی برنامه
     */
    public function update(Request $request, $id)
    {
        $program = Program::find($id);

        if (!$program) {
            return response()->json([
                'success' => false,
                'message' => 'برنامه مورد نظر یافت نشد'
            ], 404);
        }

        $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('programs')->ignore($id)],
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'target_id' => 'required|exists:targets,id',
            'progress' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $program->update($request->only([
            'code', 'title', 'description', 'target_id', 'progress', 'is_active'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'برنامه با موفقیت بروزرسانی شد',
            'data' => $program->load('target')
        ]);
    }

    /**
     * حذف برنامه
     */
    public function destroy($id)
    {
        $program = Program::find($id);

        if (!$program) {
            return response()->json([
                'success' => false,
                'message' => 'برنامه مورد نظر یافت نشد'
            ], 404);
        }

        // بررسی وجود اقدامات مرتبط
        if ($program->tasks()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'این برنامه دارای اقدامات مرتبط است و قابل حذف نمی‌باشد'
            ], 422);
        }

        $program->delete();

        return response()->json([
            'success' => true,
            'message' => 'برنامه با موفقیت حذف شد'
        ]);
    }
}
