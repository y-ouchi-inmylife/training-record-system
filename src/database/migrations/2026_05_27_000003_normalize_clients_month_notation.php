<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. CHECK制約を一旦削除（既存データの更新を可能にするため）
        DB::statement('ALTER TABLE clients DROP CHECK clients_employment_period_check');
        DB::statement('ALTER TABLE clients DROP CHECK clients_unemployment_period_check');

        // 2. 既存データを「ヶ月」表記に統一
        DB::table('clients')
            ->where('employment_period', '有期雇用（3ヵ月未満）')
            ->update(['employment_period' => '有期雇用（3ヶ月未満）']);
        DB::table('clients')
            ->where('employment_period', '有期雇用（3～6ヵ月未満）')
            ->update(['employment_period' => '有期雇用（3～6ヶ月未満）']);
        DB::table('clients')
            ->where('employment_period', '有期雇用（6ヵ月～1年未満）')
            ->update(['employment_period' => '有期雇用（6ヶ月～1年未満）']);
        DB::table('clients')
            ->where('unemployment_period', '6カ月未満')
            ->update(['unemployment_period' => '6ヶ月未満']);
        DB::table('clients')
            ->where('unemployment_period', '6カ月～1年')
            ->update(['unemployment_period' => '6ヶ月～1年']);

        // 3. CHECK制約を「ヶ月」表記で再作成
        DB::statement("
            ALTER TABLE clients
            ADD CONSTRAINT clients_employment_period_check
            CHECK (
                employment_period IS NULL OR
                employment_period IN (
                    '有期雇用（3ヶ月未満）',
                    '有期雇用（3～6ヶ月未満）',
                    '有期雇用（6ヶ月～1年未満）',
                    '有期雇用（1年以上）',
                    '無期雇用'
                )
            )
        ");

        DB::statement("
            ALTER TABLE clients
            ADD CONSTRAINT clients_unemployment_period_check
            CHECK (
                unemployment_period IS NULL OR
                unemployment_period IN (
                    '6ヶ月未満',
                    '6ヶ月～1年',
                    '1～3年',
                    '3～5年',
                    '5～10年',
                    '10年以上'
                )
            )
        ");
    }

    public function down(): void
    {
        // 1. CHECK制約を削除
        DB::statement('ALTER TABLE clients DROP CHECK clients_employment_period_check');
        DB::statement('ALTER TABLE clients DROP CHECK clients_unemployment_period_check');

        // 2. データを元の表記に戻す
        DB::table('clients')
            ->where('employment_period', '有期雇用（3ヶ月未満）')
            ->update(['employment_period' => '有期雇用（3ヵ月未満）']);
        DB::table('clients')
            ->where('employment_period', '有期雇用（3～6ヶ月未満）')
            ->update(['employment_period' => '有期雇用（3～6ヵ月未満）']);
        DB::table('clients')
            ->where('employment_period', '有期雇用（6ヶ月～1年未満）')
            ->update(['employment_period' => '有期雇用（6ヵ月～1年未満）']);
        DB::table('clients')
            ->where('unemployment_period', '6ヶ月未満')
            ->update(['unemployment_period' => '6カ月未満']);
        DB::table('clients')
            ->where('unemployment_period', '6ヶ月～1年')
            ->update(['unemployment_period' => '6カ月～1年']);

        // 3. CHECK制約を元の表記で再作成
        DB::statement("
            ALTER TABLE clients
            ADD CONSTRAINT clients_employment_period_check
            CHECK (
                employment_period IS NULL OR
                employment_period IN (
                    '有期雇用（3ヵ月未満）',
                    '有期雇用（3～6ヵ月未満）',
                    '有期雇用（6ヵ月～1年未満）',
                    '有期雇用（1年以上）',
                    '無期雇用'
                )
            )
        ");

        DB::statement("
            ALTER TABLE clients
            ADD CONSTRAINT clients_unemployment_period_check
            CHECK (
                unemployment_period IS NULL OR
                unemployment_period IN (
                    '6カ月未満',
                    '6カ月～1年',
                    '1～3年',
                    '3～5年',
                    '5～10年',
                    '10年以上'
                )
            )
        ");
    }
};
