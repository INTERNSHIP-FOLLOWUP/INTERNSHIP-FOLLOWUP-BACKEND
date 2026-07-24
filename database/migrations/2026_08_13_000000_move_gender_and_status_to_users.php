<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add columns to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'gender')) {
                $table->string('gender', 50)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status', 50)->default('active')->after('gender');
            }
        });

        // Drop gender + status from students
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'gender')) {
                $table->dropColumn('gender');
            }
            if (Schema::hasColumn('students', 'status')) {
                $table->dropColumn('status');
            }
        });

        // Drop gender + status from tutors
        Schema::table('tutors', function (Blueprint $table) {
            if (Schema::hasColumn('tutors', 'gender')) {
                $table->dropColumn('gender');
            }
            if (Schema::hasColumn('tutors', 'status')) {
                $table->dropColumn('status');
            }
        });

        // Drop status from company_supervisors
        Schema::table('company_supervisors', function (Blueprint $table) {
            if (Schema::hasColumn('company_supervisors', 'status')) {
                $table->dropColumn('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'gender')) {
                $table->dropColumn('gender');
            }
            if (Schema::hasColumn('users', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'gender')) {
                $table->string('gender')->nullable();
            }
            if (!Schema::hasColumn('students', 'status')) {
                $table->string('status')->default('active');
            }
        });

        Schema::table('tutors', function (Blueprint $table) {
            if (!Schema::hasColumn('tutors', 'gender')) {
                $table->string('gender')->nullable();
            }
            if (!Schema::hasColumn('tutors', 'status')) {
                $table->string('status')->default('active');
            }
        });

        Schema::table('company_supervisors', function (Blueprint $table) {
            if (!Schema::hasColumn('company_supervisors', 'status')) {
                $table->string('status')->default('active');
            }
        });
    }
};
