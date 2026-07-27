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
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'company_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    try {
                        $table->dropForeign(['company_id']);
                    } catch (\Throwable $e) {
                    }
                    $table->renameColumn('company_id', 'company_supervisors_id');
                });
            }
        }

        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'company_supervisors_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $onDelete = ($tableName === 'followups') ? 'nullOnDelete' : 'cascadeOnDelete';
                    if ($onDelete === 'nullOnDelete') {
                        $table->foreign('company_supervisors_id')->references('id')->on('company_supervisors')->nullOnDelete();
                    } else {
                        $table->foreign('company_supervisors_id')->references('id')->on('company_supervisors')->cascadeOnDelete();
                    }
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'company_supervisors_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    try {
                        $table->dropForeign(['company_supervisors_id']);
                    } catch (\Throwable $e) {
                    }
                    $table->renameColumn('company_supervisors_id', 'company_id');
                });
            }
        }

        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'company_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if ($tableName === 'followups') {
                        $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
                    } else {
                        $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                    }
                });
            }
        }
    }
};
