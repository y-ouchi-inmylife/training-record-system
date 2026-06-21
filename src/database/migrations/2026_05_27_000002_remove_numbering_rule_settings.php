<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')
            ->whereIn('key', ['numbering_rule', 'fiscal_year_start'])
            ->delete();
    }

    public function down(): void
    {
        // 削除した古い設定は復元しない（仕様変更により廃止された設定のため）
    }
};
