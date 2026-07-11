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
        // This migration is a placeholder since tutors are stored in the users table
        // with role='tutor'. The Tutor model uses the existing users table.
        Schema::create('tutors', function (Blueprint $table) {
            // This table is not actually used - it's here for completeness
            // Tutors are stored in the users table
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutors');
    }
};