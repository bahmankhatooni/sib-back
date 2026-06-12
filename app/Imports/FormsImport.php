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
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;

class FormsImport implements ToCollection, WithStartRow
{
    protected $formCode;
    protected $errors = [];

    public function __construct($formCode)
    {
        $this->formCode = $formCode;
    }

    public function startRow(): int
    {
        return 1;
    }

    public function collection(Collection $rows)
    {
        // این کلاس فقط برای سازگاری است
        // منطق اصلی import در FormController->processSheet پیاده‌سازی شده
        // چون نیاز به دسترسی مستقیم به شیت‌های فایل داریم
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
