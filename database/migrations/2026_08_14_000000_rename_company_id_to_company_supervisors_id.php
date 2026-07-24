<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'company_feedback',
        'company_messages',
        'evaluations',
        'followups',
        'internship_assignments',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                // Drop existing FK
                $table->dropForeign(['company_id']);
                // Rename column
                $table->renameColumn('company_id', 'company_supervisors_id');
            });
        }

        // Add new FK constraints referencing company_supervisors
        Schema::table('company_feedback', function (Blueprint $table) {
            $table->foreign('company_supervisors_id')->references('id')->on('company_supervisors')->cascadeOnDelete();
        });
        Schema::table('company_messages', function (Blueprint $table) {
            $table->foreign('company_supervisors_id')->references('id')->on('company_supervisors')->cascadeOnDelete();
        });
        Schema::table('evaluations', function (Blueprint $table) {
            $table->foreign('company_supervisors_id')->references('id')->on('company_supervisors')->cascadeOnDelete();
        });
        Schema::table('followups', function (Blueprint $table) {
            $table->foreign('company_supervisors_id')->references('id')->on('company_supervisors')->nullOnDelete();
        });
        Schema::table('internship_assignments', function (Blueprint $table) {
            $table->foreign('company_supervisors_id')->references('id')->on('company_supervisors')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['company_supervisors_id']);
                $table->renameColumn('company_supervisors_id', 'company_id');
            });
        }

        // Restore original FKs
        Schema::table('company_feedback', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
        Schema::table('company_messages', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
        Schema::table('evaluations', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
        Schema::table('followups', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });
        Schema::table('internship_assignments', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
    }
};
