<?php

namespace App\Exports;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldValue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStrictNullComparison, WithEvents
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Form::with(['unit', 'target', 'program', 'task', 'activity', 'formFields', 'formFieldValues']);

        // اعمال فیلترها
        if (!$this->filters['is_admin'] && isset($this->filters['unit_id'])) {
            $query->where('unit_id', $this->filters['unit_id']);
        }

        if ($this->filters['is_admin'] && isset($this->filters['unit_id']) && !empty($this->filters['unit_id'])) {
            $query->where('unit_id', $this->filters['unit_id']);
        }

        if (isset($this->filters['target_id']) && !empty($this->filters['target_id'])) {
            $query->where('target_id', $this->filters['target_id']);
        }

        if (isset($this->filters['program_id']) && !empty($this->filters['program_id'])) {
            $query->where('program_id', $this->filters['program_id']);
        }

        if (isset($this->filters['task_id']) && !empty($this->filters['task_id'])) {
            $query->where('task_id', $this->filters['task_id']);
        }

        if (isset($this->filters['activity_id']) && !empty($this->filters['activity_id'])) {
            $query->where('activity_id', $this->filters['activity_id']);
        }

        if (isset($this->filters['is_completed']) && $this->filters['is_completed'] !== null && $this->filters['is_completed'] !== '') {
            $query->where('is_completed', $this->filters['is_completed']);
        }

        if (isset($this->filters['search']) && !empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'کد کاربرگ',
            'واحد',
            'کد هدف',
            'عنوان هدف',
            'کد برنامه',
            'عنوان برنامه',
            'کد اقدام',
            'عنوان اقدام',
            'عنوان فعالیت',
            'وضعیت',
            'تاریخ ایجاد',
            'ایجاد کننده',
            'فیلدهای متغیر و مقادیر',
        ];
    }

    public function map($form): array
    {
        // ساخت رشته فیلدهای متغیر و مقادیر
        $fieldsString = '';
        $fields = $form->formFields->sortBy('sort_order');
        
        foreach ($fields as $field) {
            $value = $form->formFieldValues->where('form_field_id', $field->id)->first();
            $fieldValue = $value ? $value->field_value : '-';
            $fieldsString .= $field->field_label . ': ' . $fieldValue . ' | ';
        }
        
        $fieldsString = rtrim($fieldsString, ' | ');

        return [
            $form->code,
            $form->unit ? $form->unit->name : '-',
            $form->target ? $form->target->code : '-',
            $form->target ? $form->target->title : '-',
            $form->program ? $form->program->code : '-',
            $form->program ? $form->program->title : '-',
            $form->task ? $form->task->code : '-',
            $form->task ? $form->task->title : '-',
            $form->activity ? $form->activity->title : '-',
            $form->is_completed ? 'تکمیل شده' : 'در انتظار تکمیل',
            $form->created_at ? $form->created_at->format('Y-m-d H:i:s') : '-',
            $form->created_by ?? '-',
            $fieldsString,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // تعداد ردیف‌ها
                $highestRow = $sheet->getHighestRow();
                
                // استایل هدر (ردیف اول)
                $sheet->getStyle('A1:M1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['argb' => 'FF4CAF50'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);
                
                // استایل محتوا
                $sheet->getStyle('A2:M' . $highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                
                // تنظیم عرض ستون‌ها
                $sheet->getColumnDimension('A')->setWidth(15); // کد کاربرگ
                $sheet->getColumnDimension('B')->setWidth(20); // واحد
                $sheet->getColumnDimension('C')->setWidth(12); // کد هدف
                $sheet->getColumnDimension('D')->setWidth(30); // عنوان هدف
                $sheet->getColumnDimension('E')->setWidth(12); // کد برنامه
                $sheet->getColumnDimension('F')->setWidth(30); // عنوان برنامه
                $sheet->getColumnDimension('G')->setWidth(12); // کد اقدام
                $sheet->getColumnDimension('H')->setWidth(30); // عنوان اقدام
                $sheet->getColumnDimension('I')->setWidth(30); // عنوان فعالیت
                $sheet->getColumnDimension('J')->setWidth(18); // وضعیت
                $sheet->getColumnDimension('K')->setWidth(20); // تاریخ ایجاد
                $sheet->getColumnDimension('L')->setWidth(15); // ایجاد کننده
                $sheet->getColumnDimension('M')->setWidth(50); // فیلدهای متغیر
                
                // تنظیم ارتفاع ردیف هدر
                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
