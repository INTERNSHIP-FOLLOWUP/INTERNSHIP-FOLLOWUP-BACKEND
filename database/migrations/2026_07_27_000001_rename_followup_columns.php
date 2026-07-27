<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('followups', function (Blueprint $table) {
            if (Schema::hasColumn('followups', 'type')) {
                $table->renameColumn('type', 'meeting_type');
            }
            if (Schema::hasColumn('followups', 'scheduled_at')) {
                $table->renameColumn('scheduled_at', 'meeting_date');
            }
            if (Schema::hasColumn('followups', 'status')) {
                $table->dropColumn('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('followups', function (Blueprint $table) {
            if (Schema::hasColumn('followups', 'meeting_type')) {
                $table->renameColumn('meeting_type', 'type');
            }
            if (Schema::hasColumn('followups', 'meeting_date')) {
                $table->renameColumn('meeting_date', 'scheduled_at');
            }
            if (!Schema::hasColumn('followups', 'status')) {
                $table->string('status')->default('Scheduled');
            }
        });
    }
};
