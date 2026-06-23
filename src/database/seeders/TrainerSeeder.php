<?php

namespace Database\Seeders;

use App\Models\Trainer;
use Illuminate\Database\Seeder;

/**
 * トレーナーアカウントの初期データ
 */
class TrainerSeeder extends Seeder
{
    public function run(): void
    {
        $trainers = [
            [
                'login_id' => 'system_admin',
                'name' => 'システム管理者',
                'role' => 'system_admin',
                'password' => 'InMyLife1965!',
                'is_locked' => false,
                'is_active' => true,
                'must_change_password' => false,
                'display_order' => 0,
            ],
            [
                'login_id' => 'admin',
                'name' => '管理トレーナー',
                'role' => 'admin',
                'password' => 'InMyLife1965!',
                'is_locked' => false,
                'is_active' => true,
                'must_change_password' => false,
                'display_order' => 1,
            ],
            [
                'login_id' => 'staff',
                'name' => '一般トレーナー',
                'role' => 'staff',
                'password' => 'InMyLife1965!',
                'is_locked' => false,
                'is_active' => true,
                'must_change_password' => false,
                'display_order' => 2,
            ],
        ];

        foreach ($trainers as $data) {
            Trainer::firstOrCreate(
                ['login_id' => $data['login_id']],
                $data
            );
        }
    }
}
