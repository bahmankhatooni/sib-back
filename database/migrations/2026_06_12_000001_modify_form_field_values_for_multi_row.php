<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // فقط ستون‌های مورد نیاز را اضافه می‌کنیم
        Schema::table('form_field_values', function (Blueprint $table) {
            // بررسی و اضافه کردن form_id
            if (!Schema::hasColumn('form_field_values', 'form_id')) {
                $table->unsignedBigInteger('form_id')->after('form_field_id');
                $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
            }
            
            // بررسی و اضافه کردن row_index
            if (!Schema::hasColumn('form_field_values', 'row_index')) {
                $table->integer('row_index')->default(0)->after('form_id')->comment('شماره ردیف مقادیر');
            }
        });
        
        // بررسی و اضافه کردن unique constraint
        $indexes = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'form_field_values' 
            AND CONSTRAINT_NAME = 'unique_field_form_row'
        ");
        
        if (empty($indexes)) {
            Schema::table('form_field_values', function (Blueprint $table) {
                $table->unique(['form_field_id', 'form_id', 'row_index'], 'unique_field_form_row');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_field_values', function (Blueprint $table) {
            // حذف unique constraint
            try {
                $table->dropUnique('unique_field_form_row');
            } catch (\Exception $e) {}
            
            // حذف ستون‌های اضافه شده
            if (Schema::hasColumn('form_field_values', 'form_id')) {
                $table->dropForeign(['form_id']);
                $table->dropColumn('form_id');
            }
            
            if (Schema::hasColumn('form_field_values', 'row_index')) {
                $table->dropColumn('row_index');
            }
        });
    }
};
