<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DS-0900 client_password_reset_tokens
        // パスワードを忘れたお客様がログイン画面から申し込むパスワード再設定リンクの
        // トークンを管理する（段階 4-4）。
        // メールアドレスは変わらないため new_email カラムは持たない。
        // 発行者はお客様本人（申し込み時点で未認証）のため created_by も持たない。
        Schema::create('client_password_reset_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique('client_password_reset_tokens_token_unique');
            $table->unsignedBigInteger('client_id');
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->timestamps();

            $table->index('expires_at', 'client_password_reset_tokens_expires_at_idx');
            $table->index('is_used', 'client_password_reset_tokens_is_used_idx');
            $table->index('client_id', 'client_password_reset_tokens_client_id_idx');

            $table->foreign('client_id', 'client_password_reset_tokens_client_id_foreign')
                ->references('id')->on('clients')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_password_reset_tokens');
    }
};
