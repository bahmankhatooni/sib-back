<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    /**
     * نمایش لیست فعالیت‌ها با صفحه‌بندی
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $query = Activity::with(['task.program']);

        // جستجو بر اساس عنوان فعالیت
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        // فیلتر بر اساس اقدام
        if ($request->has('task_id') && !empty($request->task_id)) {
            $query->where('task_id', $request->task_id);
        }

        $activities = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $activities->items(),
            'pagination' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
                'next_page_url' => $activities->nextPageUrl(),
                'prev_page_url' => $activities->previousPageUrl(),
            ]
        ]);
    }

    /**
     * ذخیره فعالیت جدید
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'task_id' => 'required|exists:tasks,id',
            'indicator' => 'nullable|string|max:200',
            'measure' => 'nullable|string|max:200',
            'responsible' => 'nullable|string|max:100',
            'collaborator' => 'nullable|string|max:100',
            'progress' => 'nullable|integer|min:0|max:100',
        ]);

        $activity = Activity::create([
            'title' => $request->title,
            'description' => $request->description,
            'task_id' => $request->task_id,
            'indicator' => $request->indicator,
            'measure' => $request->measure,
            'responsible' => $request->responsible,
            'collaborator' => $request->collaborator,
            'progress' => $request->progress ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'فعالیت با موفقیت ایجاد شد',
            'data' => $activity->load('task')
        ], 201);
    }

    /**
     * نمایش یک فعالیت مشخص
     */
    public function show($id)
    {
        $activity = Activity::with(['task.program.target'])->find($id);

        if (!$activity) {
            return response()->json([
                'success' => false,
                'message' => 'فعالیت مورد نظر یافت نشد'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $activity
        ]);
    }

    /**
     * بروزرسانی فعالیت
     */
    public function update(Request $request, $id)
    {
        $activity = Activity::find($id);

        if (!$activity) {
            return response()->json([
                'success' => false,
                'message' => 'فعالیت مورد نظر یافت نشد'
            ], 404);
        }

        $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'task_id' => 'required|exists:tasks,id',
            'indicator' => 'nullable|string|max:200',
            'measure' => 'nullable|string|max:200',
            'responsible' => 'nullable|string|max:100',
            'collaborator' => 'nullable|string|max:100',
            'progress' => 'nullable|integer|min:0|max:100',
        ]);

        $activity->update($request->only([
            'title', 'description', 'task_id', 'indicator',
            'measure', 'responsible', 'collaborator', 'progress'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'فعالیت با موفقیت بروزرسانی شد',
            'data' => $activity->load('task')
        ]);
    }

    /**
     * حذف فعالیت
     */
    public function destroy($id)
    {
        $activity = Activity::find($id);

        if (!$activity) {
            return response()->json([
                'success' => false,
                'message' => 'فعالیت مورد نظر یافت نشد'
            ], 404);
        }

        $activity->delete();

        return response()->json([
            'success' => true,
            'message' => 'فعالیت با موفقیت حذف شد'
        ]);
    }
}
