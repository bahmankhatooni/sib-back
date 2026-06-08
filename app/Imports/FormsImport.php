<?php

namespace App\Imports;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldValue;
use App\Models\Target;
use App\Models\Program;
use App\Models\Task;
use App\Models\Activity;
use App\Models\Unit;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class FormsImport implements ToCollection
{
    protected $formCode;
    protected $errors = [];

    public function __construct($formCode)
    {
        $this->formCode = $formCode;
    }

    public function collection(Collection $rows)
    {
        // بررسی وجود کاربرگ با همین کد
        $existingForm = Form::where('code', $this->formCode)->first();
        if ($existingForm) {
            $this->errors[] = "کاربرگ با کد {$this->formCode} قبلاً وجود دارد. لطفاً ابتدا آن را حذف کنید.";
            return;
        }

        $formData = [];
        $headers = [];
        $values = [];

        // خواندن ردیف‌های مهم از فایل Excel
        foreach ($rows as $rowIndex => $row) {
            // ردیف 1 (A1): هدف (سلول‌ها ادغام شده‌اند)
            if ($rowIndex === 0) {
                $goalText = $row[0] ?? ''; // فقط سلول A1
                // فرمت: "هدف 5 : ارتقاء کیفیت رسیدگی و اتقان آراء"
                if (preg_match('/هدف\s+(\S+)\s*:\s*(.+)/', $goalText, $matches)) {
                    $targetCode = trim($matches[1]);
                    $targetTitle = trim($matches[2]);
                    $formData['goal'] = [
                        'code' => $targetCode,
                        'title' => $targetTitle,
                        'text' => $goalText
                    ];
                } else {
                    $this->errors[] = "فرمت ردیف هدف نادرست است. فرمت صحیح: 'هدف 5 : ارتقاء کیفیت رسیدگی و اتقان آراء'";
                }
            }
            
            // ردیف 2 (A2): برنامه (سلول‌ها ادغام شده‌اند)
            elseif ($rowIndex === 1) {
                $programText = $row[0] ?? ''; // فقط سلول A2
                // فرمت: "برنامه 4 :شناسایی و رفع موارد اختلاف نظر قضات (نقص، نقض و آرای متهافت)"
                if (preg_match('/برنامه\s+(\S+)\s*:\s*(.+)/', $programText, $matches)) {
                    $programCode = trim($matches[1]);
                    $programTitle = trim($matches[2]);
                    $formData['program'] = [
                        'code' => $programCode,
                        'title' => $programTitle,
                        'text' => $programText
                    ];
                } else {
                    $this->errors[] = "فرمت ردیف برنامه نادرست است. فرمت صحیح: 'برنامه 4 :شناسایی و رفع موارد اختلاف'";
                }
            }
            
            // ردیف 3 (A3): اقدام (سلول‌ها ادغام شده‌اند)
            elseif ($rowIndex === 2) {
                $taskText = $row[0] ?? ''; // فقط سلول A3
                // فرمت: "اقدام 50401 :استخراج موارد اختلاف"
                if (preg_match('/اقدام\s+(\S+)\s*:\s*(.+)/', $taskText, $matches)) {
                    $taskCode = trim($matches[1]);
                    $taskTitle = trim($matches[2]);
                    $formData['task'] = [
                        'code' => $taskCode,
                        'title' => $taskTitle,
                        'text' => $taskText
                    ];
                } else {
                    // اقدام می‌تواند NULL باشد
                    $formData['task'] = [
                        'code' => null,
                        'title' => null,
                        'text' => $taskText
                    ];
                }
            }
            
            // ردیف 4 (A4): فعالیت (سلول‌ها ادغام شده‌اند)
            elseif ($rowIndex === 3) {
                $activityText = $row[0] ?? ''; // فقط سلول A4
                // فرمت: "فعالیت :" یا "فعالیت 4 :پیگیری و نظارت"
                if (preg_match('/فعالیت\s+(\S+)\s*:\s*(.+)/', $activityText, $matches)) {
                    $activityNumber = trim($matches[1]);
                    $activityTitle = trim($matches[2]);
                    $formData['activity'] = [
                        'number' => $activityNumber,
                        'title' => $activityTitle,
                        'text' => $activityText
                    ];
                } elseif (preg_match('/فعالیت\s*:/', $activityText)) {
                    // فقط "فعالیت :" (خالی)
                    $formData['activity'] = [
                        'number' => null,
                        'title' => null,
                        'text' => $activityText
                    ];
                } else {
                    $formData['activity'] = [
                        'number' => null,
                        'title' => null,
                        'text' => $activityText
                    ];
                }
            }
            
            // ردیف 5: عناوین فیلدهای متغیر
            elseif ($rowIndex === 4) {
                foreach ($row as $colIndex => $header) {
                    $header = trim($header ?? '');
                    if (!empty($header)) {
                        $headers[$colIndex] = $header;
                    }
                }
            }
            
            // ردیف 6: مقادیر فیلدهای متغیر
            elseif ($rowIndex === 5) {
                foreach ($row as $colIndex => $value) {
                    if (isset($headers[$colIndex])) {
                        $values[$headers[$colIndex]] = trim($value ?? '');
                    }
                }
            }
        }

        // بررسی خطاهای اولیه
        if (!empty($this->errors)) {
            return;
        }

        // پیدا کردن IDهای مربوطه
        $targetId = null;
        $programId = null;
        $taskId = null;
        $activityId = null;
        
        // ========== 1. پیدا کردن هدف ==========
        if (!empty($formData['goal']['code'])) {
            $targetCode = $formData['goal']['code'];
            $targetTitle = $formData['goal']['title'] ?? '';
            
            // اول با کد جستجو
            $target = Target::where('code', $targetCode)->first();
            
            // اگر پیدا نشد، با عنوان جستجو
            if (!$target && !empty($targetTitle)) {
                $target = Target::where('title', 'like', '%' . $targetTitle . '%')->first();
            }
            
            if ($target) {
                $targetId = $target->id;
            } else {
                $this->errors[] = "هدف با کد '{$targetCode}' یا عنوان '{$targetTitle}' یافت نشد";
                return;
            }
        }
        
        // ========== 2. پیدا کردن برنامه (در زیرمجموعه هدف) ==========
        if (!empty($formData['program']['code']) && $targetId !== null) {
            $programCode = $formData['program']['code'];
            $programTitle = $formData['program']['title'] ?? '';
            
            // **مهم: جستجوی برنامه با ترکیب target_id و code**
            $program = Program::where('target_id', $targetId)
                ->where('code', $programCode)
                ->first();
            
            // اگر با کد پیدا نشد، با عنوان در همان زیرمجموعه جستجو کن
            if (!$program && !empty($programTitle)) {
                $program = Program::where('target_id', $targetId)
                    ->where('title', 'like', '%' . $programTitle . '%')
                    ->first();
            }
            
            if ($program) {
                $programId = $program->id;
            } else {
                $this->errors[] = "برنامه با کد '{$programCode}' در زیرمجموعه هدف '{$formData['goal']['code']}' یافت نشد";
                return;
            }
        } elseif (!empty($formData['program']['code']) && $targetId === null) {
            $this->errors[] = "برای پیدا کردن برنامه، ابتدا باید هدف پیدا شود";
            return;
        }
        
        // ========== 3. پیدا کردن اقدام (اختیاری - در زیرمجموعه برنامه) ==========
        if (!empty($formData['task']['code']) && $programId !== null) {
            $taskCode = $formData['task']['code'];
            $taskTitle = $formData['task']['title'] ?? '';
            
            // جستجوی اقدام با ترکیب program_id و code
            $task = Task::where('program_id', $programId)
                ->where('code', $taskCode)
                ->first();
                
            if (!$task && !empty($taskTitle)) {
                $task = Task::where('program_id', $programId)
                    ->where('title', 'like', '%' . $taskTitle . '%')
                    ->first();
            }
            
            if ($task) {
                $taskId = $task->id;
            }
            // اگر اقدام پیدا نشد، خطا نمی‌دهیم چون اختیاری است
        }
        
        // ========== 4. پیدا کردن فعالیت (اختیاری) ==========
        if (!empty($formData['activity']['title'])) {
            $activity = Activity::where('title', 'like', '%' . $formData['activity']['title'] . '%')->first();
            if ($activity) {
                $activityId = $activity->id;
            }
        }
        
        // ========== بررسی نهایی فیلدهای اجباری ==========
        if ($targetId === null) {
            $this->errors[] = "نمی‌توان هدف را پیدا کرد. فیلد target_id اجباری است.";
        }
        if ($programId === null) {
            $this->errors[] = "نمی‌توان برنامه را پیدا کرد. فیلد program_id اجباری است.";
        }
        
        if (!empty($this->errors)) {
            error_log("خطاهای FormsImport: " . implode(', ', $this->errors));
            return;
        }
        
        // دیباگ: نمایش مقادیر پیدا شده
        error_log("مقادیر پیدا شده:");
        error_log("  targetId: " . ($targetId ?? 'null'));
        error_log("  programId: " . ($programId ?? 'null'));
        error_log("  taskId: " . ($taskId ?? 'null'));
        error_log("  activityId: " . ($activityId ?? 'null'));
        error_log("  formCode: " . $this->formCode);

        // ========== ایجاد کاربرگ ==========
        $user = auth()->user();
        $unitId = $user->unit_id ?? 1;

        // ایجاد description با فیلدهای ثابت
        $description = "فیلدهای ثابت:\n";
        $description .= "هدف: " . ($formData['goal']['text'] ?? '') . "\n";
        $description .= "برنامه: " . ($formData['program']['text'] ?? '') . "\n";
        $description .= "اقدام: " . ($formData['task']['text'] ?? '') . "\n";
        $description .= "فعالیت: " . ($formData['activity']['text'] ?? '') . "\n";

        try {
            error_log("ایجاد فرم با داده‌های زیر:");
            error_log("  code: " . $this->formCode);
            error_log("  unit_id: " . $unitId);
            error_log("  target_id: " . $targetId . " (نوع: " . gettype($targetId) . ")");
            error_log("  program_id: " . $programId . " (نوع: " . gettype($programId) . ")");
            error_log("  task_id: " . ($taskId ?? 'null') . " (نوع: " . gettype($taskId) . ")");
            error_log("  activity_id: " . ($activityId ?? 'null') . " (نوع: " . gettype($activityId) . ")");
            
            // تبدیل به integer اگر لازم است
            $targetIdInt = (int)$targetId;
            $programIdInt = (int)$programId;
            $taskIdInt = $taskId ? (int)$taskId : null;
            $activityIdInt = $activityId ? (int)$activityId : null;
            
            error_log("مقادیر بعد از تبدیل به integer:");
            error_log("  target_id: " . $targetIdInt);
            error_log("  program_id: " . $programIdInt);
            error_log("  task_id: " . ($taskIdInt ?? 'null'));
            error_log("  activity_id: " . ($activityIdInt ?? 'null'));
            
            // راه حل 1: استفاده از Eloquent با مقادیر integer
            $form = Form::create([
                'code' => $this->formCode,
                'unit_id' => $unitId,
                'target_id' => $targetIdInt,
                'program_id' => $programIdInt,
                'task_id' => $taskIdInt,
                'activity_id' => $activityIdInt,
                'description' => $description,
                'is_completed' => false,
                'created_by' => $user->username,
            ]);
            
            error_log("فرم با موفقیت ایجاد شد. ID: " . $form->id);
            
            // بررسی اینکه واقعاً در دیتابیس ذخیره شده
            $savedForm = Form::find($form->id);
            error_log("بررسی مقادیر ذخیره شده:");
            error_log("  target_id: " . $savedForm->target_id);
            error_log("  program_id: " . $savedForm->program_id);
            error_log("  task_id: " . ($savedForm->task_id ?? 'null'));
            error_log("  activity_id: " . ($savedForm->activity_id ?? 'null'));
            
        } catch (\Exception $e) {
            error_log("خطای Eloquent: " . $e->getMessage());
            
            // راه حل 2: استفاده از DB facade اگر Eloquent شکست خورد
            try {
                error_log("تلاش با DB facade...");
                $id = \DB::table('forms')->insertGetId([
                    'code' => $this->formCode,
                    'unit_id' => $unitId,
                    'target_id' => (int)$targetId,
                    'program_id' => (int)$programId,
                    'task_id' => $taskId ? (int)$taskId : null,
                    'activity_id' => $activityId ? (int)$activityId : null,
                    'description' => $description,
                    'is_completed' => false,
                    'created_by' => $user->username,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $form = Form::find($id);
                error_log("فرم با DB facade ایجاد شد. ID: " . $id);
                
                // بررسی
                $savedForm = Form::find($id);
                error_log("بررسی مقادیر ذخیره شده (DB facade):");
                error_log("  target_id: " . $savedForm->target_id);
                error_log("  program_id: " . $savedForm->program_id);
                error_log("  task_id: " . ($savedForm->task_id ?? 'null'));
                error_log("  activity_id: " . ($savedForm->activity_id ?? 'null'));
                
            } catch (\Exception $e2) {
                error_log("خطای DB facade: " . $e2->getMessage());
                $this->errors[] = "خطا در ایجاد فرم: " . $e2->getMessage();
                return;
            }
        }

        // ========== ایجاد فیلدهای متغیر و مقادیر ==========
        $sortOrder = 0;
        foreach ($headers as $colIndex => $header) {
            if ($colIndex === 0 && $header === 'ردیف') continue;
            
            $formField = FormField::create([
                'form_id' => $form->id,
                'field_label' => $header,
                'field_type' => 'text',
                'field_placeholder' => '',
                'is_required' => false,
                'sort_order' => $sortOrder,
            ]);

            if (isset($values[$header]) && !empty(trim($values[$header]))) {
                FormFieldValue::create([
                    'form_field_id' => $formField->id,
                    'form_id' => $form->id,
                    'field_value' => $values[$header],
                    'created_by' => $user->id,
                ]);
            }

            $sortOrder++;
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }
}