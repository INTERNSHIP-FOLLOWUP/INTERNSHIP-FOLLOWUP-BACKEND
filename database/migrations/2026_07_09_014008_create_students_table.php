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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_code')->unique();
            $table->foreignId('batch_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('tutor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('name');
            $table->string('gender');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('photo')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
