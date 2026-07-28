<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_messages')) {
            Schema::create('company_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_supervisors_id')->constrained()->cascadeOnDelete();
                $table->foreignId('tutor_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->enum('sender_type', ['company', 'tutor']);
                $table->text('message');
                $table->boolean('is_read')->default(false);
                $table->timestamps();

                $table->index(['company_supervisors_id', 'tutor_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_messages');
    }
};
