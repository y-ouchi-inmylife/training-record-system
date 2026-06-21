<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ログイン試行記録テーブル作成
     */
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counselor_id')->nullable()->constrained('counselors')->nullOnDelete();
            $table->string('login_id_input', 50)->comment('入力されたログインID');
            $table->string('ip_address', 45)->nullable()->comment('接続元IPアドレス');
            $table->timestamp('attempted_at')->useCurrent()->comment('試行日時');
            $table->boolean('success')->comment('成功/失敗');

            // 複合インデックス: 直近の連続失敗回数カウント用
            $table->index(['counselor_id', 'attempted_at'], 'login_attempts_counselor_idx');
            $table->index('attempted_at', 'login_attempts_attempted_at_idx');
        });
    }

    /**
     * ロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
