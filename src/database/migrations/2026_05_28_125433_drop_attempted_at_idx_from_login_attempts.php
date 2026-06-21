<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // attempted_at 単独インデックスを削除
        // （クリーンアップ機能が未実装・実装予定なし、
        //   attempted_at 単独検索もないため不要）
        DB::statement('ALTER TABLE login_attempts DROP INDEX login_attempts_attempted_at_idx');
    }

    public function down(): void
    {
        // ロールバック時はインデックスを再作成
        DB::statement('ALTER TABLE login_attempts ADD INDEX login_attempts_attempted_at_idx (attempted_at)');
    }
};
