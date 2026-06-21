<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * clientsテーブルにinternal_idカラムを追加し、既存データに自動採番する
     */
    public function up(): void
    {
        // 1. internal_idカラムを追加（一時的にNULL許容）
        Schema::table('clients', function (Blueprint $table) {
            $table->string('internal_id', 10)->nullable()->after('id')->comment('内部ID（クライアント識別用）');
        });

        // 2. システム設定にデフォルト値を追加
        DB::table('system_settings')->insertOrIgnore([
            ['key' => 'numbering_rule', 'value' => 'simple', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'fiscal_year_start', 'value' => 'calendar', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. 既存データに登録日順で自動採番（単純採番）
        $clients = DB::table('clients')->orderBy('created_at', 'asc')->orderBy('id', 'asc')->get(['id']);
        foreach ($clients as $index => $client) {
            DB::table('clients')
                ->where('id', $client->id)
                ->update(['internal_id' => (string) ($index + 1)]);
        }

        // 4. NOT NULL制約とUNIQUE制約を追加
        Schema::table('clients', function (Blueprint $table) {
            $table->string('internal_id', 10)->nullable(false)->unique('clients_internal_id_unique')->change();
        });
    }

    /**
     * ロールバック
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique('clients_internal_id_unique');
            $table->dropColumn('internal_id');
        });

        DB::table('system_settings')->whereIn('key', ['numbering_rule', 'fiscal_year_start'])->delete();
    }
};
