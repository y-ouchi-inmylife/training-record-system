# データ容量試算（10年運用）

**作成日**: 2026-05-07
**目的**: さくら共有サーバー（スタンダードプラン、300GB）での10年運用が成り立つかを桁感で把握する
**根拠**: docs/db-schema.md 4章のテーブル定義

---

## 1. 試算条件

### 1-1. データ量の前提

| 項目 | 平均ケース | 最大ケース |
|---|---|---|
| クライアント数（累計） | 1,000名 | 1,000名 |
| 1名あたり相談記録数 | 200件 | 500件 |
| 相談記録総数 | 200,000件 | 500,000件 |
| 想定運用期間 | 10年 | 10年 |

### 1-2. サイズ計算の前提

- **文字セット**: utf8mb4（日本語1文字＝3バイト、絵文字等は4バイトだが本システムでは無視）
- **DBエンジン**: InnoDB（行オーバーヘッド ~30バイト／行を加算）
- **VARCHAR**: 実際の格納サイズ（NULL の場合 0 バイト相当、長さプレフィックス1〜2バイト）
- **TEXT/LONGTEXT**: 平均的なコンテンツ長を仮定して算出
- **時刻型**: TIMESTAMP=4バイト、DATE=3バイト、TIME=3バイト
- **整数型**: BIGINT=8バイト、INTEGER=4バイト、BOOLEAN=1バイト

### 1-3. 主観的に置いた仮定（桁感に大きく効く項目）

| 項目 | 仮定値 | 理由 |
|---|---|---|
| 相談記録 record_content（事実記録） | 平均1,500文字 | A4半分〜1ページ相当の手書き記録量 |
| 相談記録 impression（所感） | 平均500文字 | 短文メモ |
| 音声ファイルの作成率 | 相談記録の50% | 録音されないテキスト直接入力もあるため |
| 音声 transcription_text | 平均5,000文字 | 30分セッション相当 |
| 音声 summary_text | 平均1,000文字 |  |
| 1日あたりaccess_logs生成数 | 20名 × 100操作 = 2,000件/日 | カウンセラー20名、頻繁な画面遷移を含む |
| access_logs 保持期間 | 1年（クリーンアップ運用前提） | 長期運用は別途検討。10年無削除はリスク（後述） |
| login_attempts 保持期間 | 6ヶ月（クリーンアップ運用前提） | 同上 |

---

## 2. テーブルごとのデータ量

### 2-1. 1レコード平均サイズの内訳

#### counselors（カウンセラー）

| 要素 | サイズ |
|---|---|
| id (BIGINT) | 8 |
| name (VARCHAR 平均10字 × 3) | 32 |
| user_id (VARCHAR 平均8字) | 10 |
| email (VARCHAR 平均25字、NULL許容) | 27 |
| password (VARCHAR 60字固定、bcrypt) | 62 |
| role (VARCHAR 平均6字) | 8 |
| is_locked, is_active, must_change_password (BOOLEAN×3) | 3 |
| last_login_at, created_at, updated_at (TIMESTAMP×3) | 12 |
| display_order (INTEGER) | 4 |
| 行オーバーヘッド | 30 |
| **合計** | **約 196 バイト** |

#### clients（クライアント）

50項目の集約テーブル。NULL率を考慮し、1行あたり平均的に充填されるサイズを推定。

| 要素 | サイズ（平均） |
|---|---|
| 固定長カラム（id, support_status_id, primary_counselor_id, BOOLEAN×4, DATE×2, INTEGER×1, TIMESTAMP×2 等） | 約 60 |
| VARCHAR系 約30カラム（氏名・かな・電話・住所・各プルダウン値、平均15字 × 3バイト × 50%充填率） | 約 700 |
| TEXT系 約7カラム（education_detail / employment_detail / disability_detail / hospital / medication / financial_detail / cooperating_agencies）：平均200字 × 3バイト × 30%充填率 | 約 1,300 |
| 行オーバーヘッド | 30 |
| **合計** | **約 2,100 バイト ≒ 2.1 KB** |

#### counseling_records（相談記録）★ 最大ボリューム

