<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('worklogs', function (Blueprint $table) {
            if (Schema::hasColumn('worklogs', 'work_time')) {
                $table->string('work_time', 20)->nullable()->change();
            }
            if (Schema::hasColumn('worklogs', 'week_number')) {
                $table->integer('week_number')->nullable()->change();
            }
            if (Schema::hasColumn('worklogs', 'description')) {
                $table->text('description')->nullable()->change();
            }
            if (Schema::hasColumn('worklogs', 'submission_date')) {
                $table->date('submission_date')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('worklogs', function (Blueprint $table) {
            if (Schema::hasColumn('worklogs', 'work_time')) {
                $table->string('work_time', 10)->nullable()->change();
            }
            if (Schema::hasColumn('worklogs', 'week_number')) {
                $table->integer('week_number')->nullable(false)->change();
            }
            if (Schema::hasColumn('worklogs', 'description')) {
                $table->text('description')->nullable(false)->change();
            }
            if (Schema::hasColumn('worklogs', 'submission_date')) {
                $table->date('submission_date')->nullable(false)->change();
            }
        });
    }
};
