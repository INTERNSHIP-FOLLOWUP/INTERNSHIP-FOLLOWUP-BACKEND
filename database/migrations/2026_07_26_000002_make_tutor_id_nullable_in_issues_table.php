<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            // Drop the existing foreign key
            $table->dropForeign(['tutor_id']);
        });

        // Modify the column to be nullable
        Schema::table('issues', function (Blueprint $table) {
            $table->unsignedBigInteger('tutor_id')->nullable()->change();
        });

        // Re-add the foreign key constraint (nullable FK)
        Schema::table('issues', function (Blueprint $table) {
            $table->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->unsignedBigInteger('tutor_id')->nullable(false)->change();
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->foreign('tutor_id')->references('id')->on('tutors')->cascadeOnDelete();
        });
    }
};

