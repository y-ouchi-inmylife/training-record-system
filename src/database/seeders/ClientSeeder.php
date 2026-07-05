<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

/**
 * デモ用サンプルクライアントの初期データ
 *
 * 本番デモで「クライアントがログイン → 記録一覧」を見せるための
 * ログイン可能なサンプルクライアントを1件だけ用意する。
 * 記録・メディアは作らない（デモ当日にライブで追加する方針）。
 *
 * ログイン要件（Client\LoginController は email + password + is_viewable=true で attempt）：
 * - email は非空・unique
 * - password は Client モデルの casts で 'hashed' 指定のため、プレーン文字列を渡せば自動 bcrypt 化される
 * - is_viewable=true が無いとログインできない（閲覧未解放は認証失敗扱い）
 *
 * internal_id をユニークキーにして firstOrCreate で冪等に。重複実行しても増えない。
 */
class ClientSeeder extends Seeder
{
    public function run(): void
    {
        Client::firstOrCreate(
            ['internal_id' => 'DEMO01'],
            [
                'initial_consultation_date' => '2026-07-01',
                'last_name' => 'デモ',
                'first_name' => '太郎',
                'last_name_kana' => 'デモ',
                'first_name_kana' => 'タロウ',
                'email' => 'demo-client@example.com',
                // プレーン文字列で渡す。Client モデルの casts の 'password' => 'hashed' で
                // 自動的に bcrypt ハッシュ化されるため、ここで Hash::make しない。
                'password' => 'InMyLife1965!',
                'is_viewable' => true,
            ]
        );
    }
}
