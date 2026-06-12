<?php

namespace App\Exports;

use App\Models\Form;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class FormsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStrictNullComparison, WithEvents
{
    protected $formId;

    public function __construct($formId)
    {
        $this->formId = $formId;
    }

    public function collection()
    {
        $form = Form::with(['unit', 'target', 'program', 'task', 'activity', 'formFields'])
            ->find($this->formId);

        if (!$form) {
            return collect([]);
        }

        // دریافت تمام فیلدها
        $fields = $form->formFields->sortBy('sort_order');
        $fieldCount = $fields->count();
        $totalColumns = max(1, $fieldCount + 1); // +1 برای ستون ردیف
        
        // دریافت تمام مقادیر گروه‌بندی شده بر اساس row_index
        $fieldValues = \App\Models\FormFieldValue::where('form_id', $this->formId)
            ->orderBy('row_index')
            ->get()
            ->groupBy('row_index');
        
        $data = [];
        
        // ردیف 1: عنوان هدف
        $row1 = array_fill(0, $totalColumns, '');
        $targetTitle = $form->target ? $form->target->title : '';
        $targetCode = $form->target ? $form->target->code : '';
        $row1[0] = 'هدف ' . $targetCode . ' : ' . $targetTitle;
        $data[] = $row1;
        
        // ردیف 2: عنوان برنامه
        $row2 = array_fill(0, $totalColumns, '');
        $programTitle = $form->program ? $form->program->title : '';
        $programCode = $form->program ? $form->program->code : '';
        $row2[0] = 'برنامه ' . $programCode . ' : ' . $programTitle;
        $data[] = $row2;
        
        // ردیف 3: شماره و عنوان اقدام
        $row3 = array_fill(0, $totalColumns, '');
        $taskTitle = $form->task ? $form->task->title : '';
        $taskCode = $form->task ? $form->task->code : '';
        $row3[0] = 'اقدام ' . $taskCode . ' : ' . $taskTitle;
        $data[] = $row3;
        
        // ردیف 4: شماره و عنوان فعالیت
        $row4 = array_fill(0, $totalColumns, '');
        $activityTitle = $form->activity ? $form->activity->title : '';
        $activityCode = $form->activity ? $form->activity->code : '';
        
        if ($activityCode) {
            $row4[0] = 'فعالیت ' . $activityCode . ' : ' . $activityTitle;
        } else {
            // اگر کد نداشت، سعی کن از عنوان استخراج کنی
            if (preg_match('/^(\d+)/', $activityTitle, $matches)) {
                $activityNumber = $matches[1];
                $activityTitle = trim(substr($activityTitle, strlen($activityNumber)));
                $row4[0] = 'فعالیت ' . $activityNumber . ' : ' . $activityTitle;
            } else {
                $row4[0] = 'فعالیت : ' . $activityTitle;
            }
        }
        $data[] = $row4;
        
        // ردیف 5: "ردیف" (خالی)
        $row5 = array_fill(0, $totalColumns, '');
        $row5[0] = 'ردیف';
        $data[] = $row5;
        
        // ردیف 6: عناوین فیلدهای متغیر
        $row6 = ['ردیف']; // ستون A
        foreach ($fields as $field) {
            $row6[] = $field->field_label;
        }
        // پر کردن بقیه ستون‌ها با خالی
        while (count($row6) < $totalColumns) {
            $row6[] = '';
        }
        $data[] = $row6;
        
        // ردیف 7 به بعد: مقادیر (هر ردیف داده)
        if ($fieldValues->isEmpty()) {
            // اگر مقدار نداشت، یک ردیف خالی بساز
            $emptyRow = [1]; // شماره ردیف
            foreach ($fields as $field) {
                $emptyRow[] = '';
            }
            while (count($emptyRow) < $totalColumns) {
                $emptyRow[] = '';
            }
            $data[] = $emptyRow;
        } else {
            // برای هر row_index یک ردیف ایجاد کن
            $rowNumber = 1;
            foreach ($fieldValues as $rowIndex => $rowValues) {
                $dataRow = [$rowNumber]; // شماره ردیف
                
                foreach ($fields as $field) {
                    $value = $rowValues->firstWhere('form_field_id', $field->id);
                    $dataRow[] = $value ? $value->field_value : '';
                }
                
                // پر کردن بقیه ستون‌ها با خالی
                while (count($dataRow) < $totalColumns) {
                    $dataRow[] = '';
                }
                
                $data[] = $dataRow;
                $rowNumber++;
            }
        }

        return collect($data);
    }

    public function headings(): array
    {
        // هدرهای خاصی نیاز نیست
        return [];
    }

    public function map($row): array
    {
        return $row;
    }
    
    /**
     * رویدادهای پس از تولید sheet
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // تعداد ستون‌ها و ردیف‌ها
                $highestColumn = $sheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                $highestRow = $sheet->getHighestRow();
                
                // ادغام سلول‌های ردیف‌های 1 تا 5 (هدف، برنامه، اقدام، فعالیت، "ردیف")
                for ($row = 1; $row <= 5; $row++) {
                    $sheet->mergeCells("A{$row}:{$highestColumn}{$row}");
                    
                    // استایل: مرکز چین، بولد، بوردر
                    $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'font' => [
                            'bold' => true,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                    ]);
                }
                
                // استایل ردیف 6 (عناوین فیلدها)
                $sheet->getStyle("A6:{$highestColumn}6")->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'font' => [
                        'bold' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['argb' => 'FFE0E0E0'],
                    ],
                ]);
                
                // استایل ردیف‌های 7 به بعد (مقادیر)
                if ($highestRow >= 7) {
                    $sheet->getStyle("A7:{$highestColumn}{$highestRow}")->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                    ]);
                }
                
                // تنظیم عرض ستون‌ها
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->getColumnDimension($columnLetter)->setWidth(20);
                }
                
                // ستون اول (ردیف) کمی باریک‌تر
                $sheet->getColumnDimension('A')->setWidth(10);
            },
        ];
    }
}