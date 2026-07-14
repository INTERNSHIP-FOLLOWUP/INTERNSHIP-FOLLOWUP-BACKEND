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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('technical_skill');
            $table->unsignedTinyInteger('communication');
            $table->unsignedTinyInteger('professionalism');
            $table->unsignedTinyInteger('attendance');
            $table->unsignedTinyInteger('overall_score')->virtualAs('(technical_skill + communication + professionalism + attendance) / 4');
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};