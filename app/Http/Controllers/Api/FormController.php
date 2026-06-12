<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldValue;
use App\Models\Target;
use App\Models\Program;
use App\Models\Task;
use App\Models\Activity;
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
        $user = auth()->user();
        $isAdmin = $user->role->slug === 'ADMIN';
        
        $perPage = $request->get('per_page', 10);

        $query = Form::with(['unit', 'target', 'program', 'task', 'activity']);

        // فیلتر بر اساس واحد (برای غیر ادمین)
        if (!$isAdmin) {
            $query->where('unit_id', $user->unit_id);
        }

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
        $user = auth()->user();
        $isAdmin = $user->role->slug === 'ADMIN';
        
        $form = Form::find($id);

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
        $user = auth()->user();
        $isAdmin = $user->role->slug === 'ADMIN';
        
        $form = Form::find($id);

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
        $user = auth()->user();
        $isAdmin = $user->role->slug === 'ADMIN';
        
        // بررسی دسترسی به کاربرگ
        $form = Form::find($id);
        if (!$form) {
            return response()->json([
                'success' => false,
                'message' => 'کاربرگ مورد نظر یافت نشد'
            ], 404);
        }

        if (!$isAdmin && $form->unit_id !== $user->unit_id) {
            return response()->json([
                'success' => false,
                'message' => 'شما به این کاربرگ دسترسی ندارید'
            ], 403);
        }
        
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
        $user = auth()->user();
        $isAdmin = $user->role->slug === 'ADMIN';
        
        $form = Form::find($id);

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
    public function getForm($id)
    {
        try {
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

            // دریافت مقادیر ذخیره شده برای این کاربرگ (تمام ردیف‌ها)
            $fieldValues = FormFieldValue::where('form_id', $id)
                ->orderBy('row_index')
                ->get();

            // گروه‌بندی مقادیر بر اساس row_index
            $valuesByRow = $fieldValues->groupBy('row_index');

            // ساخت آرایه فیلدها
            $fieldsArray = $fields->map(function ($field) {
                return [
                    'id' => $field->id,
                    'field_label' => $field->field_label,
                    'field_type' => $field->field_type,
                    'field_placeholder' => $field->field_placeholder,
                    'field_options' => $field->field_options,
                    'is_required' => $field->is_required,
                    'sort_order' => $field->sort_order,
                ];
            })->toArray();

            // ساخت آرایه ردیف‌های داده
            $dataRows = [];
            foreach ($valuesByRow as $rowIndex => $rowValues) {
                $row = [];
                foreach ($fields as $field) {
                    $value = $rowValues->firstWhere('form_field_id', $field->id);
                    $row[$field->id] = $value ? $value->field_value : '';
                }
                $dataRows[] = [
                    'row_index' => $rowIndex,
                    'values' => $row
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'form' => $form,
                    'fields' => $fieldsArray,
                    'data_rows' => $dataRows,
                    'row_count' => count($dataRows)
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
        $user = auth()->user();
        $isAdmin = $user->role->slug === 'ADMIN';
        
        $form = Form::find($id);

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

        $request->validate([
            'data_rows' => 'required|array',
            'data_rows.*.row_index' => 'required|integer|min:0',
            'data_rows.*.values' => 'required|array',
            'is_completed' => 'boolean',
        ]);

        // حذف تمام مقادیر قبلی این فرم
        FormFieldValue::where('form_id', $id)->delete();

        // ذخیره مقادیر جدید برای تمام ردیف‌ها
        foreach ($request->data_rows as $dataRow) {
            $rowIndex = $dataRow['row_index'];
            $values = $dataRow['values'];

            foreach ($values as $fieldId => $value) {
                // فقط مقادیر غیر خالی را ذخیره کن
                if (!empty(trim($value))) {
                    FormFieldValue::create([
                        'form_field_id' => $fieldId,
                        'form_id' => $id,
                        'row_index' => $rowIndex,
                        'field_value' => $value,
                        'created_by' => $user->id,
                    ]);
                }
            }
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
        $user = auth()->user();
        $isAdmin = $user->role->slug === 'ADMIN';
        
        $form = Form::find($id);
        
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

        return Excel::download(new FormsExport($id), $form->code . '.xlsx');
    }

    /**
     * ورودی Excel برای ایجاد کاربرگ جدید از چند شیت
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            $file = $request->file('file');
            $filePath = $file->getRealPath();
            
            // خواندن فایل Excel
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(
                \PhpOffice\PhpSpreadsheet\IOFactory::identify($filePath)
            );
            $spreadsheet = $reader->load($filePath);
            
            $sheetCount = $spreadsheet->getSheetCount();
            $importedForms = [];
            $errors = [];
            
            // پردازش هر شیت
            for ($sheetIndex = 0; $sheetIndex < $sheetCount; $sheetIndex++) {
                $sheet = $spreadsheet->getSheet($sheetIndex);
                $sheetName = $sheet->getTitle();
                
                // استفاده از نام شیت به عنوان کد کاربرگ
                $formCode = !empty(trim($sheetName)) ? trim($sheetName) : 'Sheet_' . ($sheetIndex + 1);
                
                // بررسی وجود کاربرگ با همین کد
                $existingForm = Form::where('code', $formCode)->first();
                if ($existingForm) {
                    $errors[] = "کاربرگ با کد '{$formCode}' قبلاً وجود دارد";
                    continue;
                }
                
                // پردازش شیت
                try {
                    $this->processSheet($sheet, $formCode);
                    $importedForms[] = $formCode;
                } catch (\Exception $e) {
                    $errors[] = "خطا در پردازش شیت '{$formCode}': " . $e->getMessage();
                }
            }
            
            // ساخت پیام پاسخ
            $message = '';
            if (!empty($importedForms)) {
                $count = count($importedForms);
                $message .= $count . ' کاربرگ با موفقیت ایجاد شد';
                if ($count <= 5) {
                    $message .= ': ' . implode(', ', $importedForms);
                }
            }
            
            if (!empty($errors)) {
                return response()->json([
                    'success' => !empty($importedForms),
                    'message' => $message,
                    'imported' => $importedForms,
                    'errors' => $errors,
                    'imported_count' => count($importedForms),
                    'error_count' => count($errors)
                ], !empty($importedForms) ? 200 : 422);
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'imported' => $importedForms,
                'imported_count' => count($importedForms)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در پردازش فایل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * پردازش یک شیت و ایجاد کاربرگ
     */
    private function processSheet($sheet, $formCode)
    {
        $user = auth()->user();
        $unitId = $user->unit_id ?? 1;
        
        $formData = [];
        $headers = [];
        $dataRows = []; // ذخیره تمام ردیف‌های داده (از ردیف 7 به بعد)
        
        // خواندن کل شیت
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        
        // بررسی اینکه شیت حداقل 6 ردیف داشته باشد
        if ($highestRow < 6) {
            throw new \Exception("فرمت فایل نادرست است: شیت باید حداقل 6 ردیف داشته باشد");
        }
        
        for ($rowIndex = 1; $rowIndex <= $highestRow; $rowIndex++) {
            $row = [];
            
            for ($colIndex = 1; $colIndex <= $highestColumnIndex; $colIndex++) {
                $cell = $sheet->getCellByColumnAndRow($colIndex, $rowIndex);
                $row[] = $cell->getValue();
            }
            
            // ردیف 1: هدف
            if ($rowIndex === 1) {
                $goalText = $row[0] ?? '';
                if (preg_match('/هدف\s+(\S+)\s*:\s*(.+)/', $goalText, $matches)) {
                    $formData['goal'] = [
                        'code' => trim($matches[1]),
                        'title' => trim($matches[2]),
                    ];
                }
            }
            // ردیف 2: برنامه
            elseif ($rowIndex === 2) {
                $programText = $row[0] ?? '';
                if (preg_match('/برنامه\s+(\S+)\s*:\s*(.+)/', $programText, $matches)) {
                    $formData['program'] = [
                        'code' => trim($matches[1]),
                        'title' => trim($matches[2]),
                    ];
                }
            }
            // ردیف 3: اقدام
            elseif ($rowIndex === 3) {
                $taskText = $row[0] ?? '';
                if (preg_match('/اقدام\s+(\S+)\s*:\s*(.+)/', $taskText, $matches)) {
                    $formData['task'] = [
                        'code' => trim($matches[1]),
                        'title' => trim($matches[2]),
                    ];
                }
            }
            // ردیف 4: فعالیت
            elseif ($rowIndex === 4) {
                $activityText = $row[0] ?? '';
                if (preg_match('/فعالیت\s+(\S+)\s*:\s*(.+)/', $activityText, $matches)) {
                    $formData['activity'] = [
                        'code' => trim($matches[1]),
                        'title' => trim($matches[2]),
                    ];
                } elseif (preg_match('/فعالیت\s*:\s*(.+)/', $activityText, $matches)) {
                    $formData['activity'] = [
                        'title' => trim($matches[1]),
                    ];
                }
            }
            // از ردیف 5 به بعد: جستجو برای عناوین و مقادیر
            elseif ($rowIndex >= 5) {
                // بررسی این ردیف برای عناوین (headers)
                // اگر ستون اول "ردیف" باشد و ستون‌های بعدی مقدار داشته باشند
                $firstCell = trim($row[0] ?? '');
                
                if ($firstCell === 'ردیف' && empty($headers)) {
                    // این ردیف احتمالاً عناوین است
                    $hasHeaders = false;
                    foreach ($row as $colIndex => $header) {
                        if ($colIndex === 0) continue; // skip "ردیف"
                        $header = trim($header ?? '');
                        if (!empty($header)) {
                            $headers[$colIndex] = $header;
                            $hasHeaders = true;
                        }
                    }
                    // اگر هدر پیدا نشد، ادامه بده تا ردیف بعدی را چک کنیم
                    if (!$hasHeaders) {
                        $headers = [];
                    }
                }
                // اگر header داریم و این ردیف شماره دارد (مثلاً 1, 2, 3, ...)
                elseif (!empty($headers) && is_numeric($firstCell)) {
                    // این یک ردیف داده است
                    $hasData = false;
                    $dataRow = [];
                    
                    foreach ($row as $colIndex => $value) {
                        if ($colIndex === 0) continue; // skip شماره ردیف
                        if (isset($headers[$colIndex])) {
                            $trimmedValue = trim($value ?? '');
                            $dataRow[$colIndex] = $trimmedValue;
                            if (!empty($trimmedValue)) {
                                $hasData = true;
                            }
                        }
                    }
                    
                    if ($hasData) {
                        $dataRows[] = $dataRow;
                    }
                }
            }
        }
        
        // اگر هیچ header نداشتیم، یک کاربرگ خالی (بدون فیلد) ایجاد می‌کنیم
        // این برای کاربرگ‌هایی است که فقط metadata دارند
        
        // پیدا کردن IDها
        $targetId = null;
        $programId = null;
        $taskId = null;
        $activityId = null;
        
        // پیدا کردن هدف
        if (!empty($formData['goal']['code'])) {
            $target = Target::where('code', $formData['goal']['code'])->first();
            if ($target) {
                $targetId = $target->id;
            } else {
                throw new \Exception("هدف با کد '{$formData['goal']['code']}' یافت نشد");
            }
        } else {
            throw new \Exception("کد هدف یافت نشد");
        }
        
        // پیدا کردن برنامه
        if (!empty($formData['program']['code']) && $targetId) {
            $program = Program::where('target_id', $targetId)
                ->where('code', $formData['program']['code'])
                ->first();
            if ($program) {
                $programId = $program->id;
            } else {
                throw new \Exception("برنامه با کد '{$formData['program']['code']}' در هدف مشخص شده یافت نشد");
            }
        } else {
            throw new \Exception("کد برنامه یافت نشد");
        }
        
        // پیدا کردن اقدام (اختیاری)
        if (!empty($formData['task']['code']) && $programId) {
            $task = Task::where('program_id', $programId)
                ->where('code', $formData['task']['code'])
                ->first();
            if ($task) {
                $taskId = $task->id;
            }
            // اگر یافت نشد، null می‌ماند (اختیاری است)
        }
        
        // پیدا کردن فعالیت (اختیاری)
        if (!empty($formData['activity']['title'])) {
            // اگر کد داشت، اول با کد جستجو کن
            if (!empty($formData['activity']['code'])) {
                $activity = Activity::where('code', $formData['activity']['code'])->first();
            }
            // اگر پیدا نشد یا کد نداشت، با عنوان جستجو کن
            if (!isset($activity)) {
                $activity = Activity::where('title', 'like', '%' . $formData['activity']['title'] . '%')->first();
            }
            if ($activity) {
                $activityId = $activity->id;
            }
        }
        
        // ایجاد کاربرگ
        $form = Form::create([
            'code' => $formCode,
            'unit_id' => $unitId,
            'target_id' => (int)$targetId,
            'program_id' => (int)$programId,
            'task_id' => $taskId ? (int)$taskId : null,
            'activity_id' => $activityId ? (int)$activityId : null,
            'description' => 'Import از Excel - ' . $formCode,
            'is_completed' => false,
            'created_by' => $user->username,
        ]);
        
        // اگر header داشتیم، فیلدها را ایجاد کن
        if (!empty($headers)) {
            // ایجاد فیلدهای متغیر (یک بار برای فرم)
            $formFields = [];
            $sortOrder = 0;
            
            foreach ($headers as $colIndex => $header) {
                $formField = FormField::create([
                    'form_id' => $form->id,
                    'field_label' => $header,
                    'field_type' => 'text',
                    'field_placeholder' => '',
                    'is_required' => false,
                    'sort_order' => $sortOrder,
                ]);
                
                $formFields[$colIndex] = $formField;
                $sortOrder++;
            }
            
            // ایجاد مقادیر فیلدها برای تمام ردیف‌های داده
            foreach ($dataRows as $rowIdx => $dataRow) {
                foreach ($headers as $colIndex => $header) {
                    $value = $dataRow[$colIndex] ?? '';
                    
                    // فقط مقادیر غیر خالی را ذخیره کن
                    if (!empty(trim($value)) && isset($formFields[$colIndex])) {
                        FormFieldValue::create([
                            'form_field_id' => $formFields[$colIndex]->id,
                            'form_id' => $form->id,
                            'row_index' => $rowIdx, // شماره ردیف (0, 1, 2, ...)
                            'field_value' => $value,
                            'created_by' => $user->id,
                        ]);
                    }
                }
            }
        }
        // اگر header نداشتیم، فقط کاربرگ را ایجاد می‌کنیم (بدون فیلد)
        
        return $form;
    }
}
