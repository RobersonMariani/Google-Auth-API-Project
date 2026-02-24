<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temporary_users', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('google_token');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('temporary_users', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn('expires_at');
        });
    }
};
