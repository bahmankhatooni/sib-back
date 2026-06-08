<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    /**
     * نمایش لیست اقدامات با صفحه‌بندی
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $query = Task::with(['program.target', 'target']);

        // جستجو بر اساس کد یا عنوان اقدام
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        // فیلتر بر اساس هدف
        if ($request->has('target_id') && !empty($request->target_id)) {
            $query->where('target_id', $request->target_id);
        }

        // فیلتر بر اساس برنامه
        if ($request->has('program_id') && !empty($request->program_id)) {
            $query->where('program_id', $request->program_id);
        }

        $tasks = $query->withCount('activities')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $tasks->items(),
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
                'next_page_url' => $tasks->nextPageUrl(),
                'prev_page_url' => $tasks->previousPageUrl(),
            ]
        ]);
    }

    /**
     * ذخیره اقدام جدید
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:tasks,code',
            'title' => 'required|string|max:200',
            'target_id' => 'required|exists:targets,id',
            'program_id' => 'required|exists:programs,id',
            'activity' => 'nullable|string|max:200',
            'is_active' => 'boolean',
        ]);

        $task = Task::create([
            'code' => $request->code,
            'title' => $request->title,
            'target_id' => $request->target_id,
            'program_id' => $request->program_id,
            'activity' => $request->activity,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'اقدام با موفقیت ایجاد شد',
            'data' => $task->load(['program.target', 'target'])
        ], 201);
    }

    /**
     * نمایش یک اقدام مشخص
     */
    public function show($id)
    {
        $task = Task::with(['program.target', 'target', 'activities'])->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'اقدام مورد نظر یافت نشد'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $task
        ]);
    }

    /**
     * بروزرسانی اقدام
     */
    public function update(Request $request, $id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'اقدام مورد نظر یافت نشد'
            ], 404);
        }

        $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('tasks')->ignore($id)],
            'title' => 'required|string|max:200',
            'target_id' => 'required|exists:targets,id',
            'program_id' => 'required|exists:programs,id',
            'activity' => 'nullable|string|max:200',
            'is_active' => 'boolean',
        ]);

        $task->update($request->only([
            'code', 'title', 'target_id', 'program_id', 'activity', 'is_active'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'اقدام با موفقیت بروزرسانی شد',
            'data' => $task->load(['program.target', 'target'])
        ]);
    }

    /**
     * حذف اقدام
     */
    public function destroy($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'اقدام مورد نظر یافت نشد'
            ], 404);
        }

        // بررسی وجود فعالیت‌های مرتبط
        if ($task->activities()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'این اقدام دارای فعالیت‌های مرتبط است و قابل حذف نمی‌باشد'
            ], 422);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'اقدام با موفقیت حذف شد'
        ]);
    }
}
