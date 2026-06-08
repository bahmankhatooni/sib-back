<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->enum('status', ['برنامه‌ریزی شده', 'در حال اجرا', 'نزدیک به اتمام', 'اتمام یافته', 'متوقف شده'])->default('برنامه‌ریزی شده')->after('progress');
        });
    }
};
