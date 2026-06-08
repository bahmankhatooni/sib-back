<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            // اگر ستون form_id وجود ندارد، اضافه کن
            if (!Schema::hasColumn('form_fields', 'form_id')) {
                $table->foreignId('form_id')->after('id')->constrained('forms')->onDelete('cascade');
            }

            // اگر ستون activity_id وجود دارد (از قبل)، آن را حذف کن
            if (Schema::hasColumn('form_fields', 'activity_id')) {
                $table->dropForeign(['activity_id']);
                $table->dropColumn('activity_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropForeign(['form_id']);
            $table->dropColumn('form_id');

            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
        });
    }
};
