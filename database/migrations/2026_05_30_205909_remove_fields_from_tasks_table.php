<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'indicator',
                'measure',
                'responsible',
                'collaborator',
                'description'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('indicator', 200)->nullable()->after('activity');
            $table->string('measure', 200)->nullable()->after('indicator');
            $table->string('responsible', 100)->nullable()->after('measure');
            $table->string('collaborator', 100)->nullable()->after('responsible');
            $table->text('description')->nullable()->after('collaborator');
        });
    }
};
