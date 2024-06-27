<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bot_users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_telegram');
            $table->string('firstname', 512)->nullable();
            $table->string('lastname', 512)->nullable();
            $table->string('username', 512)->nullable();
            $table->integer('id_last_message')->nullable();
            $table->tinyInteger('study_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_users');
    }
};
