<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldValue;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Exports\FormsExport;
use App\Imports\FormsImport;
use Maatwebsite\Excel\Facades\Excel;

class FormController extends Controller
{
    /**
     * نمایش لیست کاربرگ‌ها با صفحه‌بندی
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $query = Form::with(['unit', 'target', 'program', 'task', 'activity']);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('code', 'like', "%{$search}%");
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
     * ذخیره کاربرگ جدید
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:forms,code',
            'unit_id' => 'required|exists:units,id',
            'target_id' => 'required|exists:targets,id',
            'program_id' => 'required|exists:programs,id',
            'task_id' => 'nullable|exists:tasks,id',
            'activity_id' => 'nullable|exists:activities,id',
            'description' => 'nullable|string',
            'is_completed' => 'boolean',
        ]);

        $form = Form::create([
            'code' => $request->code,
            'unit_id' => $request->unit_id,
            'target_id' => $request->target_id,
            'program_id' => $request->program_id,
            'task_id' => $request->task_id,
            'activity_id' => $request->activity_id,
            'description' => $request->description,
            'is_completed' => $request->is_completed ?? false,
            'created_by' => auth()->user()->username,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'کاربرگ با موفقیت ایجاد شد',
            'data' => $form->load(['unit', 'target', 'program', 'task', 'activity'])
        ], 201);
    }


    /**
     * نمایش یک کاربرگ مشخص
     */
    public function show($id)
    {
        $form = Form::with(['unit', 'target', 'program', 'task', 'activity'])->find($id);

        if (!$form) {
            return response()->json([
                'success' => false,
                'message' => 'کاربرگ مورد نظر یافت نشد'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $form
        ]);
    }

    /**
     * بروزرسانی کاربرگ
     */
    public function update(Request $request, $id)
    {
        $form = Form::find($id);

        if (!$form) {
            return response()->json([
                'success' => false,
                'message' => 'کاربرگ مورد نظر یافت نشد'
            ], 404);
        }

        $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('forms')->ignore($id)],
            'unit_id' => 'required|exists:units,id',
            'target_id' => 'required|exists:targets,id',
            'program_id' => 'required|exists:programs,id',
            'task_id' => 'nullable|exists:tasks,id',
            'activity_id' => 'nullable|exists:activities,id',
            'description' => 'nullable|string',
            'is_completed' => 'boolean',
        ]);

        $form->update($request->only([
            'code', 'unit_id', 'target_id', 'program_id',
            'task_id', 'activity_id', 'description', 'is_completed'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'کاربرگ با موفقیت بروزرسانی شد',
            'data' => $form->load(['unit', 'target', 'program', 'task', 'activity'])
        ]);
    }


    /**
     * حذف کاربرگ
     */
    public function destroy($id)
    {
        $form = Form::find($id);

        if (!$form) {
            return response()->json([
                'success' => false,
                'message' => 'کاربرگ مورد نظر یافت نشد'
            ], 404);
        }

        // حذف مقادیر فیلدها
        foreach ($form->formFields as $field) {
            FormFieldValue::where('form_field_id', $field->id)
                ->where('form_id', $id)
                ->delete();
            $field->delete();
        }

        $form->delete();

        return response()->json([
            'success' => true,
            'message' => 'کاربرگ با موفقیت حذف شد'
        ]);
    }

    /**
     * دریافت فیلدهای فرم یک کاربرگ (برای مدیریت)
     */
    public function getFields($id)
    {
        $fields = FormField::where('form_id', $id)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $fields
        ]);
    }

    /**
     * ذخیره فیلدهای فرم (ساختار فیلدها)
     */
    public function saveFields(Request $request, $id)
    {
        $form = Form::find($id);

        if (!$form) {
            return response()->json([
                'success' => false,
                'message' => 'کاربرگ مورد نظر یافت نشد'
            ], 404);
        }

        $request->validate([
            'fields' => 'required|array',
            'fields.*.field_label' => 'required|string|max:100',
            'fields.*.field_type' => 'required|string|in:text,number,date,select,textarea,checkbox',
            'fields.*.field_placeholder' => 'nullable|string',
            'fields.*.is_required' => 'boolean',
            'fields.*.optionsText' => 'nullable|string',
        ]);

        // حذف فیلدهای قبلی و مقادیر آنها
        foreach ($form->formFields as $oldField) {
            FormFieldValue::where('form_field_id', $oldField->id)->delete();
            $oldField->delete();
        }

        // ایجاد فیلدهای جدید
        foreach ($request->fields as $index => $fieldData) {
            $options = null;
            if ($fieldData['field_type'] === 'select' && !empty($fieldData['optionsText'])) {
                $options = array_map('trim', explode(',', $fieldData['optionsText']));
            }

            FormField::create([
                'form_id' => $id,
                'field_label' => $fieldData['field_label'],
                'field_type' => $fieldData['field_type'],
                'field_placeholder' => $fieldData['field_placeholder'] ?? null,
                'field_options' => $options,
                'is_required' => $fieldData['is_required'] ?? false,
                'sort_order' => $index,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'فیلدهای فرم با موفقیت ذخیره شدند',
        ]);
    }

    /**
     * دریافت فرم پویا برای نمایش و مقداردهی (فیلدها + مقادیر ذخیره شده)
     */
    /**
     * دریافت فرم پویا برای نمایش و مقداردهی (فیلدها + مقادیر ذخیره شده)
     */
    /**
     * دریافت فرم پویا برای نمایش و مقداردهی (فیلدها + مقادیر ذخیره شده)
     */
    /**
     * دریافت فرم پویا برای نمایش و مقداردهی (فیلدها + مقادیر ذخیره شده)
     */
    public function getForm($id)
    {
        try {
            $form = Form::with(['unit', 'target', 'program', 'task', 'activity'])->find($id);

            if (!$form) {
                return response()->json([
                    'success' => false,
                    'message' => 'کاربرگ مورد نظر یافت نشد'
                ], 404);
            }

            // دریافت فیلدهای متغیر فرم
            $fields = FormField::where('form_id', $id)
                ->orderBy('sort_order')
                ->get();

            // دریافت مقادیر ذخیره شده برای این کاربرگ
            $fieldValues = FormFieldValue::where('form_id', $id)
                ->where('created_by', auth()->id())
                ->get()
                ->keyBy('form_field_id');

            // ساخت آرایه فیلدها با مقادیر
            $fieldsArray = [];
            foreach ($fields as $field) {
                $value = $fieldValues->get($field->id);
                $fieldsArray[] = [
                    'id' => $field->id,
                    'field_label' => $field->field_label,
                    'field_type' => $field->field_type,
                    'field_placeholder' => $field->field_placeholder,
                    'field_options' => $field->field_options,
                    'is_required' => $field->is_required,
                    'sort_order' => $field->sort_order,
                    'value' => $value ? $value->field_value : '',
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'form' => $form,
                    'fields' => $fieldsArray
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت اطلاعات فرم: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ذخیره مقادیر فرم پویا و به‌روزرسانی وضعیت تکمیل
     */
    public function saveForm(Request $request, $id)
    {
        $form = Form::find($id);

        if (!$form) {
            return response()->json([
                'success' => false,
                'message' => 'کاربرگ مورد نظر یافت نشد'
            ], 404);
        }

        $request->validate([
            'fields' => 'required|array',
            'fields.*.id' => 'required|exists:form_fields,id',
            'fields.*.value' => 'nullable|string',
            'is_completed' => 'boolean',
        ]);

        // ذخیره مقادیر فیلدها
        foreach ($request->fields as $fieldData) {
            FormFieldValue::updateOrCreate(
                [
                    'form_field_id' => $fieldData['id'],
                    'form_id' => $id,
                    'created_by' => auth()->id(),
                ],
                [
                    'field_value' => $fieldData['value'] ?? null,
                ]
            );
        }

        // به‌روزرسانی وضعیت تکمیل کاربرگ
        if ($request->has('is_completed')) {
            $form->is_completed = $request->is_completed;
            $form->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'فرم با موفقیت ذخیره شد',
        ]);
    }

    /**
     * خروجی Excel از کاربرگ
     */
    public function export($id)
    {
        $form = Form::find($id);
        if (!$form) {
            return response()->json([
                'success' => false,
                'message' => 'کاربرگ مورد نظر یافت نشد'
            ], 404);
        }

        return Excel::download(new FormsExport($id), $form->code . '.xlsx');
    }

    /**
     * ورودی Excel برای ایجاد کاربرگ جدید
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'code' => 'required|string|max:50'
        ]);

        // بررسی وجود کاربرگ با همین کد
        $existingForm = Form::where('code', $request->code)->first();
        if ($existingForm) {
            return response()->json([
                'success' => false,
                'message' => "کاربرگ با کد {$request->code} قبلاً وجود دارد. لطفاً ابتدا آن را حذف کنید."
            ], 422);
        }

        $import = new FormsImport($request->code);
        Excel::import($import, $request->file('file'));

        $errors = $import->getErrors();
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در بارگذاری فایل',
                'errors' => $errors
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'کاربرگ با موفقیت بارگذاری شد'
        ]);
    }
}
