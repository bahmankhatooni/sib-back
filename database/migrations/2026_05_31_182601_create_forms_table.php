<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('شماره کاربرگ/کد');
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade')->comment('ارجاع به واحد');
            $table->foreignId('target_id')->constrained('targets')->onDelete('cascade')->comment('ارجاع به هدف');
            $table->foreignId('program_id')->constrained('programs')->onDelete('cascade')->comment('ارجاع به برنامه');
            $table->foreignId('task_id')->nullable()->constrained('tasks')->onDelete('cascade')->comment('ارجاع به اقدام (اختیاری)');
            $table->foreignId('activity_id')->nullable()->constrained('activities')->onDelete('cascade')->comment('ارجاع به فعالیت (اختیاری)');
            $table->text('description')->nullable()->comment('توضیحات');
            $table->string('created_by')->nullable()->comment('ایجاد کننده');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
