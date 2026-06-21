<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 参加者区分のCHECK制約を更新
     * 「母」「父」「配偶者」「きょうだい」「子」「祖父母」を追加
     */
    public function up(): void
    {
        // MySQLでは DROP CONSTRAINT IF EXISTS が使えないため、try-catchで対応
        try {
            DB::statement('ALTER TABLE counseling_participants DROP CHECK counseling_participants_type_check');
        } catch (\Exception $e) {
            // 制約が存在しない場合はエラーを無視
        }

        DB::statement("ALTER TABLE counseling_participants ADD CONSTRAINT counseling_participants_type_check CHECK (participant_type IN ('本人', '支援者', '母', '父', '配偶者', 'きょうだい', '子', '祖父母', 'その他'))");
    }

    /**
     * ロールバック
     */
    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE counseling_participants DROP CHECK counseling_participants_type_check');
        } catch (\Exception $e) {
            // 制約が存在しない場合はエラーを無視
        }

        DB::statement("ALTER TABLE counseling_participants ADD CONSTRAINT counseling_participants_type_check CHECK (participant_type IN ('本人', '家族', '支援者', 'その他'))");
    }
};
