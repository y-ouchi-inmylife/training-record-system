<?php

namespace Database\Seeders;

use App\Models\Phase;
use Illuminate\Database\Seeder;

/**
 * フェーズマスタの初期データ
 */
class PhaseSeeder extends Seeder
{
    public function run(): void
    {
        $phases = [
            ['name' => 'トレーナーと関わっていない', 'sort_order' => 1],
            ['name' => 'トレーニング中', 'sort_order' => 2],
            ['name' => 'トレーニング終了', 'sort_order' => 3],
        ];

        foreach ($phases as $phase) {
            Phase::firstOrCreate(
                ['name' => $phase['name']],
                ['sort_order' => $phase['sort_order']]
            );
        }
    }
}
