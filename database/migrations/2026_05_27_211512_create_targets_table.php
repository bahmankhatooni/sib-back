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
        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('کد هدف');
            $table->string('title', 200)->comment('عنوان هدف');
            $table->text('description')->nullable()->comment('توضیحات هدف');
            $table->year('year')->comment('سال اجرا');
            $table->enum('priority', ['بالا', 'متوسط', 'پایین'])->default('متوسط')->comment('اولویت هدف');
            $table->date('start_date')->nullable()->comment('تاریخ شروع');
            $table->date('end_date')->nullable()->comment('تاریخ پایان');
            $table->integer('progress')->default(0)->comment('درصد پیشرفت');
            $table->enum('status', ['در حال اجرا', 'نزدیک به اتمام', 'اتمام یافته', 'متوقف شده'])->default('در حال اجرا')->comment('وضعیت هدف');
            $table->boolean('is_active')->default(true)->comment('وضعیت فعال/غیرفعال');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};
