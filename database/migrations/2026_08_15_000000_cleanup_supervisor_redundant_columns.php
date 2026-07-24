<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_supervisors', function (Blueprint $table) {
            if (Schema::hasColumn('company_supervisors', 'first_name')) {
                $table->dropColumn('first_name');
            }
            if (Schema::hasColumn('company_supervisors', 'last_name')) {
                $table->dropColumn('last_name');
            }
            if (Schema::hasColumn('company_supervisors', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('company_supervisors', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_supervisors', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('user_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('email')->nullable()->after('last_name');
            $table->string('phone')->nullable()->after('email');
        });
    }
};
