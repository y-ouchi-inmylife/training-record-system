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
            ['name' => '事前相談', 'sort_order' => 1],
            ['name' => 'トレーニング', 'sort_order' => 2],
            ['name' => 'その他', 'sort_order' => 3],
        ];

        foreach ($types as $type) {
            ConsultationType::firstOrCreate(
                ['name' => $type['name']],
                ['sort_order' => $type['sort_order']]
            );
        }
    }
}
