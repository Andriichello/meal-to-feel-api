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
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credential_id')->nullable();
            $table->foreignId('message_id')->nullable();
            $table->foreignId('file_id')->nullable();
            $table->string('language');
            $table->string('status');
            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('tried_at');
            $table->timestamp('try_again_at')->nullable();
            $table->timestamps();

            $table->foreign('credential_id')
                ->references('id')
                ->on('credentials')
                ->onDelete('set null');

            $table->foreign('file_id')
                ->references('id')
                ->on('files')
                ->onDelete('cascade');

            $table->foreign('message_id')
                ->references('id')
                ->on('messages')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
