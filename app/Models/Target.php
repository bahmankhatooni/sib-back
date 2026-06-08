<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    use HasFactory;

    /**
     * فیلدهای قابل پر کردن
     */
    protected $fillable = [
        'code', 'title', 'description', 'year', 'priority',
        'start_date', 'end_date', 'progress', 'status', 'is_active'
    ];

    /**
     * تبدیل نوع داده‌ها
     */
    protected $casts = [
        'progress' => 'integer',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * رابطه با Program: هر هدف چندین برنامه دارد
     */
    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
