<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * 全シーダーの実行
     */
    public function run(): void
    {
        $this->call([
            CounselorSeeder::class,
            ConsultationTypeSeeder::class,
            PhaseSeeder::class,
            SupportStatusSeeder::class,
            SystemSettingSeeder::class,
        ]);
    }
}
