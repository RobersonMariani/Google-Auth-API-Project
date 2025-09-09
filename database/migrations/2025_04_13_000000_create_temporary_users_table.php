<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('temporary_users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->text('google_id');
            $table->text('google_token');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_users');
    }
};
