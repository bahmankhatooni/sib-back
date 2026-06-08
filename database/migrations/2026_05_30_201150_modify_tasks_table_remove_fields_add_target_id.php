<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // اضافه کردن فیلد target_id
            $table->foreignId('target_id')->nullable()->after('program_id')->constrained('targets')->onDelete('cascade');

            // حذف فیلدهای غیرضروری
            $table->dropColumn([
                'end_date',
                'progress',
                'status'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // حذف فیلد target_id
            $table->dropForeign(['target_id']);
            $table->dropColumn('target_id');

            // بازگرداندن فیلدهای حذف شده
            $table->date('end_date')->nullable()->after('collaborator');
            $table->integer('progress')->default(0)->after('end_date');
            $table->enum('status', ['برنامه‌ریزی شده', 'در حال اجرا', 'نزدیک به اتمام', 'اتمام یافته', 'متوقف شده'])->default('برنامه‌ریزی شده')->after('progress');
        });
    }
};
