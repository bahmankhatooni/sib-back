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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 50)->comment('نام');
            $table->string('last_name', 50)->comment('نام خانوادگی');
            $table->string('username', 50)->unique()->comment('نام کاربری (منحصر به فرد)');
            $table->string('password')->comment('رمز عبور (هش شده)');
            $table->string('email', 100)->nullable()->unique()->comment('ایمیل');
            $table->string('phone', 20)->nullable()->comment('شماره تماس');
            $table->foreignId('role_id')->constrained('roles')->onDelete('restrict')->comment('ارجاع به نقش کاربر');
            $table->foreignId('unit_id')->nullable()->constrained('units')->onDelete('set null')->comment('ارجاع به واحد کاربر (در صورت وجود)');
            $table->boolean('is_active')->default(true)->comment('وضعیت فعال/غیرفعال');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
