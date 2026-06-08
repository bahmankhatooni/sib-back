<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldValue;
use App\Models\Unit;
use App\Models\Target;
use App\Models\Program;
use App\Models\Task;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\ReportsExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * دریافت آمار کلی سیستم
     */
    public function statistics(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->role->slug === 'ADMIN';

        // فیلتر بر اساس واحد (برای غیر ادمین)
        $formsQuery = Form::query();
        if (!$isAdmin) {
            $formsQuery->where('unit_id', $user->unit_id);
        }

        // فیلتر بر اساس واحد (برای ادمین)
        if ($isAdmin && $request->has('unit_id') && !empty($request->unit_id)) {
            $formsQuery->where('unit_id', $request->unit_id);
        }

        // فیلتر بر اساس هدف
        if ($request->has('target_id') && !empty($request->target_id)) {
            $formsQuery->where('target_id', $request->target_id);
        }

        // فیلتر بر اساس برنامه
        if ($request->has('program_id') && !empty($request->program_id)) {
            $formsQuery->where('program_id', $request->program_id);
        }

        // محاسبه آمار
        $totalForms = (clone $formsQuery)->count();
        $completedForms = (clone $formsQuery)->where('is_completed', true)->count();
        $incompleteForms = (clone $formsQuery)->where('is_completed', false)->count();

        // آمار بر اساس واحد
        $statsByUnit = Unit::select('units.id', 'units.name', DB::raw('COUNT(forms.id) as total_forms'))
            ->leftJoin('forms', function($join) use ($request, $isAdmin, $user) {
                $join->on('units.id', '=', 'forms.unit_id');
                if (!$isAdmin) {
                    $join->where('forms.unit_id', '=', $user->unit_id);
                }
            })
            ->groupBy('units.id', 'units.name')
            ->get();

        // آمار بر اساس هدف
        $statsByTarget = Target::select('targets.id', 'targets.code', 'targets.title', DB::raw('COUNT(forms.id) as total_forms'))
            ->leftJoin('forms', function($join) use ($request, $isAdmin, $user) {
                $join->on('targets.id', '=', 'forms.target_id');
                if (!$isAdmin) {
                    $join->where('forms.unit_id', '=', $user->unit_id);
                }
            })
            ->groupBy('targets.id', 'targets.code', 'targets.title')
            ->get();

        // آمار بر اساس برنامه
        $statsByProgram = Program::select('programs.id', 'programs.code', 'programs.title', DB::raw('COUNT(forms.id) as total_forms'))
            ->leftJoin('forms', function($join) use ($request, $isAdmin, $user) {
                $join->on('programs.id', '=', 'forms.program_id');
                if (!$isAdmin) {
                    $join->where('forms.unit_id', '=', $user->unit_id);
                }
            })
            ->groupBy('programs.id', 'programs.code', 'programs.title')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_forms' => $totalForms,
                    'completed_forms' => $completedForms,
                    'incomplete_forms' => $incompleteForms,
                    'completion_percentage' => $totalForms > 0 ? round(($completedForms / $totalForms) * 100, 2) : 0,
                ],
                'by_unit' => $statsByUnit,
                'by_target' => $statsByTarget,
                'by_program' => $statsByProgram,
            ]
        ]);
    }

    /**
     * دریافت لیست کاربرگ‌ها با فیلترهای پیشرفته برای گزارش
     */
    public function list(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->role->slug === 'ADMIN';

        $perPage = $request->get('per_page', 10);

        $query = Form::with(['unit', 'target', 'program', 'task', 'activity']);

        // فیلتر بر اساس واحد (برای غیر ادمین)
        if (!$isAdmin) {
            $query->where('unit_id', $user->unit_id);
        }

        // فیلترهای جستجو
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // فیلتر بر اساس واحد (برای ادمین)
        if ($isAdmin && $request->has('unit_id') && !empty($request->unit_id)) {
            $query->where('unit_id', $request->unit_id);
        }

        // فیلتر بر اساس هدف
        if ($request->has('target_id') && !empty($request->target_id)) {
            $query->where('target_id', $request->target_id);
        }

        // فیلتر بر اساس برنامه
        if ($request->has('program_id') && !empty($request->program_id)) {
            $query->where('program_id', $request->program_id);
        }

        // فیلتر بر اساس اقدام
        if ($request->has('task_id') && !empty($request->task_id)) {
            $query->where('task_id', $request->task_id);
        }

        // فیلتر بر اساس فعالیت
        if ($request->has('activity_id') && !empty($request->activity_id)) {
            $query->where('activity_id', $request->activity_id);
        }

        // فیلتر بر اساس وضعیت تکمیل
        if ($request->has('is_completed') && $request->is_completed !== null && $request->is_completed !== '') {
            $query->where('is_completed', $request->is_completed);
        }

        $forms = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $forms->items(),
            'pagination' => [
                'current_page' => $forms->currentPage(),
                'last_page' => $forms->lastPage(),
                'per_page' => $forms->perPage(),
                'total' => $forms->total(),
                'next_page_url' => $forms->nextPageUrl(),
                'prev_page_url' => $forms->previousPageUrl(),
            ]
        ]);
    }

    /**
     * دریافت جزئیات یک کاربرگ برای گزارش (شامل فیلدها و مقادیر)
     */
    public function details($id)
    {
        $user = auth()->user();
        $isAdmin = $user->role->slug === 'ADMIN';

        $form = Form::with(['unit', 'target', 'program', 'task', 'activity'])->find($id);

        if (!$form) {
            return response()->json([
                'success' => false,
                'message' => 'کاربرگ مورد نظر یافت نشد'
            ], 404);
        }

        // بررسی دسترسی
        if (!$isAdmin && $form->unit_id !== $user->unit_id) {
            return response()->json([
                'success' => false,
                'message' => 'شما به این کاربرگ دسترسی ندارید'
            ], 403);
        }

        // دریافت فیلدهای متغیر فرم
        $fields = FormField::where('form_id', $id)
            ->orderBy('sort_order')
            ->get();

        // دریافت تمام مقادیر ذخیره شده
        $fieldValues = FormFieldValue::where('form_id', $id)
            ->with('creator')
            ->get()
            ->groupBy('form_field_id');

        // ساخت آرایه فیلدها با مقادیر
        $fieldsArray = [];
        foreach ($fields as $field) {
            $values = $fieldValues->get($field->id, collect());
            $fieldsArray[] = [
                'id' => $field->id,
                'field_label' => $field->field_label,
                'field_type' => $field->field_type,
                'field_placeholder' => $field->field_placeholder,
                'field_options' => $field->field_options,
                'is_required' => $field->is_required,
                'sort_order' => $field->sort_order,
                'values' => $values->map(function($value) {
                    return [
                        'id' => $value->id,
                        'field_value' => $value->field_value,
                        'created_by' => $value->creator ? $value->creator->username : 'نامشخص',
                        'created_at' => $value->created_at,
                    ];
                })->toArray(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'form' => $form,
                'fields' => $fieldsArray
            ]
        ]);
    }

    /**
     * Export گزارش به فایل Excel
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->role->slug === 'ADMIN';

        // دریافت فیلترها از request
        $filters = [
            'unit_id' => $isAdmin ? $request->get('unit_id') : $user->unit_id,
            'target_id' => $request->get('target_id'),
            'program_id' => $request->get('program_id'),
            'task_id' => $request->get('task_id'),
            'activity_id' => $request->get('activity_id'),
            'is_completed' => $request->get('is_completed'),
            'search' => $request->get('search'),
            'is_admin' => $isAdmin,
        ];

        $filename = 'report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new ReportsExport($filters), $filename);
    }
}
