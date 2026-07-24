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
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('general');
            $table->string('priority')->default('normal');
            $table->string('entity_type')->nullable();
            $table->string('entity_id')->nullable();
            $table->string('action_url')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['receiver_id', 'is_read', 'created_at']);
            $table->index(['type']);
            $table->index(['priority']);
            $table->index(['receiver_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};