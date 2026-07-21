<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('file_path');
            $table->string('file_type');
            $table->bigInteger('file_size');
            $table->timestamps();

            $table->index('issue_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_attachments');
    }
};

