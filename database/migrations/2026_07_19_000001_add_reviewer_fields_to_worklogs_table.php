<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('worklogs', function (Blueprint $table) {
            if (!Schema::hasColumn('worklogs', 'reviewer_id')) {
                $table->foreignId('reviewer_id')->nullable()->after('feedback')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('worklogs', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewer_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('worklogs', function (Blueprint $table) {
            if (Schema::hasColumn('worklogs', 'reviewer_id')) {
                $table->dropForeign(['reviewer_id']);
                $table->dropColumn('reviewer_id');
            }
            if (Schema::hasColumn('worklogs', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
        });
    }
};

