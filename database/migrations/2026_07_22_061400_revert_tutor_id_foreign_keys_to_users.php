<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'internship_assignments' => ['on' => 'users', 'delete' => 'cascade'],
            'students' => ['on' => 'users', 'delete' => 'set null'],
            'issues' => ['on' => 'users', 'delete' => 'cascade'],
            'followups' => ['on' => 'users', 'delete' => 'cascade'],
            'company_messages' => ['on' => 'users', 'delete' => 'cascade'],
        ];

        foreach ($tables as $table => $config) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropForeign(['tutor_id']);
                });
            } catch (\Exception $e) {
                // Foreign key may not exist
            }

            try {
                Schema::table($table, function (Blueprint $t) use ($config) {
                    $foreign = $t->foreign('tutor_id')->references('id')->on($config['on']);
                    if ($config['delete'] === 'cascade') {
                        $foreign->cascadeOnDelete();
                    } elseif ($config['delete'] === 'set null') {
                        $foreign->nullOnDelete();
                    }
                });
            } catch (\Exception $e) {
                // Foreign key may already exist
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'company_messages' => ['on' => 'tutors', 'delete' => 'cascade'],
            'followups' => ['on' => 'tutors', 'delete' => 'cascade'],
            'issues' => ['on' => 'tutors', 'delete' => 'cascade'],
            'students' => ['on' => 'tutors', 'delete' => 'set null'],
            'internship_assignments' => ['on' => 'tutors', 'delete' => 'cascade'],
        ];

        foreach ($tables as $table => $config) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropForeign(['tutor_id']);
                });
            } catch (\Exception $e) {
                // Foreign key may not exist
            }

            try {
                Schema::table($table, function (Blueprint $t) use ($config) {
                    $foreign = $t->foreign('tutor_id')->references('id')->on($config['on']);
                    if ($config['delete'] === 'cascade') {
                        $foreign->cascadeOnDelete();
                    } elseif ($config['delete'] === 'set null') {
                        $foreign->nullOnDelete();
                    }
                });
            } catch (\Exception $e) {
                // Foreign key may already exist
            }
        }
    }
};
