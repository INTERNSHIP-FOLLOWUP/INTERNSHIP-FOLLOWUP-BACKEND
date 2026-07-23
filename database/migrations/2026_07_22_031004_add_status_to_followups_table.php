<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('followups', function (Blueprint $table) {
            if (!Schema::hasColumn('followups', 'status')) {
                $table->enum('status', ['Scheduled', 'Completed', 'Missed', 'Cancelled'])->default('Scheduled')->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('followups', function (Blueprint $table) {
            if (Schema::hasColumn('followups', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};

