<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Note: company_supervisors.first_name, last_name, email, phone were already dropped
        // by a previous failed migration run that partially applied.

        // Drop unused columns from companies
        Schema::table('companies', function (Blueprint $table) {
            // Drop foreign key constraint on user_id first, then the column
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->dropColumn('role');
            $table->dropColumn('password');
            $table->dropColumn('contact_person');
            $table->dropColumn('phone');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('role')->nullable()->after('company_name');
            $table->string('password')->nullable()->after('email');
            $table->string('contact_person')->nullable()->after('industry');
            $table->string('phone')->nullable()->after('contact_person');
        });
    }
};
