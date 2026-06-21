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
            ['name' => '【個人支援】本人と関わっていない', 'sort_order' => 1],
            ['name' => '【個人支援】相談員との関わりが中心', 'sort_order' => 2],
            ['name' => '【集団支援】他の利用者との関わりが中心', 'sort_order' => 3],
            ['name' => '【集団支援】プログラムに参加している', 'sort_order' => 4],
            ['name' => '【集団支援】社会との関わりが中心', 'sort_order' => 5],
            ['name' => '【就職支援】社会との関わりが中心', 'sort_order' => 6],
            ['name' => '【就職支援】就職活動をしている', 'sort_order' => 7],
            ['name' => '【就職支援】就労している', 'sort_order' => 8],
        ];

        foreach ($phases as $phase) {
            Phase::create($phase);
        }
    }
}
