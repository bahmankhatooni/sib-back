<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * فیلدهای قابل پر کردن (mass assignment)
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'password',
        'email',
        'phone',
        'role_id',
        'unit_id',
        'is_active',
    ];

    /**
     * فیلدهایی که در آرایه‌ها مخفی می‌شوند
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * تبدیل نوع داده‌ها
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * رابطه با مدل Role: هر کاربر یک نقش دارد
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * رابطه با مدل Unit: هر کاربر به یک واحد تعلق دارد (اختیاری)
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * رابطه با FormFieldValue: هر کاربر چندین مقدار فیلد ثبت می‌کند
     */
    public function formFieldValues()
    {
        return $this->hasMany(FormFieldValue::class, 'created_by');
    }

    /**
     * دریافت نام کامل کاربر
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
