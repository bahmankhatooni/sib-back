<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // حذف فیلدهای قدیمی
            $table->dropColumn([
                'code',
                'due_date',
                'priority',
                'is_completed',
                'is_active'
            ]);

            // اضافه کردن فیلدهای جدید
            $table->string('indicator', 200)->nullable()->after('title')->comment('شاخص');
            $table->string('measure', 200)->nullable()->after('indicator')->comment('سنجه');
            $table->string('responsible', 100)->nullable()->after('measure')->comment('مجری');
            $table->string('collaborator', 100)->nullable()->after('responsible')->comment('همکار');
            $table->integer('progress')->default(0)->after('collaborator')->comment('درصد پیشرفت');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // حذف فیلدهای جدید
            $table->dropColumn([
                'indicator',
                'measure',
                'responsible',
                'collaborator',
                'progress'
            ]);

            // بازگرداندن فیلدهای قدیمی
            $table->string('code', 50)->unique()->after('id')->comment('کد فعالیت');
            $table->date('due_date')->nullable()->after('description')->comment('مهلت انجام');
            $table->enum('priority', ['بالا', 'متوسط', 'پایین'])->default('متوسط')->after('due_date')->comment('اولویت');
            $table->boolean('is_completed')->default(false)->after('priority')->comment('وضعیت انجام');
            $table->boolean('is_active')->default(true)->after('is_completed')->comment('وضعیت فعال');
        });
    }
};
