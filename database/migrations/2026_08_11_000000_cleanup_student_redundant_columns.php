<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'email', 'phone', 'photo']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('tutor_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('email')->nullable()->after('gender');
            $table->string('phone')->nullable()->after('email');
            $table->string('photo')->nullable()->after('status');
        });
    }
};
