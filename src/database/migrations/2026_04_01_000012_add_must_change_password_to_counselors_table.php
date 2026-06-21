<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * カウンセラーテーブルにパスワード変更必須フラグを追加
     */
    public function up(): void
    {
        Schema::table('counselors', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('last_login_at')
                ->comment('初回ログイン時パスワード変更必須フラグ');
        });
    }

    /**
     * ロールバック
     */
    public function down(): void
    {
        Schema::table('counselors', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};
