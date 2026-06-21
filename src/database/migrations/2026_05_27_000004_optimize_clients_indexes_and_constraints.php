<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. 名前検索インデックス3本を削除（部分一致LIKEでは効かないため）
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_name_idx');
            $table->dropIndex('clients_kana_idx');
            $table->dropIndex('clients_family_name_idx');
        });

        // 2. family_relationship の CHECK 制約を厳密化（IS NULL OR を削除）
        DB::statement('ALTER TABLE clients DROP CHECK clients_family_rel_check');
        DB::statement("
            ALTER TABLE clients
            ADD CONSTRAINT clients_family_rel_check
            CHECK (
                family_relationship IN ('本人', '母', '父', '配偶者', 'きょうだい', '子', '祖父母', 'その他')
            )
        ");
    }

    public function down(): void
    {
        // 1. CHECK 制約を IS NULL OR 含む形に戻す
        DB::statement('ALTER TABLE clients DROP CHECK clients_family_rel_check');
        DB::statement("
            ALTER TABLE clients
            ADD CONSTRAINT clients_family_rel_check
            CHECK (
                family_relationship IS NULL OR
                family_relationship IN ('本人', '母', '父', '配偶者', 'きょうだい', '子', '祖父母', 'その他')
            )
        ");

        // 2. 名前検索インデックス3本を復元
        Schema::table('clients', function (Blueprint $table) {
            $table->index(['last_name', 'first_name'], 'clients_name_idx');
            $table->index(['last_name_kana', 'first_name_kana'], 'clients_kana_idx');
            $table->index(['family_last_name', 'family_first_name'], 'clients_family_name_idx');
        });
    }
};
