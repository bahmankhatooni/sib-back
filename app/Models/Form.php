<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory;

    protected $table = 'forms';

    protected $fillable = [
        'code',
        'unit_id',
        'target_id',
        'program_id',  // اصلاح: program_id نه program_code
        'task_id',
        'activity_id',
        'description',
        'is_completed',
        'created_by'
    ];

    protected $casts = [
        'unit_id' => 'integer',
        'target_id' => 'integer',
        'program_id' => 'integer',  // اضافه کردن program_id
        'task_id' => 'integer',
        'activity_id' => 'integer',
        'is_completed' => 'boolean',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function target()
    {
        return $this->belongsTo(Target::class);
    }

    // اصلاح رابطه program - استفاده از program_id
    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function formFields()
    {
        return $this->hasMany(FormField::class, 'form_id');
    }

    public function formFieldValues()
    {
        return $this->hasMany(FormFieldValue::class, 'form_id');
    }
}
