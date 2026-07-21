<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // We only modify the enum for MySQL-like databases.
        // Tests use SQLite, and SQLite doesn't support altering enum definitions this way.
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        $table = 'worklogs';
        $column = 'status';

        if (!Schema::hasColumn($table, $column)) {
            return;
        }

        // Current enum: Draft, Submitted, Approved, Rejected
        // Controller expects: Approved, Rejected, Reviewed, Pending
        DB::statement("ALTER TABLE {$table} MODIFY {$column} ENUM('Draft','Submitted','Approved','Rejected','Reviewed','Pending') DEFAULT 'Draft'");

    }

    public function down(): void
    {
        $table = 'worklogs';
        $column = 'status';

        if (!Schema::hasColumn($table, $column)) {
            return;
        }

        DB::statement("ALTER TABLE {$table} MODIFY {$column} ENUM('Draft','Submitted','Approved','Rejected') DEFAULT 'Draft'");
    }
};

