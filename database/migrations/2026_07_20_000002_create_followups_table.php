<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->default('General Meeting');
            $table->dateTime('scheduled_at');
            $table->text('notes')->nullable();
            $table->enum('status', ['Scheduled', 'Completed', 'Missed', 'Cancelled'])->default('Scheduled');
            $table->timestamps();

            $table->index(['tutor_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('followups');
    }
};
