<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * counseling_participants.created_at を削除する
     *
     * 参加者は相談記録に従属し、独自のタイムスタンプを持つ必要がない。
     * 詳細画面の更新日時は counseling_records.updated_at/updated_by を表示しており、
     * 参加者の created_at は一切参照されていない（運用上未使用）。
     */
    public function up(): void
    {
        Schema::table('counseling_participants', function (Blueprint $table) {
            $table->dropColumn('created_at');
        });
    }

    /**
     * ロールバック: created_at を元の定義（CURRENT_TIMESTAMP デフォルト）で復元する
     *
     * 注: 削除した値そのものは復元されず、復元時の CURRENT_TIMESTAMP が入る。
     * 参加者の created_at は運用上未使用のため許容。
     */
    public function down(): void
    {
        Schema::table('counseling_participants', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent();
        });
    }
};
