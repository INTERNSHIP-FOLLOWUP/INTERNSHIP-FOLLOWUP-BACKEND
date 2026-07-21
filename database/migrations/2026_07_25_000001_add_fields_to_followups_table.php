<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('followups', function (Blueprint $table) {
            if (!Schema::hasColumn('followups', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('tutor_id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('followups', 'action_items')) {
                $table->text('action_items')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('followups', 'next_followup')) {
                $table->date('next_followup')->nullable()->after('action_items');
            }
        });
    }

    public function down(): void
    {
        Schema::table('followups', function (Blueprint $table) {
            if (Schema::hasColumn('followups', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
            if (Schema::hasColumn('followups', 'action_items')) {
                $table->dropColumn('action_items');
            }
            if (Schema::hasColumn('followups', 'next_followup')) {
                $table->dropColumn('next_followup');
            }
        });
    }
};

