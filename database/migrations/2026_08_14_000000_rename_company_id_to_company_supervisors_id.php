<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'company_feedback',
        'evaluations',
        'followups',
        'internship_assignments',
    ];

    public function up(): void
    {
        // Step 1: Rename company_id → company_supervisors_id where needed
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'company_id')) {
                    // Drop old FK (safe — no-op if already gone)
                    try {
                        $table->dropForeign(['company_id']);
                    } catch (\Throwable $e) {
                        // FK may not exist
                    }
                    $table->renameColumn('company_id', 'company_supervisors_id');
                }
            });
        }

        // Step 2: Ensure new FK constraints exist (idempotent)
        // Use raw SQL to drop FK first, because $table->dropForeign() inside a
        // Schema::table closure only queues the command — the actual SQL runs
        // after the closure returns, so a try-catch inside the closure cannot
        // catch PDO exceptions from the DROP FOREIGN KEY statement.
        $fkTables = [
            'company_feedback'       => 'cascade',
            'evaluations'            => 'cascade',
            'followups'              => 'null',
            'internship_assignments' => 'cascade',
        ];

        foreach ($fkTables as $tableName => $deleteMode) {
            if (!Schema::hasColumn($tableName, 'company_supervisors_id')) {
                continue;
            }

            // Drop existing FK if it was already added in a previous partial run
            $fkName = $tableName . '_company_supervisors_id_foreign';
            try {
                DB::statement("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$fkName}`");
            } catch (\Throwable $e) {
                // FK doesn't exist — that's fine
            }

            // Now add the FK safely
            Schema::table($tableName, function (Blueprint $table) use ($deleteMode) {
                $foreign = $table->foreign('company_supervisors_id')
                    ->references('id')
                    ->on('company_supervisors');
                if ($deleteMode === 'null') {
                    $foreign->nullOnDelete();
                } else {
                    $foreign->cascadeOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        // Step 1: Restore column name company_supervisors_id → company_id
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'company_supervisors_id')) {
                    // Drop FK on company_supervisors_id first
                    try {
                        $table->dropForeign(['company_supervisors_id']);
                    } catch (\Throwable $e) {
                        // FK may not exist
                    }
                    $table->renameColumn('company_supervisors_id', 'company_id');
                }
            });
        }

        // Step 2: Restore original FKs referencing companies (idempotent)
        $restoreTables = [
            'company_feedback'       => 'cascade',
            'evaluations'            => 'cascade',
            'followups'              => 'null',
            'internship_assignments' => 'cascade',
        ];

        foreach ($restoreTables as $tableName => $deleteMode) {
            if (!Schema::hasColumn($tableName, 'company_id')) {
                continue;
            }

            // Drop any lingering FK on company_id first (raw SQL to avoid deferred-execution issue)
            $fkName = $tableName . '_company_id_foreign';
            try {
                DB::statement("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$fkName}`");
            } catch (\Throwable $e) {
                // FK doesn't exist — that's fine
            }

            // Restore the original FK to companies
            Schema::table($tableName, function (Blueprint $table) use ($deleteMode) {
                $foreign = $table->foreign('company_id')->references('id')->on('companies');
                if ($deleteMode === 'null') {
                    $foreign->nullOnDelete();
                } else {
                    $foreign->cascadeOnDelete();
                }
            });
        }
    }
};
