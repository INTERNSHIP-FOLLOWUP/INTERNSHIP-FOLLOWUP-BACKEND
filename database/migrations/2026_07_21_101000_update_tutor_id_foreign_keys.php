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
                    'first_name' => $user->name ?? '',
                    'last_name' => '',
                    'email' => $user->email,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $tables = ['students', 'internship_assignments', 'issues', 'followups', 'company_messages'];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropForeign(['tutor_id']);
                });
            } catch (\Exception $e) {
                // Foreign key may not exist
            }

            try {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
                });
            } catch (\Exception $e) {
                // Foreign key may already exist
            }
        }
    }

    public function down(): void
    {
        $tables = ['students', 'internship_assignments', 'issues', 'followups', 'company_messages'];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropForeign(['tutor_id']);
                });
            } catch (\Exception $e) {
                // Foreign key may not exist
            }

            try {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreign('tutor_id')->references('id')->on('users')->cascadeOnDelete();
                });
            } catch (\Exception $e) {
                // Foreign key may already exist
            }
        }

        DB::table('tutors')->truncate();
    }
};
