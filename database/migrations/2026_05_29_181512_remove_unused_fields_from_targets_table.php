<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('targets', function (Blueprint $table) {
            $table->dropColumn([
                'priority',
                'start_date',
                'end_date',
                'status'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('targets', function (Blueprint $table) {
            $table->enum('priority', ['بالا', 'متوسط', 'پایین'])->default('متوسط')->after('year');
            $table->date('start_date')->nullable()->after('priority');
            $table->date('end_date')->nullable()->after('start_date');
            $table->enum('status', ['در حال اجرا', 'نزدیک به اتمام', 'اتمام یافته', 'متوقف شده'])->default('در حال اجرا')->after('end_date');
        });
    }
};
