<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Remap existing tutor_id values from users.id -> tutors.id
        $tutorsMap = DB::table('tutors')->pluck('id', 'user_id');
        foreach ($tutorsMap as $userId => $tutorId) {
            DB::table('students')->where('tutor_id', $userId)->update(['tutor_id' => $tutorId]);
            DB::table('internship_assignments')->where('tutor_id', $userId)->update(['tutor_id' => $tutorId]);
            DB::table('issues')->where('tutor_id', $userId)->update(['tutor_id' => $tutorId]);
            DB::table('followups')->where('tutor_id', $userId)->update(['tutor_id' => $tutorId]);
            DB::table('company_messages')->where('tutor_id', $userId)->update(['tutor_id' => $tutorId]);
        }

        // 2. Clear invalid tutor_id records not present in tutors table
        $validTutorIds = DB::table('tutors')->pluck('id')->toArray();
        if (!empty($validTutorIds)) {
            DB::table('students')->whereNotIn('tutor_id', $validTutorIds)->update(['tutor_id' => null]);
            DB::table('internship_assignments')->whereNotIn('tutor_id', $validTutorIds)->update(['tutor_id' => $validTutorIds[0]]);
            DB::table('issues')->whereNotIn('tutor_id', $validTutorIds)->update(['tutor_id' => null]);
            DB::table('followups')->whereNotIn('tutor_id', $validTutorIds)->update(['tutor_id' => null]);
            DB::table('company_messages')->whereNotIn('tutor_id', $validTutorIds)->update(['tutor_id' => null]);
        }

        // 3. Update foreign key constraints to reference tutors(id)
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('tutors')->onDelete('set null');
        });

        Schema::table('internship_assignments', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
        });

        Schema::table('followups', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
        });

        Schema::table('company_messages', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('company_messages', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('followups', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('internship_assignments', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('users')->onDelete('set null');
        });
    }
};
