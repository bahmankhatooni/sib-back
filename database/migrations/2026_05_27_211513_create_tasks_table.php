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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('کد اقدام');
            $table->string('title', 200)->comment('عنوان اقدام');
            $table->text('description')->nullable()->comment('توضیحات اقدام');
            $table->foreignId('program_id')->constrained('programs')->onDelete('cascade')->comment('ارجاع به برنامه مرتبط');
            $table->string('activity', 200)->nullable()->comment('فعالیت مرتبط');
            $table->string('indicator', 200)->nullable()->comment('شاخص اندازه‌گیری');
            $table->string('measure', 200)->nullable()->comment('سنجه');
            $table->string('responsible', 100)->nullable()->comment('مجری');
            $table->string('collaborator', 100)->nullable()->comment('همکار');
            $table->date('end_date')->nullable()->comment('تاریخ پایان');
            $table->integer('progress')->default(0)->comment('درصد پیشرفت');
            $table->enum('status', ['برنامه‌ریزی شده', 'در حال اجرا', 'نزدیک به اتمام', 'اتمام یافته', 'متوقف شده'])->default('برنامه‌ریزی شده')->comment('وضعیت اقدام');
            $table->boolean('is_active')->default(true)->comment('وضعیت فعال/غیرفعال');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
