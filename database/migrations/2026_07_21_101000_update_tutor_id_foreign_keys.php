<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tutorRoleId = DB::table('roles')->where('name', 'tutor')->value('id');

        if ($tutorRoleId) {
            $tutorUsers = DB::table('users')->where('role_id', $tutorRoleId)->get();

            foreach ($tutorUsers as $user) {
                DB::table('tutors')->insertOrIgnore([
                    'id' => $user->id,
                    'user_id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

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
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('internship_assignments', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->foreign('tutor_id')->references('id')->on('users')->cascadeOnDelete();
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

        DB::table('tutors')->truncate();
    }
};