| 要素 | サイズ（平均） |
|---|---|
| 固定長：id, client_id, consultation_type_id, counselor1_id, counselor2_id, phase_id (BIGINT×6) | 48 |
| consultation_date, consultation_time, created_at, updated_at | 14 |
| is_intake, is_followup (BOOLEAN×2) | 2 |
| consultation_detail (VARCHAR 平均30字 × 3) | 92 |
| record_content (TEXT 1,500字 × 3) | 4,500 |
| impression (TEXT 500字 × 3) | 1,500 |
| attendance, consultation_format (VARCHAR 平均8字 × 3) | 26 |
| consultation_format_detail (VARCHAR 平均20字 × 3、30%充填率) | 20 |
| 行オーバーヘッド | 30 |
| **合計** | **約 6,230 バイト ≒ 6.1 KB** |

#### counseling_participants（相談参加者）

| 要素 | サイズ |
|---|---|
| id, counseling_record_id (BIGINT×2) | 16 |
| participant_type (VARCHAR 平均6字 × 3) | 20 |
| participant_detail (VARCHAR 平均20字 × 3、70%充填) | 50 |
| created_at | 4 |
| 行オーバーヘッド | 30 |
| **合計** | **約 120 バイト** |

#### audio_files（音声ファイル＋文字起こし＋要約）★ 単行サイズ大

| 要素 | サイズ（平均） |
|---|---|
| 固定長：id, user_id, duration_seconds, file_size, summarized_at, created_at, updated_at | 48 |
| status, source (VARCHAR 平均10字) | 22 |
| title, file_name (VARCHAR 平均20字 × 3 × 2) | 124 |
| file_path (VARCHAR 平均60字) | 62 |
| transcription_text (LONGTEXT 5,000字 × 3) | 15,000 |
| summary_text (LONGTEXT 1,000字 × 3) | 3,000 |
| 行オーバーヘッド | 30 |
| **合計** | **約 18,300 バイト ≒ 18 KB** |

#### マスタ系（consultation_types / phases / support_statuses）

各 ~80バイト × 10〜20件 = 各テーブル 1〜2 KB

#### system_settings

~150バイト × 10件 = 1.5 KB

#### access_logs（アクセスログ）

| 要素 | サイズ |
|---|---|
| id, counselor_id, target_id (BIGINT×3) | 24 |
| action (VARCHAR 平均20字) | 22 |
| target_type (VARCHAR 平均15字、80%充填) | 17 |
| ip_address (VARCHAR 平均15字) | 17 |
| user_agent (VARCHAR 平均150字、ブラウザ固有) | 152 |
| created_at, updated_at | 8 |
| 行オーバーヘッド | 30 |
| **合計** | **約 270 バイト** |

#### login_attempts

| 要素 | サイズ |
|---|---|
| id, counselor_id (BIGINT×2) | 16 |
| user_id_input (VARCHAR 平均10字) | 12 |
| ip_address (VARCHAR 平均15字) | 17 |
| attempted_at (TIMESTAMP) | 4 |
| success (BOOLEAN) | 1 |
| 行オーバーヘッド | 30 |
| **合計** | **約 80 バイト** |

#### backup_histories

| 要素 | サイズ |
|---|---|
| id, file_size, created_by (BIGINT×3) | 24 |
| file_name (VARCHAR 平均40字) | 42 |
| file_path (VARCHAR 平均120字) | 122 |
| backup_type (VARCHAR 6字) | 8 |
| created_at | 4 |
| 行オーバーヘッド | 30 |
| **合計** | **約 230 バイト** |

#### sessions

平均 ~500バイト × 20アクティブ = ~10 KB

#### ip_whitelist

~80バイト × 20件 = ~1.6 KB

#### client_intake_tokens

~250バイト × 1,000件（10年で発行・蓄積）= ~250 KB

### 2-2. テーブル別合計サイズ

