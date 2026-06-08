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
        Schema::create('form_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_field_id')->constrained('form_fields')->onDelete('cascade')->comment('ارجاع به فیلد فرم');
            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade')->comment('ارجاع به فعالیت');
            $table->text('field_value')->nullable()->comment('مقدار ذخیره شده فیلد');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict')->comment('کاربر ثبت‌کننده مقدار');
            $table->timestamps();

            // هر فعالیت فقط یک مقدار برای هر فیلد داشته باشد
            $table->unique(['form_field_id', 'activity_id'], 'unique_activity_field');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_field_values');
    }
};
