<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            if (!Schema::hasColumn('issues', 'due_date')) {
                $table->date('due_date')->nullable()->after('priority');
            }
            if (!Schema::hasColumn('issues', 'assigned_user_id')) {
                $table->foreignId('assigned_user_id')->nullable()->after('tutor_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('issues', 'reporter_id')) {
                $table->foreignId('reporter_id')->nullable()->after('student_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            if (Schema::hasColumn('issues', 'due_date')) {
                $table->dropColumn('due_date');
            }
            if (Schema::hasColumn('issues', 'assigned_user_id')) {
                $table->dropForeign(['assigned_user_id']);
                $table->dropColumn('assigned_user_id');
            }
            if (Schema::hasColumn('issues', 'reporter_id')) {
                $table->dropForeign(['reporter_id']);
                $table->dropColumn('reporter_id');
            }
        });
    }
};

