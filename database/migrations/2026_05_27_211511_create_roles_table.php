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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('کد نقش (مثال: ADMIN, UNIT_USER)');
            $table->string('name', 100)->comment('نام نقش');
            $table->enum('type', ['مدیریتی', 'کاربری', 'نظارتی', 'اجرایی'])->default('کاربری')->comment('نوع نقش');
            $table->text('description')->nullable()->comment('توضیحات نقش');
            $table->boolean('is_active')->default(true)->comment('وضعیت فعال/غیرفعال');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
