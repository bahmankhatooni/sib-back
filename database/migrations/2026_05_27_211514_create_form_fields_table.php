<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade')->comment('ارجاع به فعالیت مرتبط');
            $table->string('field_label', 100)->comment('عنوان/برچسب فیلد (مثال: نام پروژه)');
            $table->string('field_type', 30)->comment('نوع فیلد (text, number, date, select, textarea, checkbox)');
            $table->string('field_placeholder', 200)->nullable()->comment('متن راهنما در فیلد');
            $table->text('field_options')->nullable()->comment('گزینه‌های فیلد select (ذخیره به صورت JSON)');
            $table->boolean('is_required')->default(false)->comment('الزامی بودن فیلد');
            $table->integer('sort_order')->default(0)->comment('ترتیب نمایش فیلدها');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
