<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DS-0800 client_email_change_tokens
        // ログイン中のクライアントがメールアドレスを変更する際、新しいアドレス宛に送る
        // メールアドレス確認リンクのトークンを管理する（段階 4-3）。
        Schema::create('client_email_change_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique('client_email_change_tokens_token_unique');
            $table->unsignedBigInteger('client_id');
            // 確認までは clients.email を書き換えず、この列に新しいアドレスを保持する
            $table->string('new_email', 255);
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->timestamps();

            $table->index('expires_at', 'client_email_change_tokens_expires_at_idx');
            $table->index('is_used', 'client_email_change_tokens_is_used_idx');
            $table->index('client_id', 'client_email_change_tokens_client_id_idx');

            $table->foreign('client_id', 'client_email_change_tokens_client_id_foreign')
                ->references('id')->on('clients')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_email_change_tokens');
    }
};
