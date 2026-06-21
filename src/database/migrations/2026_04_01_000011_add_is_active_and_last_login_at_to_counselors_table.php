<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * カウンセラーテーブルにアカウント有効フラグと最終ログイン日時を追加
     */
    public function up(): void
    {
        Schema::table('counselors', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_locked')->comment('アカウント有効フラグ');
            $table->timestamp('last_login_at')->nullable()->after('is_active')->comment('最終ログイン日時');
        });
    }

    /**
     * ロールバック
     */
    public function down(): void
    {
        Schema::table('counselors', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'last_login_at']);
        });
    }
};
