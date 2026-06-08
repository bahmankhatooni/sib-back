<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    /**
     * فیلدهای قابل پر کردن (mass assignment)
     */
    protected $fillable = [
        'code',
        'title',
        'program_id',
        'target_id',
        'activity',
        'is_active'
    ];

    /**
     * تبدیل نوع داده‌ها
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * رابطه با مدل Program (معکوس)
     * هر اقدام متعلق به یک برنامه است
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * رابطه با مدل Target (معکوس)
     * هر اقدام متعلق به یک هدف است
     */
    public function target()
    {
        return $this->belongsTo(Target::class);
    }

    /**
     * رابطه با مدل Activity
     * هر اقدام چندین فعالیت دارد
     */
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
}
