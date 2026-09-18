<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * 全シーダーの実行
     *
     * 本番／開発の初回セットアップで実行する 2 種。
     * ClientSeeder は開発環境用のサンプルクライアント（佐藤 太郎、client@example.com）で、
     * ここからは呼ばれない。開発環境で必要なとき：
     *
     *     php artisan db:seed --class=ClientSeeder
     *
     * 同様に TrainingRecordDemoSeeder（クライアントポータル検証用の 18 件）も
     * --class 明示指定でのみ実行される（本番実行禁止）。
     */
    public function run(): void
    {
        $this->call([
            TrainerSeeder::class,        // 1. トレーナー2名（system_admin + admin ロールのトレーナー本人）
            SystemSettingSeeder::class,  // 2. システム設定2件
        ]);
    }
}
