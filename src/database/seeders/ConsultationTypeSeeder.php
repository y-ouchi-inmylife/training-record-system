<?php

namespace Database\Seeders;

use App\Models\ConsultationType;
use Illuminate\Database\Seeder;

/**
 * トレーニング内容マスタの初期データ
 */
class ConsultationTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => '就労相談', 'sort_order' => 1],
            ['name' => '生活相談', 'sort_order' => 2],
            ['name' => '心理相談', 'sort_order' => 3],
            ['name' => '家族相談', 'sort_order' => 4],
            ['name' => 'その他', 'sort_order' => 5],
        ];

        foreach ($types as $type) {
            ConsultationType::firstOrCreate(
                ['name' => $type['name']],
                ['sort_order' => $type['sort_order']]
            );
        }
    }
}
