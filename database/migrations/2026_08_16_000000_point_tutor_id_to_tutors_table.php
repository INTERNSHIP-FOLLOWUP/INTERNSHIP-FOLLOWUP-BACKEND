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
            if (Schema::hasTable('students')) DB::table('students')->where('tutor_id', $userId)->update(['tutor_id' => $tutorId]);
            if (Schema::hasTable('internship_assignments')) DB::table('internship_assignments')->where('tutor_id', $userId)->update(['tutor_id' => $tutorId]);
            if (Schema::hasTable('issues')) DB::table('issues')->where('tutor_id', $userId)->update(['tutor_id' => $tutorId]);
            if (Schema::hasTable('followups')) DB::table('followups')->where('tutor_id', $userId)->update(['tutor_id' => $tutorId]);
            if (Schema::hasTable('company_messages')) DB::table('company_messages')->where('tutor_id', $userId)->update(['tutor_id' => $tutorId]);
        }

        // 2. Clear invalid tutor_id records not present in tutors table
        $validTutorIds = DB::table('tutors')->pluck('id')->toArray();
        if (!empty($validTutorIds)) {
            if (Schema::hasTable('students')) DB::table('students')->whereNotIn('tutor_id', $validTutorIds)->update(['tutor_id' => null]);
            if (Schema::hasTable('internship_assignments')) DB::table('internship_assignments')->whereNotIn('tutor_id', $validTutorIds)->update(['tutor_id' => $validTutorIds[0]]);
            if (Schema::hasTable('issues')) DB::table('issues')->whereNotIn('tutor_id', $validTutorIds)->update(['tutor_id' => null]);
            if (Schema::hasTable('followups')) DB::table('followups')->whereNotIn('tutor_id', $validTutorIds)->update(['tutor_id' => null]);
            if (Schema::hasTable('company_messages')) DB::table('company_messages')->whereNotIn('tutor_id', $validTutorIds)->update(['tutor_id' => null]);
        }

        // 3. Update foreign key constraints to reference tutors(id)
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                try { $table->dropForeign(['tutor_id']); } catch (\Throwable $e) {}
                $table->foreign('tutor_id')->references('id')->on('tutors')->onDelete('set null');
            });
        }

        if (Schema::hasTable('internship_assignments')) {
            Schema::table('internship_assignments', function (Blueprint $table) {
                try { $table->dropForeign(['tutor_id']); } catch (\Throwable $e) {}
                $table->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('issues')) {
            Schema::table('issues', function (Blueprint $table) {
                try { $table->dropForeign(['tutor_id']); } catch (\Throwable $e) {}
                $table->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('followups')) {
            Schema::table('followups', function (Blueprint $table) {
                try { $table->dropForeign(['tutor_id']); } catch (\Throwable $e) {}
                $table->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('company_messages')) {
            Schema::table('company_messages', function (Blueprint $table) {
                try { $table->dropForeign(['tutor_id']); } catch (\Throwable $e) {}
                $table->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('company_messages')) {
            Schema::table('company_messages', function (Blueprint $table) {
                try { $table->dropForeign(['tutor_id']); } catch (\Throwable $e) {}
                $table->foreign('tutor_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('followups')) {
            Schema::table('followups', function (Blueprint $table) {
                try { $table->dropForeign(['tutor_id']); } catch (\Throwable $e) {}
                $table->foreign('tutor_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('issues')) {
            Schema::table('issues', function (Blueprint $table) {
                try { $table->dropForeign(['tutor_id']); } catch (\Throwable $e) {}
                $table->foreign('tutor_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('internship_assignments')) {
            Schema::table('internship_assignments', function (Blueprint $table) {
                try { $table->dropForeign(['tutor_id']); } catch (\Throwable $e) {}
                $table->foreign('tutor_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                try { $table->dropForeign(['tutor_id']); } catch (\Throwable $e) {}
                $table->foreign('tutor_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }
};
