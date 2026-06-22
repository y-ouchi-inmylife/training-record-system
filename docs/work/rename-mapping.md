# 英語識別子リネーム 新旧対応表

## 訳語方針（語幹レベル）

| 旧語幹 | 新語幹 | 備考 |
|---|---|---|
| counseling | training | カウンセリング記録 → トレーニング記録 |
| counselor | trainer | カウンセラー → トレーナー |
| consultation | training | α案：全部 training_ に統一 |

**対象外（温存）:**
- `record_content`（記録内容、中立的な名前）
- `initial_consultation_date`（初回日、別概念。後述の注意参照）
- `client_intake_tokens` 系（URL発行クライアント登録機能、語幹を含まない別物）

---

## A. テーブル名

| 旧 | 新 |
|---|---|
| counseling_records | training_records |
| counselors | trainers |
| consultation_types | training_types |

---

## B. カラム名

| テーブル | 旧カラム | 新カラム |
|---|---|---|
| training_records | consultation_date | training_date |
| training_records | consultation_time | training_time |
| training_records | consultation_type_id | training_type_id |
| training_records | consultation_detail | training_detail |
| training_records | counselor1_id | trainer1_id |
| training_records | counselor2_id | trainer2_id |
| clients | primary_counselor_id | primary_trainer_id |
| (各テーブル) | created_by / updated_by | （変更なし。FK先が trainers になるだけ） |

**注意:** `initial_consultation_date`（clients テーブル、初回日）は consultation を含むが**別概念のため変更しない**。リネーム時に巻き込まないこと。

---

## C. 制約・インデックス名

### 外部キー（training_records）

| 旧 | 新 |
|---|---|
| counseling_records_client_id_foreign | training_records_client_id_foreign |
| counseling_records_counselor1_id_foreign | training_records_trainer1_id_foreign |
| counseling_records_counselor2_id_foreign | training_records_trainer2_id_foreign |
| counseling_records_consultation_type_id_foreign | training_records_training_type_id_foreign |
| counseling_records_phase_id_foreign | training_records_phase_id_foreign |
| counseling_records_updated_by_foreign | training_records_updated_by_foreign |

### インデックス

| 旧 | 新 |
|---|---|
| counseling_records_client_date_idx | training_records_client_date_idx |
| counseling_records_date_idx | training_records_date_idx |
| counseling_records_counselor1_idx | training_records_trainer1_idx |
| counseling_records_counselor2_idx | training_records_trainer2_idx |
| counseling_records_type_idx | training_records_type_idx |
| counseling_records_phase_idx | training_records_phase_idx |
| counselors_display_order_index | trainers_display_order_index |
| consultation_types_order_idx | training_types_order_idx |

### その他制約（clients / counselors など、棚卸しで未列挙のものは実ファイル確認時に追加）

| 旧 | 新 |
|---|---|
| consultation_types_name_unique | training_types_name_unique |
| consultation_types_sort_check | training_types_sort_check |
| counselors_login_id_unique | trainers_login_id_unique |
| counselors_role_check | trainers_role_check |
| counselors_login_id_check | trainers_login_id_check |
| clients_primary_counselor_id_foreign | clients_primary_trainer_id_foreign |
| phases_*（counselingを含まない） | （変更なし） |

※ この節は棚卸しで全制約名が出きっていないため、実装フェーズで各マイグレーションを確認して補完する。

---

## D. モデルクラス名 ↔ ファイル名

| 旧クラス | 新クラス | 旧ファイル | 新ファイル |
|---|---|---|---|
| CounselingRecord | TrainingRecord | app/Models/CounselingRecord.php | app/Models/TrainingRecord.php |
| Counselor | Trainer | app/Models/Counselor.php | app/Models/Trainer.php |
| ConsultationType | TrainingType | app/Models/ConsultationType.php | app/Models/TrainingType.php |

---

## E. コントローラクラス名 ↔ ファイル名

| 旧クラス | 新クラス |
|---|---|
| CounselingRecordController | TrainingRecordController |
| CounselorController | TrainerController |
| ConsultationTypeController | TrainingTypeController |

（ファイル名も同様に app/Http/Controllers/ 配下でリネーム）

---

## F. その他クラス・コマンド

| 旧 | 新 | 備考 |
|---|---|---|
| LockUnusedCounselors | LockUnusedTrainers | Console コマンドクラス |
| LockInactiveCounselors | LockInactiveTrainers | Console コマンドクラス |
| counselors:lock-unused（signature） | trainers:lock-unused | コマンド signature。スケジューラ・cron 設定の確認要 |
| counselors:lock-inactive（signature） | trainers:lock-inactive | 同上 |
| LogAccess の ACTION_MAP の counseling-records.* キー | training-records.* | ルート名変更と連動 |

---

## G. Eloquent リレーションメソッド名

