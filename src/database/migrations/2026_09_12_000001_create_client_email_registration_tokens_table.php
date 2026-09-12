<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DS-0700 client_email_registration_tokens
        // メールアドレス登録用 URL のトークンを管理するテーブル
        Schema::create('client_email_registration_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique('client_email_registration_tokens_token_unique');
            $table->unsignedBigInteger('client_id');
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->index('expires_at', 'client_email_registration_tokens_expires_at_idx');
            $table->index('is_used', 'client_email_registration_tokens_is_used_idx');
            $table->index('client_id', 'client_email_registration_tokens_client_id_idx');
            $table->index('created_by', 'client_email_registration_tokens_created_by_idx');

            $table->foreign('client_id', 'client_email_registration_tokens_client_id_foreign')
                ->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('created_by', 'client_email_registration_tokens_created_by_foreign')
                ->references('id')->on('trainers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_email_registration_tokens');
    }
};
