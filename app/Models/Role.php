<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * فیلدهای قابل پر کردن (mass assignment)
     */
    protected $fillable = [
        'code', 'name', 'type', 'description', 'is_active'
    ];

    /**
     * تبدیل نوع داده‌ها
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * رابطه با مدل User: یک نقش چندین کاربر دارد
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
