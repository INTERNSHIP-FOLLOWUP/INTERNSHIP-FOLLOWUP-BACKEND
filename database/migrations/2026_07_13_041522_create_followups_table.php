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
        Schema::create('followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('meeting_type', ['Monthly', 'Quarterly', 'Annual', 'Emergency'])->default('Monthly');
            $table->date('meeting_date');
            $table->text('notes');
            $table->text('action_items')->nullable();
            $table->date('next_followup')->nullable();
            $table->timestamps();

            // Indexes for better query performance
            $table->index(['student_id', 'meeting_date']);
            $table->index('tutor_id');
            $table->index('meeting_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('followups');
    }
};
