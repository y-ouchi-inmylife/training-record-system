<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_password_setup_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique('client_password_setup_tokens_token_unique');
            $table->unsignedBigInteger('client_id');
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->index('expires_at', 'client_password_setup_tokens_expires_at_idx');
            $table->index('is_used', 'client_password_setup_tokens_is_used_idx');
            $table->index('client_id', 'client_password_setup_tokens_client_id_idx');
            $table->index('created_by', 'client_password_setup_tokens_created_by_idx');

            $table->foreign('client_id', 'client_password_setup_tokens_client_id_foreign')
                ->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('created_by', 'client_password_setup_tokens_created_by_foreign')
                ->references('id')->on('trainers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_password_setup_tokens');
    }
};
