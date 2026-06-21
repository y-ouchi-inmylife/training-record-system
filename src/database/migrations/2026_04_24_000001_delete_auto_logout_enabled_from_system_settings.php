<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * system_settings テーブルから auto_logout_enabled キーのレコードを削除する。
     *
     * 背景:
     * UI 改善により、自動ログアウトの有効/無効判定は auto_logout_minutes > 0 に
     * 統合された。auto_logout_enabled は書き込みのみ行われ、どこからも参照されない
     * デッドフィールド化していたため、Seeder/Controller からの書き込み処理削除と
     * あわせて、既存 DB からも該当レコードを削除する。
     */
    public function up(): void
    {
        DB::table('system_settings')->where('key', 'auto_logout_enabled')->delete();
    }

    /**
     * ロールバック時は auto_logout_minutes > 0 から派生値を算出してレコードを復元する。
     */
    public function down(): void
    {
        $autoLogoutMinutes = DB::table('system_settings')
            ->where('key', 'auto_logout_minutes')
            ->value('value');

        $value = ((int) $autoLogoutMinutes > 0) ? '1' : '0';

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'auto_logout_enabled'],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
        );
    }
};
