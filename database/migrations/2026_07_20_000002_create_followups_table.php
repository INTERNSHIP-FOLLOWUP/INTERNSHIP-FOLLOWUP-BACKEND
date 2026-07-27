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
            $table->foreignId('tutor_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('meeting_type');
            $table->date('meeting_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tutor_id', 'meeting_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('followups');
    }
};