| モデル | 旧メソッド | 新メソッド |
|---|---|---|
| TrainingRecord | consultationType() | trainingType() |
| TrainingRecord | counselor1() | trainer1() |
| TrainingRecord | counselor2() | trainer2() |
| TrainingRecord | updatedBy() | （変更なし） |
| Trainer | counselingRecordsAsCounselor1() | trainingRecordsAsTrainer1() |
| Trainer | counselingRecordsAsCounselor2() | trainingRecordsAsTrainer2() |
| Trainer | primaryClients() | （変更なし。primary_trainer_id を見るが名前は中立） |
| TrainingType | counselingRecords() | trainingRecords() |

※ リレーション名を変えると、それを参照する with()/withCount()/load()/$model->relation すべてが連動。

---

## H. ルート名・URLセグメント

| 旧ルート名 | 新ルート名 | 旧URL | 新URL |
|---|---|---|---|
| counseling-records.* | training-records.* | /counseling-records | /training-records |
| api.counseling-records.auto-create | api.training-records.auto-create | /api/counseling-records/auto-create | /api/training-records/auto-create |
| counselors.* | trainers.* | /counselors | /trainers |
| api.counselors.list | api.trainers.list | /api/counselors/list | /api/trainers/list |
| master.consultation-types.* | master.training-types.* | /master/consultation-types | /master/training-types |

（* は index, create, show, store, edit, update, destroy 等のアクション全て。counselors.* には unlock, toggle-active, move-up, move-down, reset-password.* も含む）

---

## I. route() ヘルパー参照（~75箇所）

H のルート名変更に伴い、`route('counseling-records.show')` → `route('training-records.show')` のように全置換。Blade + Controller で約75箇所。

---

## J. Blade ファイル・ディレクトリ名

| 旧 | 新 |
|---|---|
| resources/views/counseling-records/ | resources/views/training-records/ |
| resources/views/counselors/ | resources/views/trainers/ |
| resources/views/master/consultation-types/ | resources/views/master/training-types/ |

---

## K. view() 参照

| 旧 | 新 |
|---|---|
| view('counseling-records.*') | view('training-records.*') |
| view('counselors.*') | view('trainers.*') |
| view('master.consultation-types.index') | view('master.training-types.index') |

---

## L. DOM ID / HTML name属性 / JavaScript

| 旧 | 新 |
|---|---|
| id="counselingRecordForm" / #counselingRecordForm | id="trainingRecordForm" / #trainingRecordForm |
| btn-preset-counseling | **変更しない**（要約プロンプトのプリセット、温存方針。特記4参照） |
| name="counselor1_id" | name="trainer1_id" |
| name="counselor2_id" | name="trainer2_id" |
| name="counselor_id"（検索フィルタ） | name="trainer_id" |
| name="consultation_date" | name="training_date" |
| name="consultation_time" | name="training_time" |
| name="consultation_type_id" | name="training_type_id" |
| name="consultation_detail" | name="training_detail" |

---

## M. バリデーションルール内のテーブル/カラム参照

| 旧 | 新 |
|---|---|
| exists:counseling_records,id | exists:training_records,id |
| exists:counselors,id | exists:trainers,id |
| unique:counselors,login_id | unique:trainers,login_id |
| exists:consultation_types,id | exists:training_types,id |

---

## N. 設計書（docs/）

requirements.md / db-schema.md / api-design.md / screen-design.md / batch-design.md / doc-index.md に書かれた全ての英語識別子（テーブル名・カラム名・制約名・ルート名・DOM ID）を上記対応表に従って更新。

---

## O. その他（lang / tests / seeders）

| ファイル | 旧 | 新 |
|---|---|---|
| lang/ja/validation.php | attributes の counselor1_id / counselor2_id / primary_counselor_id | trainer1_id / trainer2_id / primary_trainer_id |
| lang/ja/validation.php | attributes の consultation_date / consultation_type_id 等 | training_date / training_type_id 等 |
| database/seeders/ | ConsultationTypeSeeder.php → TrainingTypeSeeder.php、Counselor 参照 → Trainer | クラス名・モデル参照 |
| tests/ | route名 counseling-records.* / counselors.* | training-records.* / trainers.* |

---

## 特記事項・要注意点

1. **initial_consultation_date は変更しない**（clients テーブルの初回日。consultation を含むが別概念）。
2. **record_content は変更しない**（中立的な名前）。
3. **client_intake_tokens 系は対象外**（語幹を含まない別機能）。
4. **btn-preset-counseling / summary_prompt_preset_counseling は変更しない（確定）**。要約プロンプトの「心理カウンセリング用」プリセットは過去に意図的な温存方針が決まっており、この counseling は「カウンセリング業務向けプロンプト」という意味で残す。リネーム後、システム全体で counseling が残るのはこのプリセット関連のみ（key名・ボタンID・コメント）。これは設計判断による意図的な残置であり、リネーム漏れではない。
5. **created_by / updated_by カラム自体は変更なし**（FK先が trainers になるだけ）。
6. **制約名（C節）は棚卸しで全部出きっていない**ため、実装フェーズで各マイグレーションを開いて全制約名を確認・補完する。
7. **段階的に進める**。一度に全部やらず、独立性の高いもの（DOM ID、docs、view名）から、連動の大きいもの（テーブル名・カラム名・クラス名）へ。各段階で migrate:fresh --seed と test green を確認。
