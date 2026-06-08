<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('targets', function (Blueprint $table) {
            $table->string('year', 4)->change();
        });
    }

    public function down(): void
    {
        Schema::table('targets', function (Blueprint $table) {
            $table->year('year')->change();
        });
    }
};
