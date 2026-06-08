<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_field_values', function (Blueprint $table) {
            if (!Schema::hasColumn('form_field_values', 'form_id')) {
                $table->foreignId('form_id')->after('form_field_id')->constrained('forms')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_field_values', function (Blueprint $table) {
            $table->dropForeign(['form_id']);
            $table->dropColumn('form_id');
        });
    }
};
