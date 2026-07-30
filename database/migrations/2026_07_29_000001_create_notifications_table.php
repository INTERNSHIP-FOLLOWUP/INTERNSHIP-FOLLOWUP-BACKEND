<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('sender_type')->nullable(); // system, company, tutor, student
            $table->string('event'); // worklog_submitted, evaluation_submitted, issue_created, etc.
            $table->string('category')->default('general'); // general, evaluation
            $table->string('title');
            $table->text('message');
            $table->string('reference_type')->nullable(); // worklog, assignment, issue, evaluation, followup
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('action_url')->nullable();
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_read', 'created_at']);
            $table->index(['user_id', 'category', 'created_at']);
            $table->index(['user_id', 'priority', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
