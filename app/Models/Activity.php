<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    /**
     * فیلدهای قابل پر کردن
     */
    protected $fillable = [
        'title',
        'description',
        'task_id',
        'indicator',
        'measure',
        'responsible',
        'collaborator',
        'progress'
    ];

    /**
     * تبدیل نوع داده‌ها
     */
    protected $casts = [
        'progress' => 'integer',
    ];

    /**
     * رابطه با Task (معکوس)
     * هر فعالیت متعلق به یک اقدام است
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
