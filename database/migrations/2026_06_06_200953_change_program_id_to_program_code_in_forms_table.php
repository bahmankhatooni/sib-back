<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ابتدا بررسی می‌کنیم که ستون program_id وجود داشته باشد
        if (Schema::hasColumn('forms', 'program_id')) {
            // حذف کلید خارجی قبلی
            Schema::table('forms', function (Blueprint $table) {
                $table->dropForeign(['program_id']);
            });

            // تغییر نام ستون program_id به program_code
            Schema::table('forms', function (Blueprint $table) {
                $table->renameColumn('program_id', 'program_code');
            });

            // تغییر نوع ستون به string
            Schema::table('forms', function (Blueprint $table) {
                $table->string('program_code', 50)->change();
            });
        } else {
            // اگر ستون program_id وجود نداشت، ستون program_code را اضافه می‌کنیم
            Schema::table('forms', function (Blueprint $table) {
                $table->string('program_code', 50)->nullable()->after('target_id');
            });
        }

        // اضافه کردن کلید خارجی به جدول programs (ارجاع به فیلد code)
        Schema::table('forms', function (Blueprint $table) {
            $table->foreign('program_code')
                ->references('code')
                ->on('programs')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            // حذف کلید خارجی
            $table->dropForeign(['program_code']);

            // تغییر نام ستون program_code به program_id
            if (Schema::hasColumn('forms', 'program_code')) {
                $table->renameColumn('program_code', 'program_id');
                $table->unsignedBigInteger('program_id')->change();

                // بازگرداندن کلید خارجی قبلی
                $table->foreign('program_id')
                    ->references('id')
                    ->on('programs')
                    ->onDelete('cascade');
            }
        });
    }
};
