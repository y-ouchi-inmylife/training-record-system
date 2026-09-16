<?php

namespace Database\Seeders;

use App\Models\Trainer;
use App\Rules\StrongPassword;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * トレーナーアカウントの初期データ（本番運用構成）
 *
 * 本番でも実行する。作成されるのはシステム管理者 1 名と管理者（トレーナー本人）1 名の 2 名。
 * 詳細は docs/requirements.md §5-2 と docs/db-schema.md §7-2 を参照。
 *
 * 【管理者アカウント（admin ロール）について】
 * - **トレーナー本人のアカウント**として使い続ける（初期構築用の一時アカウントではない）。
 *   `login_id` と `name` は本人のものを設定する。運用開始後にトレーナーが増える場合は、
 *   この admin アカウントでログインしてトレーナー管理画面から追加する。
 * - パスワードは **ランダム生成** する。誰も値を知らない状態でシステムが立ち上がる。
 *   運用開始時にシステム管理者が `trainers.reset-password` からパスワードをリセットし、
 *   本人に伝える運用（`TrainerController::resetPassword` L253-278）。
 * - `must_change_password` はシーダーで `false` のままにする。パスワードリセット時に
 *   `TrainerController::resetPassword` が自動で `true` に立てるため、リセット後の初回
 *   ログインで本人にパスワード変更が求められる。
 *
 * 【冪等性】
 * `firstOrCreate(['login_id' => ...], $data)` で冪等。既にアカウントが存在する場合は
 * 作成されず、生成したランダム値は破棄される（意図した挙動）。
 */
class TrainerSeeder extends Seeder
{
    public function run(): void
    {
        // 管理者（admin ロール、トレーナー本人）用のランダムパスワードを生成する。
        // Str::password(32) は各カテゴリ（letters / numbers / symbols）から最低 1 文字ずつ
        // 確保するが、letters は大文字と小文字が同じ配列にあり「大文字と小文字の両方を必ず含む」
        // ことは保証されない（32 文字なら実務上ほぼ確実だが原理的には非決定的）。
        // したがって生成後に StrongPassword ルールで検証し、通過するまで再生成する
        // ループ形式にする。ループ回数の期待値はほぼ 1（32 文字で通過確率は極めて高い）。
        //
        // 生成した値はログや標準出力に出さない（$this->command->info() 等で流さない）。
        // 誰も知らない状態にして、システム管理者が trainers.reset-password からリセットする
        // 運用が前提。
        do {
            $adminPassword = Str::password(32);
            $validator = Validator::make(
                ['password' => $adminPassword],
                ['password' => [new StrongPassword()]]
            );
        } while ($validator->fails());

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
                // トレーナー本人の login_id / name
                'login_id' => 'murayama',
                'name' => '村山',
                'role' => 'admin',
                // ランダム生成（誰も知らない）。本人には後から
                // システム管理者が trainers.reset-password でリセットして伝える
                'password' => $adminPassword,
                'is_locked' => false,
                'is_active' => true,
                // リセット時に自動で true に立つため、シーダー側では false のままでよい
                'must_change_password' => false,
                'display_order' => 1,
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
