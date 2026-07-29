<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('worklogs', function (Blueprint $table) {
            if (!Schema::hasColumn('worklogs', 'work_date')) {
                $table->date('work_date')->nullable()->after('challenges');
            }
            if (!Schema::hasColumn('worklogs', 'work_time')) {
                $table->string('work_time', 10)->nullable()->after('work_date');
            }
            if (!Schema::hasColumn('worklogs', 'work_activities')) {
                $table->text('work_activities')->nullable()->after('work_time');
            }
            if (!Schema::hasColumn('worklogs', 'what_learned')) {
                $table->text('what_learned')->nullable()->after('work_activities');
            }
            if (!Schema::hasColumn('worklogs', 'difficulties')) {
                $table->text('difficulties')->nullable()->after('what_learned');
            }
            if (!Schema::hasColumn('worklogs', 'solutions')) {
                $table->text('solutions')->nullable()->after('difficulties');
            }
            if (!Schema::hasColumn('worklogs', 'to_do')) {
                $table->text('to_do')->nullable()->after('solutions');
            }
            if (!Schema::hasColumn('worklogs', 'comment')) {
                $table->text('comment')->nullable()->after('to_do');
            }
        });
    }

    public function down(): void
    {
        Schema::table('worklogs', function (Blueprint $table) {
            $table->dropColumn([
                'work_date', 'work_time', 'work_activities', 'what_learned',
                'difficulties', 'solutions', 'to_do', 'comment',
            ]);
        });
    }
};
