<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('google_email');
        });

        Schema::table('mail_logs', function (Blueprint $table) {
            $table->index('user_email');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['google_email']);
        });

        Schema::table('mail_logs', function (Blueprint $table) {
            $table->dropIndex(['user_email']);
            $table->dropIndex(['status']);
        });
    }
};
