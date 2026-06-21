<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * role を system_admin / admin / staff の3種類に変更
     */
    public function up(): void
    {
        // 1. 先にCHECK制約を削除（データ変更前に必要）
        try {
            DB::statement("ALTER TABLE counselors DROP CHECK counselors_role_check");
        } catch (\Exception $e) {
            // 制約が存在しない場合は無視
        }

        // 2. login_id='admin' を system_admin に変更
        DB::table('counselors')
            ->where('login_id', 'admin')
            ->update(['role' => 'system_admin']);

        // 3. general を staff に変更
        DB::table('counselors')
            ->where('role', 'general')
            ->update(['role' => 'staff']);

        // 4. 新しいCHECK制約を追加
        DB::statement("ALTER TABLE counselors ADD CONSTRAINT counselors_role_check CHECK (role IN ('system_admin', 'admin', 'staff'))");
    }

    /**
     * ロールバック
     */
    public function down(): void
    {
        // 1. 先にCHECK制約を削除
        try {
            DB::statement("ALTER TABLE counselors DROP CHECK counselors_role_check");
        } catch (\Exception $e) {
            // 制約が存在しない場合は無視
        }

        // 2. system_admin を admin に戻す
        DB::table('counselors')
            ->where('role', 'system_admin')
            ->update(['role' => 'admin']);

        // 3. staff を general に戻す
        DB::table('counselors')
            ->where('role', 'staff')
            ->update(['role' => 'general']);

        // 4. CHECK制約を元に戻す
        DB::statement("ALTER TABLE counselors ADD CONSTRAINT counselors_role_check CHECK (role IN ('admin', 'general'))");
    }
};
