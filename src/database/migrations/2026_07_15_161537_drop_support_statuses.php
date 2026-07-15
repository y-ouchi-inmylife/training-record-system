<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 支援状態マスタの削除。
     * clients の外部キー・インデックス・カラムを落としてから、マスタテーブルを削除する。
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign('clients_support_status_id_foreign');
            $table->dropIndex('clients_support_status_idx');
            $table->dropColumn('support_status_id');
        });

        Schema::dropIfExists('support_statuses');
    }

    /**
     * ロールバック。
     * マスタテーブルを再作成してから、clients にカラム・インデックス・外部キーを戻す。
     * ※ clients.support_status_id の値およびマスタのデータは復元されない（スキーマのみ）。
     */
    public function down(): void
    {
        Schema::create('support_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique()->comment('支援状態の名称');
            $table->integer('sort_order')->default(0)->comment('表示順序');
            $table->boolean('show_in_dashboard')->default(true)->comment('ダッシュボードに表示するか');
            $table->timestamps();

            $table->index('sort_order', 'support_statuses_order_idx');
        });

        DB::statement("ALTER TABLE support_statuses ADD CONSTRAINT support_statuses_sort_check CHECK (sort_order >= 0)");

        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('support_status_id')->nullable()->after('primary_trainer_id');
            $table->index('support_status_id', 'clients_support_status_idx');
            $table->foreign('support_status_id', 'clients_support_status_id_foreign')
                ->references('id')->on('support_statuses')->nullOnDelete();
        });
    }
};
