<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * clients.internal_id の採番サービス
 *
 * 既存の最大 internal_id（数値として解釈）に +1 した値を採番する。
 * ClientController（管理画面登録）と ClientIntakeController（公開URL登録）の
 * 採番ロジックを集約したもの。
 */
class ClientInternalIdService
{
    /**
     * 次の内部IDを採番して返す。
     *
     * 既存の最大 internal_id を `SELECT MAX(CAST(internal_id AS UNSIGNED)) ... FOR UPDATE`
     * で取得し、+1 した値を返す。レコードが無い場合は 1 を返す。
     *
     * 重要: このメソッドは必ず呼び出し側の既存トランザクション内で実行すること。
     *       FOR UPDATE のロックはトランザクション内でのみ機能するため、
     *       トランザクション外で呼ぶと採番の競合回避が効かない
     *       （このサービス内では新たにトランザクションを開始しない）。
     */
    public function generateNext(): int
    {
        $maxId = DB::selectOne('SELECT MAX(CAST(internal_id AS UNSIGNED)) as max_id FROM clients FOR UPDATE')->max_id;

        return ($maxId ?? 0) + 1;
    }
}