| テーブル | 平均ケース 件数 | 平均ケース 容量 | 最大ケース 件数 | 最大ケース 容量 |
|---|---:|---:|---:|---:|
| counselors | 20 | 4 KB | 20 | 4 KB |
| clients | 1,000 | 2.1 MB | 1,000 | 2.1 MB |
| **counseling_records** | 200,000 | **1.22 GB** | 500,000 | **3.05 GB** |
| counseling_participants | 400,000（×2倍） | 48 MB | 1,500,000（×3倍） | 180 MB |
| **audio_files** | 100,000（×0.5倍） | **1.83 GB** | 250,000（×0.5倍） | **4.58 GB** |
| consultation_types | 20 | 2 KB | 20 | 2 KB |
| phases | 20 | 2 KB | 20 | 2 KB |
| support_statuses | 20 | 2 KB | 20 | 2 KB |
| system_settings | 10 | 1.5 KB | 10 | 1.5 KB |
| ip_whitelist | 20 | 1.6 KB | 20 | 1.6 KB |
| sessions | 20 | 10 KB | 20 | 10 KB |
| login_attempts（6ヶ月分） | 約 36,000 | 2.9 MB | 36,000 | 2.9 MB |
| backup_histories（10年分） | 3,650 | 0.84 MB | 3,650 | 0.84 MB |
| access_logs（1年分） | 730,000 | 197 MB | 730,000 | 197 MB |
| client_intake_tokens | 1,000 | 250 KB | 1,000 | 250 KB |
| **テーブル合計（データ部）** | | **約 3.30 GB** | | **約 8.01 GB** |

> **計算式の例**：counseling_records 平均ケース = 200,000件 × 6,230バイト = 1,246,000,000バイト ≒ 1.22 GB

---

## 3. インデックス領域の見積もり

### 3-1. ルール

InnoDB の B+tree 二次インデックスは概ね「(キー長 + PK長 + 10バイト程度)」で見積もり。
1インデックスあたり、対象列の長さに依存して 30〜80バイト／行 程度を加算。

### 3-2. 主要テーブルのインデックス容量

| テーブル | インデックス数 | 平均ケース | 最大ケース |
|---|---:|---:|---:|
| counselors | 4 | 微小 | 微小 |
| clients | 8（複合含む） | 約 1.0 MB | 約 1.0 MB |
| counseling_records（FULLTEXT 除く） | 8 | 約 80 MB | 約 200 MB |
| counseling_records FULLTEXT（**未実装**、参考） | 1 | （実装時 +約 1.2 GB） | （実装時 +約 3.0 GB） |
| counseling_participants | 2 | 約 16 MB | 約 60 MB |
| audio_files | 4 | 約 16 MB | 約 40 MB |
| マスタ系×3 | 各3 | 微小 | 微小 |
| access_logs（1年） | 3 | 約 60 MB | 約 60 MB |
| login_attempts（6ヶ月） | 3 | 約 4 MB | 約 4 MB |
| その他 | — | 微小 | 微小 |
| **インデックス合計（FULLTEXT 除く）** | | **約 180 MB** | **約 370 MB** |
| **インデックス合計（FULLTEXT 実装時）** | | **約 1.4 GB** | **約 3.4 GB** |

### 3-3. データ＋インデックス合計（MySQL）

| | 平均ケース | 最大ケース |
|---|---:|---:|
| データ部 | 3.30 GB | 8.01 GB |
| インデックス（FULLTEXT 除く） | 0.18 GB | 0.37 GB |
| **MySQL DB 合計（FULLTEXT 未実装）** | **約 3.5 GB** | **約 8.4 GB** |
| **MySQL DB 合計（FULLTEXT 実装時、参考）** | **約 4.7 GB** | **約 11.4 GB** |

---

## 4. 想定外領域（DB以外のサーバーストレージ）

### 4-1. 音声ファイル（直近7日保持）

仮定：
- 1日あたり録音件数 = 20名 × 平均3セッション × 50%録音率 = 30件／日
- 1ファイル平均サイズ = 30分 × 1MB/分 = 30 MB（WebM Opus / M4A）

| 項目 | 計算 | 容量 |
|---|---|---:|
| 1日分 | 30件 × 30 MB | 0.9 GB |
| 7日分 | 0.9 GB × 7 | **約 6.3 GB** |

