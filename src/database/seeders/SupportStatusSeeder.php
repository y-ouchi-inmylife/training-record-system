<?php

namespace Database\Seeders;

use App\Models\SupportStatus;
use Illuminate\Database\Seeder;

/**
 * 支援状態マスタの初期データ
 */
class SupportStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => '支援中', 'sort_order' => 1, 'show_in_dashboard' => true],
            ['name' => '連絡待ち', 'sort_order' => 2, 'show_in_dashboard' => true],
            ['name' => '支援終了', 'sort_order' => 3, 'show_in_dashboard' => false],
            ['name' => 'リファー済', 'sort_order' => 4, 'show_in_dashboard' => false],
            ['name' => '利用中止', 'sort_order' => 5, 'show_in_dashboard' => false],
            ['name' => '利用せず', 'sort_order' => 6, 'show_in_dashboard' => false],
        ];

        foreach ($statuses as $status) {
            SupportStatus::firstOrCreate(
                ['name' => $status['name']],
                ['sort_order' => $status['sort_order'], 'show_in_dashboard' => $status['show_in_dashboard']]
            );
        }
    }
}
