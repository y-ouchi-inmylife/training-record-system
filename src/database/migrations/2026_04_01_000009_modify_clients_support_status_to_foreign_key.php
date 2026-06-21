<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * clientsテーブルのsupport_statusカラムを外部キー（support_status_id）に変更
     * 既存データを支援状態マスタに移行し、紐付けを行う
     */
    public function up(): void
    {
        // 1. 支援状態マスタにデフォルトデータを投入（まだ存在しない場合）
        $statuses = ['支援中', '連絡待ち', '支援終了', 'リファー済', '利用中止', '利用せず'];
        foreach ($statuses as $index => $name) {
            DB::table('support_statuses')->insertOrIgnore([
                'name' => $name,
                'sort_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. support_status_id カラムを追加
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('support_status_id')
                ->nullable()
                ->after('cooperating_agencies')
                ->constrained('support_statuses')
                ->nullOnDelete();
        });

        // 3. 既存データの移行（support_status → support_status_id）
        $supportStatuses = DB::table('support_statuses')->pluck('id', 'name');
        foreach ($supportStatuses as $name => $id) {
            DB::table('clients')
                ->where('support_status', $name)
                ->update(['support_status_id' => $id]);
        }

        // 4. CHECK制約を削除（存在する場合）
        try {
            DB::statement("ALTER TABLE clients DROP CHECK clients_support_status_check");
        } catch (\Exception $e) {
            // 制約が存在しない場合は無視
        }

        // 5. 旧インデックスを削除
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_support_status_idx');
        });

        // 6. 旧カラムを削除
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('support_status');
        });

        // 7. 新しいインデックスを追加
        Schema::table('clients', function (Blueprint $table) {
            $table->index('support_status_id', 'clients_support_status_idx');
        });
    }

    /**
     * ロールバック：support_status_id を support_status に戻す
     */
    public function down(): void
    {
        // 1. 旧カラムを復元
        Schema::table('clients', function (Blueprint $table) {
            $table->string('support_status', 20)->nullable()->comment('支援状態');
        });

        // 2. データを復元（support_status_id → support_status）
        $supportStatuses = DB::table('support_statuses')->pluck('name', 'id');
        foreach ($supportStatuses as $id => $name) {
            DB::table('clients')
                ->where('support_status_id', $id)
                ->update(['support_status' => $name]);
        }

        // 3. support_status_id カラムと外部キーを削除
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['support_status_id']);
            $table->dropIndex('clients_support_status_idx');
            $table->dropColumn('support_status_id');
        });

        // 4. 旧インデックスとCHECK制約を復元
        Schema::table('clients', function (Blueprint $table) {
            $table->index('support_status', 'clients_support_status_idx');
        });
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_support_status_check CHECK (support_status IS NULL OR support_status IN ('支援中', '連絡待ち', '支援終了', 'リファー済', '利用中止', '利用せず'))");
    }
};
