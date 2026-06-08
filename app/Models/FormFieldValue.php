<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormFieldValue extends Model
{
    use HasFactory;

    protected $table = 'form_field_values';

    protected $fillable = [
        'form_field_id',
        'form_id',
        'field_value',
        'created_by'
    ];

    public function formField()
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
