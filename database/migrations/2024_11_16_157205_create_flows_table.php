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
        Schema::create('flows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('beg_id');
            $table->unsignedBigInteger('end_id')->nullable();
            $table->string('command', 64);
            $table->string('status', 25);
            $table->string('step', 25)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('chat_id')
                ->references('unique_id')
                ->on('chats')
                ->onDelete('set null');

            $table->foreign('user_id')
                ->references('unique_id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('beg_id')
                ->references('unique_id')
                ->on('messages')
                ->onDelete('cascade');

            $table->foreign('end_id')
                ->references('unique_id')
                ->on('messages')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flows');
    }
};
