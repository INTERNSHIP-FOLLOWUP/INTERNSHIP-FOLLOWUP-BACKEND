<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internship_assignments', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('followups', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('company_messages', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('company_messages', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
        });

        Schema::table('followups', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('tutors')->onDelete('set null');
        });

        Schema::table('internship_assignments', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
        });
    }
};
