<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            if (Schema::hasColumn('tutors', 'first_name')) {
                $table->dropColumn('first_name');
            }
            if (Schema::hasColumn('tutors', 'last_name')) {
                $table->dropColumn('last_name');
            }
            if (Schema::hasColumn('tutors', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('tutors', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('tutors', 'photo')) {
                $table->dropColumn('photo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('photo')->nullable();
        });
    }
};
