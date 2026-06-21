<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 学歴の状態CHECK制約を更新
     * 「休学中」を追加
     */
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE clients DROP CHECK clients_education_status_check');
        } catch (\Exception $e) {
            // 制約が存在しない場合はエラーを無視
        }

        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_education_status_check CHECK (education_status IS NULL OR education_status IN ('卒業', '中退', '在学中', '休学中'))");
    }

    /**
     * ロールバック
     */
    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE clients DROP CHECK clients_education_status_check');
        } catch (\Exception $e) {
            // 制約が存在しない場合はエラーを無視
        }

        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_education_status_check CHECK (education_status IS NULL OR education_status IN ('卒業', '中退', '在学中'))");
    }
};
