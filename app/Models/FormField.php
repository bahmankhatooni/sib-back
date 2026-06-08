<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    use HasFactory;

    protected $table = 'form_fields';

    protected $fillable = [
        'form_id',
        'field_label',
        'field_type',
        'field_placeholder',
        'field_options',
        'is_required',
        'sort_order'
    ];

    protected $casts = [
        'field_options' => 'array',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function values()
    {
        return $this->hasMany(FormFieldValue::class, 'form_field_id');
    }
}
