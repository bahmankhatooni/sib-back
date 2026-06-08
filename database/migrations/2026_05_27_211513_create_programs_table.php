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
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('کد برنامه');
            $table->string('title', 200)->comment('عنوان برنامه');
            $table->text('description')->nullable()->comment('توضیحات برنامه');
            $table->foreignId('target_id')->constrained('targets')->onDelete('cascade')->comment('ارجاع به هدف مرتبط');
            $table->date('start_date')->nullable()->comment('تاریخ شروع');
            $table->date('end_date')->nullable()->comment('تاریخ پایان');
            $table->decimal('budget', 15, 0)->nullable()->comment('بودجه (میلیون ریال)');
            $table->enum('priority', ['بالا', 'متوسط', 'پایین'])->default('متوسط')->comment('اولویت برنامه');
            $table->integer('progress')->default(0)->comment('درصد پیشرفت');
            $table->enum('status', ['برنامه‌ریزی شده', 'در حال اجرا', 'نزدیک به اتمام', 'اتمام یافته', 'متوقف شده'])->default('برنامه‌ریزی شده')->comment('وضعیت برنامه');
            $table->boolean('is_active')->default(true)->comment('وضعیت فعال/غیرفعال');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