ばらつきが大きいため、5〜15 GB の範囲を想定。

### 4-2. バックアップファイル（直近7世代）

仮定：
- 1回あたりバックアップ = MySQL DB サイズの 約 50%（gzip 圧縮後の mysqldump）
- 7世代保持

| ケース | 1世代サイズ | 7世代合計 |
|---|---:|---:|
| 平均ケース（DB 3.5 GB） | 約 1.8 GB | **約 12 GB** |
| 最大ケース（DB 8.4 GB） | 約 4.2 GB | **約 30 GB** |

### 4-3. ログファイル（Laravel + Apache）

仮定：
- Laravel ログ：1日あたり 30 MB、90日ローテーション
- Apache access_log + error_log：1日あたり 50 MB、90日ローテーション
- 合計 80 MB/日 × 90日 = **約 7 GB**

ローテーション設定を行わない場合、10年で 290 GB に達するため必須。

---

## 5. 合計

### 5-1. 平均ケース

| 領域 | 容量 |
|---|---:|
| MySQL DB（データ＋インデックス、FULLTEXT 未実装） | 3.5 GB |
| 音声ファイル（直近7日） | 6 GB |
| バックアップ（7世代） | 12 GB |
| ログファイル（90日ローテーション） | 7 GB |
| **合計** | **約 28 GB** |

### 5-2. 最大ケース

| 領域 | 容量 |
|---|---:|
| MySQL DB（データ＋インデックス、FULLTEXT 未実装） | 8.4 GB |
| 音声ファイル（直近7日） | 15 GB |
| バックアップ（7世代） | 30 GB |
| ログファイル（90日ローテーション） | 7 GB |
| **合計** | **約 60 GB** |

### 5-3. 最大ケース＋FULLTEXT 実装＋音声多めの保守的見積もり

| 領域 | 容量 |
|---|---:|
| MySQL DB（FULLTEXT 含む） | 11.4 GB |
| 音声ファイル（録音率高め） | 20 GB |
| バックアップ（7世代） | 35 GB |
| ログファイル | 10 GB |
| **合計** | **約 76 GB** |

---

## 6. さくら共有サーバー（スタンダードプラン、300GB）との比較

| ケース | 使用容量 | 使用率 | 残容量 |
|---|---:|---:|---:|
| 平均ケース | 28 GB | 9% | 272 GB |
| 最大ケース | 60 GB | 20% | 240 GB |
| 保守的見積もり | 76 GB | 25% | 224 GB |

### 6-1. 容量逼迫リスクの評価

**結論：300GB の容量は10年運用において十分な余裕がある**。

ただし、以下のシナリオで逼迫の可能性あり：

| シナリオ | 影響度 | 対応の必要性 |
|---|---|---|
| アクセスログのクリーンアップ未実施で10年放置 | 中（+約 2 GB） | 中：定期削除バッチが必要 |
| 音声の保存期間延長（30日など） | 中（+20〜60 GB） | 中：システム設定の見直し時に容量確認 |
| バックアップ世代数の増加（30世代など） | 大（+90 GB） | 高：保持期間と容量のバランス検討 |
| 想定規模の大幅超過（クライアント1万名など） | 大 | 高：別プラン移行を検討 |
| record_content/transcription_text が想定の3倍 | 大（DB が ~25 GB に） | 中：実運用データで再評価 |

### 6-2. 対応策の提案

**優先度：高**

1. **ログのローテーション設定を必須化**
   - Apache: logrotate で 90日保持
   - Laravel: `config/logging.php` で daily channel + 90日保持
2. **access_logs の自動削除バッチ**
   - 1年以上前のレコードを月次で物理削除
   - もしくは別ファイルへエクスポート＋DB削除
3. **バックアップの自動削除**
   - backup_histories テーブルと連動して 7世代を超えたファイルを削除

**優先度：中**

4. **容量モニタリング**
   - 月次で DBサイズ・ストレージ使用量を確認するバッチ
   - 200GB（67%使用）到達でアラート
