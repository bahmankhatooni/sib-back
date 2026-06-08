<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    /**
     * فیلدهای قابل پر کردن
     */
    protected $fillable = [
        'code', 'title', 'description', 'target_id', 'start_date',
        'end_date', 'budget', 'priority', 'progress', 'status', 'is_active'
    ];

    /**
     * تبدیل نوع داده‌ها
     */
    protected $casts = [
        'progress' => 'integer',
        'budget' => 'decimal:0',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * رابطه با Target (معکوس)
     */
    public function target()
    {
        return $this->belongsTo(Target::class);
    }

    /**
     * رابطه با Task: هر برنامه چندین اقدام دارد
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
