<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worklog_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worklog_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->text('feedback')->nullable();
            $table->enum('status', ['Pending', 'Reviewed', 'Approved', 'Rejected'])->default('Pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['worklog_id', 'tutor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worklog_reviews');
    }
};
