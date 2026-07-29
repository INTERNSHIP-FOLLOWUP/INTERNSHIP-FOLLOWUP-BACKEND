<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('companies', 'company_image')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('company_image')->nullable()->after('website');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('companies', 'company_image')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('company_image');
            });
        }
    }
};
