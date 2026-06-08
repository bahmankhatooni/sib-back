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
        $form = Form::with(['unit', 'target', 'program', 'task', 'activity', 'formFields', 'formFieldValues'])
            ->find($this->formId);

        if (!$form) {
            return collect([]);
        }

        // تعداد فیلدهای متغیر
        $fieldCount = $form->formFields->count();
        $totalColumns = max(1, $fieldCount); // حداقل یک ستون
        
        // ساختار داده‌ها: هر ردیف یک آرایه از مقادیر
        $data = [];
        
        // ردیف 1: عنوان هدف (همه سلول‌ها ادغام می‌شوند)
        $row1 = array_fill(0, $totalColumns, '');
        $targetTitle = $form->target ? $form->target->title : '';
        $targetCode = $form->target ? $form->target->code : '';
        $row1[0] = 'هدف ' . $targetCode . ' : ' . $targetTitle;
        $data[] = $row1;
        
        // ردیف 2: عنوان برنامه (همه سلول‌ها ادغام می‌شوند)
        $row2 = array_fill(0, $totalColumns, '');
        $programTitle = $form->program ? $form->program->title : '';
        $programCode = $form->program ? $form->program->code : '';
        $row2[0] = 'برنامه ' . $programCode . ' : ' . $programTitle;
        $data[] = $row2;
        
        // ردیف 3: شماره و عنوان اقدام (همه سلول‌ها ادغام می‌شوند)
        $row3 = array_fill(0, $totalColumns, '');
        $taskTitle = $form->task ? $form->task->title : '';
        $taskCode = $form->task ? $form->task->code : '';
        $row3[0] = 'اقدام ' . $taskCode . ' : ' . $taskTitle;
        $data[] = $row3;
        
        // ردیف 4: شماره و عنوان فعالیت (همه سلول‌ها ادغام می‌شوند)
        $activityTitle = $form->activity ? $form->activity->title : '';
        $activityNumber = '';
        if (preg_match('/^(\d+)/', $activityTitle, $matches)) {
            $activityNumber = $matches[1];
            $activityTitle = trim(substr($activityTitle, strlen($activityNumber)));
        } else {
            // اگر شماره در عنوان نبود، از کد task استفاده می‌کنیم
            $activityNumber = $taskCode;
        }
        $row4 = array_fill(0, $totalColumns, '');
        $row4[0] = 'فعالیت ' . $activityNumber . ' : ' . $activityTitle;
        $data[] = $row4;
        
        // ردیف 5: عناوین فیلدهای متغیر
        $row5 = ['ردیف']; // ستون A
        $row6 = [1]; // ستون A (مقدار ردیف)
        
        // فیلدهای متغیر از ستون B به بعد
        $fieldIndex = 0;
        foreach ($form->formFields->sortBy('sort_order') as $field) {
            $row5[] = $field->field_label;
            
            // پیدا کردن مقدار فیلد
            $value = $form->formFieldValues->where('form_field_id', $field->id)->first();
            $row6[] = $value ? $value->field_value : '';
            
            $fieldIndex++;
        }
        
        // اگر تعداد فیلدها کمتر از totalColumns بود، بقیه سلول‌ها را خالی می‌گذاریم
        while (count($row5) < $totalColumns) {
            $row5[] = '';
            $row6[] = '';
        }
        
        $data[] = $row5;
        $data[] = $row6;

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
                
                // تعداد ستون‌ها
                $highestColumn = $sheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                
                // ادغام سلول‌های ردیف‌های 1 تا 4
                for ($row = 1; $row <= 4; $row++) {
                    $sheet->mergeCells("A{$row}:{$highestColumn}{$row}");
                    
                    // مرکز چین کردن متن
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
                
                // استایل ردیف 5 (هدر فیلدها)
                $sheet->getStyle("A5:{$highestColumn}5")->applyFromArray([
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
                
                // استایل ردیف 6 (مقادیر فیلدها)
                $sheet->getStyle("A6:{$highestColumn}6")->applyFromArray([
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
                
                // تنظیم عرض ستون‌ها
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->getColumnDimension($columnLetter)->setWidth(20);
                }
            },
        ];
    }
}