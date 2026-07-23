<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_feedback', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->after('company_id')
                ->constrained('students')->nullOnDelete();
            $table->json('strengths')->nullable()->after('title');
            $table->json('improvement_areas')->nullable()->after('strengths');
            $table->string('title')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('company_feedback', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropColumn(['student_id', 'strengths', 'improvement_areas']);
            $table->string('title')->nullable(false)->change();
        });
    }
};
