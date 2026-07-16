<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Note: MySQL doesn't enforce CHECK constraints, so this migration
     * is kept for documentation purposes but doesn't actually add constraints.
     * The role validation is handled at the application level.
     */
    public function up(): void
    {
        // MySQL doesn't enforce CHECK constraints, so no action needed
        // Role validation is handled at the application level
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // MySQL doesn't enforce CHECK constraints, so no action needed
    }
};
