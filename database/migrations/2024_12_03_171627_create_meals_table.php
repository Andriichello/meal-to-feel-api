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
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('chat_id')->nullable();
            $table->foreignId('flow_id')->nullable();
            $table->string('status')->nullable();
            $table->date('date');
            $table->time('time');
            $table->json('metadata')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('chat_id')
                ->references('id')
                ->on('chats')
                ->onDelete('set null');

            $table->foreign('flow_id')
                ->references('id')
                ->on('flows')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
