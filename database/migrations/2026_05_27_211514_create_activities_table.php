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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('کد فعالیت');
            $table->string('title', 200)->comment('عنوان فعالیت');
            $table->text('description')->nullable()->comment('توضیحات فعالیت');
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade')->comment('ارجاع به اقدام مرتبط');
            $table->date('due_date')->nullable()->comment('مهلت انجام فعالیت');
            $table->enum('priority', ['بالا', 'متوسط', 'پایین'])->default('متوسط')->comment('اولویت فعالیت');
            $table->boolean('is_completed')->default(false)->comment('وضعیت انجام شده/انجام نشده');
            $table->boolean('is_active')->default(true)->comment('وضعیت فعال/غیرفعال');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