5. **記録内容のサイズ実測**
   - 運用開始3ヶ月後に record_content / transcription_text の実平均長を計測し、本試算を更新

---

## 7. パフォーマンス上の懸念

### 7-1. レコード数増加によるクエリ性能

#### counseling_records 50万件時の主要クエリ

| クエリ | 想定インデックス | 想定性能 | リスク |
|---|---|---|---|
| `SELECT * FROM counseling_records WHERE client_id = ? ORDER BY consultation_date DESC` | counseling_records_client_date_idx | 1〜10ms | 低：複合インデックスでカバー |
| `SELECT * FROM counseling_records WHERE consultation_date BETWEEN ? AND ?` | counseling_records_date_idx | 範囲による（数百ms） | 中：日付範囲が広いとフルスキャン的になる |
| キーワード検索（record_content LIKE '%...%'） | （該当なし、フルスキャン） | **数秒〜数十秒** | **高：FULLTEXT 必須** |
| 担当別検索（counselor1 / counselor2 のOR） | counselor1_idx / counselor2_idx | 別個に検索→マージで数百ms | 中：UNION で対応 |

#### audio_files 25万件時

| クエリ | 想定インデックス | リスク |
|---|---|---|
| 一覧取得（直近順） | audio_files_created_at_idx | 低 |
| LONGTEXT のフルスキャン | （該当なし） | **transcription_text 全件読みは数十秒以上** |

### 7-2. インデックス設計上の配慮点

1. **FULLTEXT インデックスの実装は最大ケース前に必須**
   - 現状「未実装」だが、相談記録10万件超でキーワード検索を実用化するには必要
   - 容量増（最大3 GB）と更新コストを許容するか要判断
2. **counseling_records_client_date_idx の方向問題**
   - DB設計書の注記通り、降順指定なし。MySQL 8.0は降順インデックス対応のため、Laravel 制約を超えて生 SQL マイグレーションで作成するのも検討に値する
   - 現状性能で十分ならそのままで可
3. **access_logs の複合インデックス**
   - target_type + target_id の複合インデックスは未定義。「特定のクライアントを誰が閲覧したか」という監査クエリで遅くなる可能性
4. **未実装インデックスの状態管理**
   - `counseling_records_fulltext_idx`、`support_statuses_dashboard_idx` が未実装。実装時の影響を別途試算する

### 7-3. 想定規模を超えそうな場合の閾値

| 指標 | 警戒閾値 | 限界閾値（プラン見直し検討） |
|---|---:|---:|
| 相談記録総数 | 100万件 | 300万件 |
| MySQL DB サイズ | 30 GB | 100 GB |
| サーバー全体使用量 | 200 GB（67%） | 270 GB（90%） |
| record_content 平均長 | 5,000字超 | 10,000字超 |
| audio_files 件数 | 50万件 | 100万件 |

**プラン見直しの目安**：
- さくらのレンタルサーバー プレミアム（400GB）または ビジネス（600GB）に移行
- それでも不足する場合はクラウド（VPS / クラウドサーバー）への移行検討

---

## 8. 注記・補足

- 本試算は **桁感の把握** が目的。±50% 程度の誤差は許容範囲とする
- 運用開始から 3ヶ月・1年・3年のタイミングで実データを使った再試算を推奨
- 試算で最も支配的な要素は次の3つ：
  1. counseling_records.record_content の平均長（仮定 1,500字）
  2. audio_files の作成率（仮定 50%）と transcription_text の平均長（仮定 5,000字）
  3. アクセスログ／ログファイルのクリーンアップ運用が回るかどうか
- これらの仮定が崩れると試算結果が大きく変動するため、運用開始後の実測が重要

---

## 試算実施履歴

- 2026-05-07：初回試算実施（要件定義書 4-3 利用想定の妥当性確認のため）
  - 想定：クライアント1,000名 × 平均200件・最大500件
  - 結果：300GBに対する使用率は最大25%程度、容量逼迫リスクなし
  - 残タスク：tests/bugs.md に登録（access_logs自動削除、FULLTEXTインデックス、複合インデックス降順、3ヶ月後の再試算）
