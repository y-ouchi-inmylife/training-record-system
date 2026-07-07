**位置づけ**: 仕様文書（API設計書）
**対象読者**: 開発者
**上位文書**: requirements.md（機能一覧、6章）
**詳細**: 詳細は doc-index.md を参照

---

# API設計書: トレーニング記録管理システム

## 1. 認証方式

- Laravelの標準セッション認証（Cookie + CSRFトークン）を使用する。
- JWT（トークンベース認証）は使用しない。ブラウザ経由の利用のみのため、セッション認証で十分。
- トレーナー（`web` ガード）とクライアント（`client` ガード）でセッションを分離する。本番ではサブドメインを分け（トレーナー用 `mikan-trs01-staff.inmylife1965.com` / クライアント用 `mikan.inmylife1965.com`）、`SESSION_DOMAIN` を各サブドメイン限定にしたうえで、セッション Cookie 名も役割別に別名化する（トレーナー用 `trs01-staff-session` / クライアント用 `trs01-client-session`）。
- セッション有効期限（時間経過ログアウト）はトレーナー／クライアントで別の固定値とし、ミドルウェアがリクエストのサブドメイン（またはガード）を判定して、Cookie 名と有効期限をセットで動的に切り替える。
- サブドメイン構成・ホスト名管理（`config/subdomain.php`）の詳細は、アーキテクチャ設計書（architecture.md 2-4 サブドメイン構成）を参照。

---

## 2. 共通ミドルウェア

認証後の全エンドポイントに横断的に適用される挙動を記述する。これらは routes/web.php のルート全体に適用され、各エンドポイントの詳細では繰り返さない。リクエストは、エンドポイントの処理に到達する前に、以下のミドルウェアを順に通過する。

### 2-1. CheckIpRestriction（IPアドレス制限）

IP アドレス制限は、**トレーナー用サブドメイン（内部）のルートにのみ適用する**。クライアント用サブドメイン（外部。クライアント本人が自宅・スマートフォン等、機関の許可 IP 外から利用する）には適用しない。これにより、従来の「全体に適用したうえでクライアント閲覧機能を対象外にする」という例外構造が不要になる。

トレーナー用サブドメインにおいて、IP アドレス制限が有効で許可リストが登録されている場合、許可された IP アドレス以外からのアクセスを遮断する（403）。許可リストは完全一致または CIDR 範囲で判定する。IP アドレス制限が無効、または許可リストが空の場合は、すべて許可する。

ただし、トレーナー用サブドメイン内でも次のアクセスは制限の対象外とする。
- ログイン・ログアウト（ログイン画面には到達させ、ログイン後の業務機能で制御する）
- システム管理者
- ローカルホストからのアクセス（開発環境。ローカルホストは常に許可されるため、開発環境では IP 制限による遮断は発生しない）

### 2-2. CheckPasswordChange（強制パスワード変更）

初回ログイン等でパスワード変更が必要な状態のユーザーを、強制パスワード変更画面（S-0102）へ誘導する（警告メッセージ：「初回ログインのため、パスワードを変更してください。」）。パスワード変更画面・ログアウト以外のすべてのエンドポイントが対象。パスワード変更を完了すると、通常どおり利用できる。

### 2-3. LogAccess（操作履歴の記録）

クライアント・トレーニング記録の参照・登録・更新・削除が成功したとき、操作者・操作種別・操作対象・IPアドレス・ユーザーエージェントを、トレーナー操作履歴（access_logs）に記録する。記録される操作種別は、DB設計書の access_logs テーブル定義を参照。

なお、ログイン・ログアウトの記録は、このミドルウェアではなく、それぞれのエンドポイントの処理で行う。

---


## 3. エンドポイント一覧

**記載範囲**:
- 本章では、本システムが提供する全エンドポイントを列挙する。

| カテゴリ | 対応画面 | メソッド | パス | 概要 | 認証 | 権限 |
|---------|---------|------|------|------|------|-----------|
| 認証 | S-0101 ログイン画面 | GET | `/login` | ログイン画面を表示する | guest | - |
| 認証 | S-0101 ログイン画面 | POST | `/login` | ログインする | guest | - |
| 認証 | S-0102 強制パスワード変更画面 | GET | `/password/change` | パスワード変更画面を表示する | auth | 全員 |
| 認証 | S-0102 強制パスワード変更画面 | POST | `/password/change` | パスワードを変更する | auth | 全員 |
| ダッシュボード | S-0201 ダッシュボード画面 | GET | `/dashboard` | ダッシュボード画面を表示する | auth | 管理者、一般 |
| クライアント管理 | S-0301 クライアント登録画面 | GET | `/clients/create` | クライアント登録画面を表示する | auth | 管理者、一般 |
| クライアント管理 | S-0301 クライアント登録画面 | POST | `/clients` | クライアントを登録する | auth | 管理者、一般 |
| クライアント管理 | S-0302 クライアント登録（URL発行）管理画面 | GET | `/client-intake-tokens` | クライアント登録（URL発行）管理画面を表示する | auth | 管理者、一般 |
| クライアント管理 | S-0302 クライアント登録（URL発行）管理画面 | POST | `/client-intake-tokens` | ワンタイムURLを発行する | auth | 管理者、一般 |
| クライアント管理 | S-0302 クライアント登録（URL発行）管理画面 | DELETE | `/client-intake-tokens/{id}` | 発行済みURLを削除する | auth | 管理者、一般 |
| クライアント管理 | S-0303 クライアント登録（URL発行）画面 | GET | `/client-intake/token/{token}` | トークンチェックしてクライアント登録（URL発行）画面を表示する | public | - |
| クライアント管理 | S-0303 クライアント登録（URL発行）画面 | POST | `/client-intake/token/{token}` | クライアントを登録する | public | - |
| クライアント管理 | S-0304 クライアント一覧画面 | GET | `/clients` | クライアント一覧画面を表示する | auth | 管理者、一般 |
| クライアント管理 | S-0305 クライアント詳細画面 | GET | `/clients/{id}` | クライアント詳細画面を表示する | auth | 管理者、一般 |
| クライアント管理 | S-0305 クライアント詳細画面 | POST | `/clients/{client}/release-view` | クライアントの閲覧を解放し招待メールを送信する | auth | 管理者、一般 |
| クライアント管理 | S-0305 クライアント詳細画面 | POST | `/clients/{client}/revoke-view` | クライアントの閲覧解放を取り消し、解放前の状態に戻す | auth | 管理者、一般 |
| クライアント管理 | S-0305 クライアント詳細画面 | DELETE | `/clients/{id}` | クライアントを削除する | auth | 管理者、一般 |
| クライアント管理 | S-0306 クライアント編集画面 | GET | `/clients/{id}/edit` | クライアント編集画面を表示する | auth | 管理者、一般 |
| クライアント管理 | S-0306 クライアント編集画面 | PUT | `/clients/{id}` | クライアント情報を更新する | auth | 管理者、一般 |
| トレーニング記録管理 | S-0401 トレーニング記録登録画面 | GET | `/training-records/create` | トレーニング記録登録画面を表示する | auth | 管理者、一般 |
| トレーニング記録管理 | S-0401 トレーニング記録登録画面 | POST | `/training-records` | トレーニング記録を新規登録する | auth | 管理者、一般 |
| トレーニング記録管理 | S-0402 トレーニング記録一覧画面 | GET | `/training-records` | トレーニング記録一覧画面を表示する | auth | 管理者、一般 |
| トレーニング記録管理 | S-0403 トレーニング記録詳細画面 | GET | `/training-records/{id}` | トレーニング記録詳細画面を表示する | auth | 管理者、一般 |
| トレーニング記録管理 | S-0403 トレーニング記録詳細画面 | DELETE | `/training-records/{id}` | トレーニング記録を削除する | auth | 管理者、一般 |
| トレーニング記録管理 | S-0404 トレーニング記録編集画面 | GET | `/training-records/{id}/edit` | トレーニング記録編集画面を表示する | auth | 管理者、一般 |
| トレーニング記録管理 | S-0404 トレーニング記録編集画面 | PUT | `/training-records/{id}` | トレーニング記録を更新する | auth | 管理者、一般 |
| 音声記録管理 | S-0501 録音準備画面 | GET | `/recording-v2` | 録音準備画面を表示する | auth | 管理者、一般 |
| 音声記録管理 | S-0501 録音準備画面 | POST | `/recording-v2/start` | 録音実行画面に遷移する | auth | 管理者、一般 |
| 音声記録管理 | S-0502 録音実行画面 | GET | `/recording-v2/session` | 録音実行画面を表示する | auth | 管理者、一般 |
| 音声記録管理 | S-0502 録音実行画面 | POST | `/audio-records/recording` | 録音した音声をアップロードして音声記録を作成する | auth | 管理者、一般 |
| 音声記録管理 | S-0503 音声記録登録（文字起こしテキスト）画面 | GET | `/audio-records/text-paste/create` | 音声記録登録（文字起こしテキスト）画面を表示する | auth | 管理者、一般 |
| 音声記録管理 | S-0503 音声記録登録（文字起こしテキスト）画面 | POST | `/audio-records/text-paste` | 文字起こしテキストから音声記録を登録する | auth | 管理者、一般 |
| 音声記録管理 | S-0504 音声記録登録（音声ファイルのアップロード）画面 | GET | `/audio-records/upload/create` | 音声記録登録（音声ファイルのアップロード）画面を表示する | auth | 管理者、一般 |
| 音声記録管理 | S-0504 音声記録登録（音声ファイルのアップロード）画面 | POST | `/audio-records/upload` | 音声ファイルをアップロードして音声記録を登録する | auth | 管理者、一般 |
| 音声記録管理 | S-0505 音声記録一覧画面 | GET | `/audio-records` | 音声記録一覧画面を表示する | auth | 管理者、一般 |
| 音声記録管理 | S-0505 音声記録一覧画面 | GET | `/audio-records/{id}` | 音声記録の詳細を取得する | auth | 管理者、一般 |
| 音声記録管理 | S-0505 音声記録一覧画面 | PUT | `/audio-records/{id}` | 音声記録のタイトル・文字起こしテキスト・要約テキストを編集する | auth | 管理者、一般 |
| 音声記録管理 | S-0505 音声記録一覧画面 | DELETE | `/audio-records/{id}` | 音声記録を削除する | auth | 管理者、一般 |
| 音声記録管理 | S-0505 音声記録一覧画面 | DELETE | `/audio-records/{id}/delete-audio` | 音声記録の音声ファイルのみ削除する（文字起こし・要約は保持） | auth | 管理者、一般 |
| 音声記録管理 | S-0505 音声記録一覧画面 | GET | `/audio-records/{id}/play` | 音声ファイルを再生する（ストリーミング） | auth | 管理者、一般 |
| 要約 | S-0601 要約プロンプト画面 | GET | `/settings/summary-prompts` | 要約プロンプト画面を表示する | auth | 管理者 |
| 要約 | S-0601 要約プロンプト画面 | PUT | `/settings/summary-prompts` | 要約プロンプトを更新する | auth | 管理者 |
| 音声ファイル容量管理 | S-0701 音声ファイル一覧画面 | GET | `/usage-stats` | 音声ファイル一覧画面を表示する（サーバー容量管理用） | auth | システム管理者 |
| トレーナー管理 | S-0801 トレーナー登録画面 | GET | `/trainers/create` | トレーナー登録画面を表示する | auth | 管理者 |
| トレーナー管理 | S-0801 トレーナー登録画面 | POST | `/trainers` | トレーナーを登録する | auth | 管理者 |
| トレーナー管理 | S-0802 トレーナー管理画面 | GET | `/trainers` | トレーナー管理画面を表示する | auth | 管理者 |
| トレーナー管理 | S-0802 トレーナー管理画面 | PATCH | `/trainers/{id}/move-up` | トレーナーの表示順を上に移動する | auth | 管理者 |
| トレーナー管理 | S-0802 トレーナー管理画面 | PATCH | `/trainers/{id}/move-down` | トレーナーの表示順を下に移動する | auth | 管理者 |
| トレーナー管理 | S-0802 トレーナー管理画面 | PATCH | `/trainers/{id}/unlock` | アカウントロックを解除する | auth | 管理者 |
| トレーナー管理 | S-0802 トレーナー管理画面 | PATCH | `/trainers/{id}/toggle-active` | アカウントの有効/無効を切り替える | auth | 管理者 |
| トレーナー管理 | S-0802 トレーナー管理画面 | DELETE | `/trainers/{id}` | トレーナーを削除する | auth | 管理者 |
| トレーナー管理 | S-0803 トレーナー編集画面 | GET | `/trainers/{id}/edit` | トレーナー編集画面を表示する | auth | 管理者 |
| トレーナー管理 | S-0803 トレーナー編集画面 | PUT | `/trainers/{id}` | トレーナーを更新する | auth | 管理者 |
| トレーナー管理 | S-0804 パスワードリセット画面 | GET | `/trainers/{id}/reset-password` | パスワードリセット画面を表示する | auth | 管理者 |
| トレーナー管理 | S-0804 パスワードリセット画面 | PUT | `/trainers/{id}/reset-password` | パスワードをリセットする | auth | 管理者 |
| トレーナー管理 | S-0805 トレーナー操作履歴画面 | GET | `/access-logs` | トレーナー操作履歴画面を表示する | auth | 管理者 |
| マスタ管理 | S-0901 支援状態マスタ画面 | GET | `/master/support-statuses` | 支援状態マスタ管理画面を表示する | auth | 管理者 |
| マスタ管理 | S-0901 支援状態マスタ画面 | POST | `/master/support-statuses` | 支援状態の選択肢を追加する | auth | 管理者 |
| マスタ管理 | S-0901 支援状態マスタ画面 | PATCH | `/master/support-statuses/{id}/move-up` | 支援状態の表示順を上に移動する | auth | 管理者 |
| マスタ管理 | S-0901 支援状態マスタ画面 | PATCH | `/master/support-statuses/{id}/move-down` | 支援状態の表示順を下に移動する | auth | 管理者 |
| マスタ管理 | S-0901 支援状態マスタ画面 | PUT | `/master/support-statuses/{id}` | 支援状態の選択肢を更新する | auth | 管理者 |
| マスタ管理 | S-0901 支援状態マスタ画面 | DELETE | `/master/support-statuses/{id}` | 支援状態の選択肢を削除する | auth | 管理者 |
| マスタ管理 | S-0902 トレーニング内容マスタ画面 | GET | `/master/training-types` | トレーニング内容マスタ管理画面を表示する | auth | 管理者 |
| マスタ管理 | S-0902 トレーニング内容マスタ画面 | POST | `/master/training-types` | トレーニング内容の選択肢を追加する | auth | 管理者 |
| マスタ管理 | S-0902 トレーニング内容マスタ画面 | PATCH | `/master/training-types/{id}/move-up` | トレーニング内容の表示順を上に移動する | auth | 管理者 |
| マスタ管理 | S-0902 トレーニング内容マスタ画面 | PATCH | `/master/training-types/{id}/move-down` | トレーニング内容の表示順を下に移動する | auth | 管理者 |
| マスタ管理 | S-0902 トレーニング内容マスタ画面 | PUT | `/master/training-types/{id}` | トレーニング内容の選択肢を更新する | auth | 管理者 |
| マスタ管理 | S-0902 トレーニング内容マスタ画面 | DELETE | `/master/training-types/{id}` | トレーニング内容の選択肢を削除する | auth | 管理者 |
| マスタ管理 | S-0903 フェーズマスタ画面 | GET | `/master/phases` | フェーズマスタ管理画面を表示する | auth | 管理者 |
| マスタ管理 | S-0903 フェーズマスタ画面 | POST | `/master/phases` | フェーズの選択肢を追加する | auth | 管理者 |
| マスタ管理 | S-0903 フェーズマスタ画面 | PATCH | `/master/phases/{id}/move-up` | フェーズの表示順を上に移動する | auth | 管理者 |
| マスタ管理 | S-0903 フェーズマスタ画面 | PATCH | `/master/phases/{id}/move-down` | フェーズの表示順を下に移動する | auth | 管理者 |
| マスタ管理 | S-0903 フェーズマスタ画面 | PUT | `/master/phases/{id}` | フェーズの選択肢を更新する | auth | 管理者 |
| マスタ管理 | S-0903 フェーズマスタ画面 | DELETE | `/master/phases/{id}` | フェーズの選択肢を削除する | auth | 管理者 |
| セキュリティ設定 | S-1002 IPアドレス制限画面 | GET | `/settings/ip-restriction` | IPアドレス制限画面を表示する | auth | システム管理者 |
| セキュリティ設定 | S-1002 IPアドレス制限画面 | PUT | `/settings/ip-restriction` | IPアドレス制限設定を更新する | auth | システム管理者 |
| レポート | S-1101 トレーニング記録数推移画面 | GET | `/statistics/clients` | トレーニング記録数推移画面を表示する | auth | 管理者、一般 |
| マイプロフィール | S-1201 マイプロフィール画面 | GET | `/profile` | マイプロフィール画面を表示する | auth | 全員 |
| マイプロフィール | S-1201 マイプロフィール画面 | PUT | `/profile` | プロフィールを更新する | auth | 全員 |
| マイプロフィール | S-1202 パスワード変更画面 | GET | `/profile/password` | パスワード変更画面を表示する | auth | 全員 |
| マイプロフィール | S-1202 パスワード変更画面 | PUT | `/profile/password` | パスワードを変更する | auth | 全員 |
| メディア管理 | S-1302 メディア一覧画面 | GET | `/media-records` | メディア一覧画面を表示する | auth | 管理者、一般 |
| メディア管理 | S-1302 メディア一覧画面 | GET | `/media-records/{id}` | メディアの詳細を取得する | auth | 管理者、一般 |
| メディア管理 | S-1302 メディア一覧画面 | PUT | `/media-records/{id}` | メディアの表示名を更新する | auth | 管理者、一般 |
| メディア管理 | S-1302 メディア一覧画面 | DELETE | `/media-records/{id}` | メディアを削除する（レコード・ファイル実体） | auth | 管理者、一般 |
| 共通 | ログアウト | POST | `/logout` | ログアウトする | auth | 全員 |
| クライアント閲覧 | S-1401 クライアントログイン画面 | GET | `/client-portal/login` | クライアントログイン画面を表示する | guest:client | - |
| クライアント閲覧 | S-1401 クライアントログイン画面 | POST | `/client-portal/login` | クライアントとしてログインする | guest:client | - |
| クライアント閲覧 | - | POST | `/client-portal/logout` | クライアントとしてログアウトする | auth:client | クライアント |
| クライアント閲覧 | S-1402 クライアントダッシュボード画面 | GET | `/client-portal/dashboard` | クライアントダッシュボード画面を表示する | auth:client | クライアント |
| クライアント閲覧 | S-1403 クライアントパスワード設定画面 | GET | `/client-portal/password-setup/{token}` | パスワード設定画面を表示する（トークン検証） | public | - |
| クライアント閲覧 | S-1403 クライアントパスワード設定画面 | POST | `/client-portal/password-setup/{token}` | パスワードを設定する | public | - |
| クライアント閲覧 | S-1404 クライアントトレーニング記録詳細画面 | GET | `/client-portal/training-records/{id}` | クライアントが自分のトレーニング記録の詳細を表示する | auth:client | クライアント |
| クライアント閲覧 | S-1404 クライアントトレーニング記録詳細画面 | GET | `/client-portal/media/{id}/play` | クライアントが自分の記録に紐づくメディアを表示・再生する | auth:client | クライアント |
| 内部API | - | GET | `/api/clients/search` | クライアントを検索する | auth | 管理者、一般 |
| 内部API | - | POST | `/api/training-records/auto-create` | 音声記録の要約からトレーニング記録を作成する | auth | 管理者、一般 |
| 内部API | - | GET | `/api/training-records/available-media` | トレーニング記録に紐づけ可能なメディア一覧を取得する | auth | 管理者、一般 |
| 内部API | - | POST | `/api/audio-records/{id}/transcribe` | 文字起こしを実行する | auth | 管理者、一般 |
| 内部API | - | POST | `/api/audio-records/{id}/summarize` | 要約を実行する | auth | 管理者、一般 |
| 内部API | - | GET | `/api/audio-records/{id}/summary` | 音声記録の要約テキストを取得する | auth | 管理者、一般 |
| 内部API | - | GET | `/api/audio-records/summaries` | 要約取り込み候補の音声記録一覧を取得する | auth | 管理者、一般 |
| 内部API | - | GET | `/api/trainers` | 担当トレーナーの選択候補を取得する | auth | 管理者、一般 |
| 内部API | - | POST | `/api/media-records/upload-url` | アップロード用の署名付きURLを発行する | auth | 管理者、一般 |
| 内部API | - | POST | `/api/media-records` | アップロード完了後、メディアレコードを作成する | auth | 管理者、一般 |
| 内部API | - | GET | `/api/media-records/{id}/play` | メディアを表示・再生する（ストリーミング） | auth | 管理者、一般 |
| 内部API | - | POST | `/api/media-records/{id}/convert` | 表示用変換（heic→jpeg / mov→mp4）を起動する | auth | 管理者、一般 |

※ 認証列は実装のミドルウェア区分を示す。`public`＝認証不要（誰でもアクセス可）、`guest`＝未認証ユーザー向け（ログイン済みはホーム画面へリダイレクト）、`auth`＝要認証（ログイン済みのトレーナー）。（クライアント閲覧機能では guard を明示し、`guest:client`＝未認証のクライアント向け（ログイン済みはクライアントダッシュボードへリダイレクト）、`auth:client`＝要認証（ログイン済みのクライアント）を用いる。）
※ 権限列は認証後のロール制限を示す。`-`＝認証不要のため対象外、`全員`＝ログイン済みの全トレーナー（システム管理者を含む）、`管理者、一般`＝システム管理者を除く実務トレーナー、`管理者`＝管理者のみ、`システム管理者`＝システム管理者のみ。`クライアント`＝ログイン済みのクライアント（飼い主）。

---


## 4. エンドポイント詳細

**記載範囲**:
- 本章では、本システムが提供する全エンドポイントを列挙する。
- 全エンドポイントとは、「3. エンドポイント一覧」で挙げているものを指す。
- エンドポイントを、利用者がブラウザで開く画面（routes/web.php）と、画面のJavaScriptがAjaxで呼び出す内部API（routes/api.php）に分けて記述する。

**参照ルール**:
- 各エンドポイントの認証・権限は、「3. エンドポイント一覧」で管理している。本章では同じメソッド・パスを使用しているため、メソッドとパスで突き合わせて参照すること。

**記載する項目**: 各エンドポイントについて、以下の項目を記載する。
- **概要**（必須）: エンドポイントが何をするかを一文で示す。
- **リクエスト**: 受け取るパラメータの一覧。「パラメータ」「型」「必須」「バリデーション」「説明」の5列で記載する。パラメータがない場合は記載しない。
- **処理**: エンドポイント内の挙動のうち、特記すべきものを記述する。単純な表示など特記事項がない場合は記載しない。
- **レスポンス**（必須）: 返す内容（画面の描画、リダイレクト、JSON）を記載する。

**必須欄の記号**: 「リクエスト」表の「必須」列では、以下の記号を用いる。

| 記号 | 意味 |
|---|---|
| ● | 必須 |
| 空欄 | 任意 |

**バリデーションの補足**: 「リクエスト」表の「バリデーション」列は、Laravelのバリデーションルールを記載する。標準ルール以外のカスタムルールを以下に補足する。

- **StrongPassword**: パスワードの強度をチェックするカスタムルール。8文字以上で、大文字・小文字・数字・記号をそれぞれ1文字以上含み、よくあるパスワード（password 等）を禁止する。


### 4-1. 画面（routes/web.php）

利用者がブラウザで直接開くエンドポイント。`routes/web.php` に定義する。
レスポンスはBladeテンプレートで描画したHTML（画面遷移）。
Laravelのセッション認証（Cookie + CSRF）で保護する。


#### 4-1-1. 認証

##### S-0101 ログイン画面

###### GET /login

**概要**: ログイン画面を表示する。

**レスポンス**:
- 未ログインの場合：view `auth.login`
- ログイン済みの場合：権限に応じたホーム画面へリダイレクト（システム管理者：音声ファイル一覧画面、それ以外：ダッシュボード画面）


###### POST /login

**概要**: ログインする。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| login_id | string | ● | required, string, max:50 | ログインID |
| password | string | ● | required, string | パスワード |

**処理**:
- ログインに連続して5回失敗すると、アカウントがロックされる
- ログイン成功時、ログインを操作履歴に記録する

**レスポンス**:
- 成功：権限に応じたホーム画面へリダイレクト（システム管理者：音声ファイル一覧画面、それ以外：ダッシュボード画面）
- 認証失敗：エラーを表示（「ログインIDまたはパスワードが正しくありません。」）
- 無効化されたアカウントの場合：エラーを表示（「このアカウントは無効化されています。管理者にお問い合わせください。」）
- ロックされたアカウントの場合：エラーを表示（「アカウントがロックされています。管理者に連絡してください。」）

---

##### S-0102 強制パスワード変更画面

###### GET /password/change

**概要**: パスワード変更画面を表示する。

**レスポンス**:
- view `auth.change-password`


###### POST /password/change

**概要**: パスワードを変更する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| new_password | string | ● | required, string, confirmed, StrongPassword | 新しいパスワード |
| new_password_confirmation | string | ● | — | 新しいパスワード（確認） |

**処理**:
- パスワードを更新し、初回ログイン時のパスワード変更を済みにする

**レスポンス**:
- 成功：権限に応じたホーム画面へリダイレクト（システム管理者：音声ファイル一覧画面、それ以外：ダッシュボード画面）

---


#### 4-1-2. ダッシュボード

##### S-0201 ダッシュボード画面

###### GET /dashboard

**概要**: ダッシュボード画面を表示する。

**処理**:
- ログインユーザーが主担当のクライアント一覧を取得
- 支援状態の show_in_dashboard が true、または support_status_id が NULL のものに絞り込み
- 最終記録日が新しい順にソート（NULLは最後）

**レスポンス**:
- view `dashboard`

---


#### 4-1-3. クライアント管理

##### S-0301 クライアント登録画面

###### GET /clients/create

**概要**: クライアント登録画面を表示する。

**リクエスト**:
なし

**レスポンス**:
- view `clients.create`


###### POST /clients

**概要**: クライアントを登録する。

**リクエスト**:
クライアント情報の全項目（詳細はDB設計書の clients テーブル定義を参照）。
以下に主要項目を記載。

| パラメータ | 型 | 必須 | バリデーション | 備考 |
|-----------|-----|------|---------------|------|
| internal_id | string | | — | サーバー側で採番（既存の最大値+1）。クライアントからは送信しない |
| initial_consultation_date | date | ● | required, date | |
| last_name | string | ● | nullable, string, max:50 | |
| *_kana | string | | nullable, string, max:50, regex:/^[\p{Hiragana}\s　]+$/u | |
| gender | string | | nullable, in:男,女,その他 | |
| birth_date | date | | nullable, date | |
| phone* | string | | nullable, string, max:20, regex:/^[0-9\-]+$/ | |
| email | string | | nullable, email, max:255 | |
| primary_trainer_id | integer | | nullable, exists:trainers,id | |
| support_status_id | integer | | nullable, exists:support_statuses,id | |

**レスポンス**:
- 成功：`redirect('/clients/{id}')`
- 失敗：`back()` ＋ バリデーションエラーメッセージ

---

##### S-0302 クライアント登録（URL発行）管理画面

###### GET /client-intake-tokens

**概要**: クライアント登録（URL発行）管理画面を表示する。

**処理**:
- 発行済みトークン一覧を取得（並び順は created_at の降順）

**レスポンス**:
- view `client-intake-tokens.index`


###### POST /client-intake-tokens

**概要**: ワンタイムURLを発行する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| initial_consultation_date | date | ● | required, date | 初回日 |
| email | string | | nullable, email, max:255 | メールアドレス（記録用） |
| expires_in_days | integer | ● | required, integer, in:1,7,14,30 | 有効期間（日数） |
| memo | string | | nullable, string, max:500 | メモ |

**処理**:
- ランダムなトークンを生成
- 有効期限を「現在日時 + expires_in_days」の当日終わり（23:59:59）に設定

**レスポンス**:
- 成功：`redirect('/client-intake-tokens')`
- 失敗：`back()` ＋ バリデーションエラーメッセージ


###### DELETE /client-intake-tokens/{id}

**概要**: 発行済みURLを削除する。

**処理**:
- 使用済みトークンは削除不可
- 物理削除（論理削除ではない）

**レスポンス**:
- 成功：`redirect('/client-intake-tokens')`
- 使用済み：`redirect('/client-intake-tokens')` ＋「使用済みのURLは削除できません」

---

##### S-0303 クライアント登録（URL発行）画面

クライアント本人がメールで受け取ったURLからアクセスする、認証不要の公開画面。ログイン後にかかる共通ミドルウェア（4-0）は適用されない。

###### GET /client-intake/token/{token}

**概要**: トークンチェックしてクライアント登録（URL発行）画面を表示する。

**処理**:
- トークンの有効性を「存在する／期限内／未使用」の順にチェック
- いずれかを満たさない場合はエラー画面を表示（無効・期限切れ・使用済みでメッセージを出し分ける）

**レスポンス**:
- 有効：view `client-intake.index-public`（登録フォーム）
- 無効：view `client-intake.errors.invalid-token`


###### POST /client-intake/token/{token}

**概要**: クライアントを登録する。

**リクエスト**:
クライアント情報の項目（S-0301 と同じ。ただし主担当トレーナー・支援状態は受け付けず、登録後にトレーナー側で設定する）。

**処理**:
- トークンの有効性を再チェック（無効ならエラー画面）
- クライアントを登録（internal_idを採番。主担当トレーナー・支援状態はNULL）
- トークンを使用済み（is_used=true）にし、登録したクライアントを紐付け

**レスポンス**:
- 成功：view `client-intake.complete-public`（完了画面）
- トークン無効：view `client-intake.errors.invalid-token`
- 失敗：`back()` ＋ バリデーションエラーメッセージ

---

##### S-0304 クライアント一覧画面

###### GET /clients

**概要**: クライアント一覧画面を表示する。

**クエリパラメータ**（すべて任意）:

| パラメータ | 型 | 説明 |
|-----------|-----|------|
| internal_id | string | 内部ID（部分一致） |
| keyword | string | 氏名・かなの複合検索（クライアントの姓名およびそのかな、計4項目を部分一致でOR検索） |
| support_status_id | integer | 支援状態ID |
| primary_trainer_id | integer | 主担当トレーナーID |
| date_from | date | 最終記録日（開始日） |
| date_to | date | 最終記録日（終了日） |
| sort | string | ソートカラム（internal_id, last_name, last_name_kana, created_at）。未指定時は created_at。internal_idは数値順、last_name・last_name_kanaは画面表示上の氏名で五十音順 |
| direction | string | ソート方向（asc, desc）。asc以外はdesc |
| page | integer | ページ番号 |

**レスポンス**:
- view `clients.index`

---

##### S-0305 クライアント詳細画面

###### GET /clients/{id}

**概要**: クライアント詳細画面を表示する。

**レスポンス**:
- view `clients.show`（クライアント情報とトレーニング記録一覧。トレーニング記録は新しい順。表示内容には閲覧状態（is_viewable と password から導出）を含む）


###### POST /clients/{client}/release-view

**概要**: クライアントの閲覧を解放し、パスワード設定用の招待メールを送信する。

**処理**:
- クライアントにメールアドレスが登録されていることを検証する（未登録の場合は解放しない）
- 以下を1つのトランザクションで実行する：
  - クライアントの is_viewable を true にする
  - パスワード設定用トークンを発行する（ランダムなトークン、有効期限は現在日時から72時間後、対象クライアントに紐付け）
  - トークン付きのパスワード設定URLを含む招待メールを、クライアントのメールアドレス宛に送信する

**レスポンス**:
- 成功：`redirect('/clients/{id}')` ＋「閲覧を解放し、招待メールを送信しました」
- メールアドレス未登録：`back()` ＋「メールアドレスが未登録のため解放できません」

**閲覧状態の判定**:
- クライアント詳細画面（S-0305）に表示する閲覧状態は、専用のカラムを持たず is_viewable と password から導出する：
  - is_viewable が false：「未解放」
  - is_viewable が true かつ password が未設定：「解放中（パスワード未設定）」
  - is_viewable が true かつ password が設定済み：「解放中」


###### POST /clients/{client}/revoke-view

**概要**: クライアントの閲覧解放を取り消し、解放前の状態に戻す。

**処理**:
- 以下を1つのトランザクションで実行する：
  - クライアントの is_viewable を false にする
  - クライアントの password を NULL にする（既に設定済みのパスワードを破棄する）
  - 対象クライアントの未使用の招待トークン（`client_password_setup_tokens` のうち is_used=false のレコード）をすべて物理削除する
- メール送信は行わない（send のような外部副作用は取り消し時には対称に持たせない）
- 既存のログイン中セッションは強制的には無効化しない。次回以降の新規ログインは is_viewable=false により attempt が失敗するため弾かれる（既存セッションは SESSION_LIFETIME で自然失効する）

**レスポンス**:
- 成功：`redirect('/clients/{id}')` ＋「閲覧を取り消しました」

**取り消し後の閲覧状態**:
- 取り消し後は is_viewable=false かつ password=NULL となるため、上記「閲覧状態の判定」ロジックにより自然に「未解放」へ戻る。再解放が必要な場合は既存の release-view を叩けばよい（password が NULL のため特別扱いは不要）。


###### DELETE /clients/{id}

**概要**: クライアントを削除する。

**処理**:
- トレーニング記録が1件でも存在する場合は削除不可
- 物理削除（論理削除ではない）

**レスポンス**:
- 成功：`redirect('/clients')`
- トレーニング記録あり：`redirect('/clients/{id}')` ＋「このクライアントにはトレーニング記録が登録されているため削除できません。」
- 権限なし：403エラー（「管理者のみ削除できます。」）

---

##### S-0306 クライアント編集画面

###### GET /clients/{id}/edit

**概要**: クライアント編集画面を表示する。

**レスポンス**:
- view `clients.edit`


###### PUT /clients/{id}

**概要**: クライアント情報を更新する。

**リクエスト**:
POST /clients と同じ項目。ただし internal_id は登録時と異なり、ユーザーが手入力で変更可能:

| パラメータ | 型 | 必須 | バリデーション | 備考 |
|-----------|-----|------|---------------|------|
| internal_id | string | ● | required, numeric, unique:clients,internal_id,{自身を除外} | 登録時はサーバー採番だが、編集時は手入力で変更可能 |

**レスポンス**:
- 成功：`redirect('/clients/{id}')`
- 失敗：`back()` ＋ バリデーションエラーメッセージ

---


#### 4-1-4. トレーニング記録管理

##### S-0401 トレーニング記録登録画面

トレーニング記録は必ずクライアント詳細画面（S-0305）から登録する。

###### GET /training-records/create

**概要**: トレーニング記録登録画面を表示する。

**クエリパラメータ**:
| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| client_id | integer | ● | 登録対象クライアントID |
| audio_file_id | integer | | 録音実行画面（S-0502）からの遷移時に引き継ぐ音声記録ID |

**レスポンス**:
- view `training-records.create`
- client_id 未指定・不存在：クライアント一覧へリダイレクト


###### POST /training-records

**概要**: トレーニング記録を新規登録する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| client_id | integer | ● | required, exists:clients,id | クライアントID |
| training_date | date | ● | required, date | 日付（過去・未来日付とも入力可） |
| training_time | time | | nullable, date_format:H:i | 時刻 |
| training_type_id | integer | | nullable, exists:training_types,id | トレーニング内容マスタID |
| training_detail | string | | nullable, string, max:255 | トレーニング内容の詳細 |
| trainer1_id | integer | ● | required, exists:trainers,id | 担当1 |
| trainer2_id | integer | | nullable, exists:trainers,id, different:trainer1_id | 担当2（担当1と異なること） |
| record_content | text | | nullable, string | トレーニング記録 |
| impression | text | | nullable, string | 所感 |
| phase_id | integer | | nullable, exists:phases,id | フェーズID |
| media_record_ids | array | | nullable, array | この記録に紐づけるメディアのID配列。**配列の順序が表示順**となる。空配列・未送信は紐づけなし |
| media_record_ids.* | integer | | integer, distinct, exists:media_records,id | 各メディアID（重複不可、実在すること） |

**処理**:
- トレーニング記録を新規登録する
- メディア紐づけを作成する（記録の登録と同一トランザクション）：
  - media_record_ids の配列で中間テーブル（media_record_training_record）に紐づけを作成する
  - 配列の順序で sort_order を 0 始まりの連番（0, 1, 2, ...）として保存する
- メディア紐づけの作成とトレーニング記録の登録は、すべて1トランザクションで反映する（いずれかが失敗した場合はロールバックする）

**レスポンス**:
- 成功：`redirect('/training-records/{id}')`
- 失敗：`back()` ＋ バリデーションエラーメッセージ

---

##### S-0402 トレーニング記録一覧画面

###### GET /training-records

**概要**: トレーニング記録一覧画面を表示する。

**クエリパラメータ**（すべて任意）:

| パラメータ | 型 | 説明 |
|-----------|-----|------|
| internal_id | string | クライアントの内部ID（部分一致） |
| name | string | クライアントの氏名（姓名およびそのかな、計4項目を部分一致でOR検索） |
| date_from | date | 日付（開始日） |
| date_to | date | 日付（終了日） |
| trainer_id | integer | 担当トレーナーID（担当1・担当2のいずれかに一致） |
| keyword | string | 記録内容・所感・トレーニング内容詳細を対象としたキーワード検索（部分一致でOR検索） |
| sort | string | ソートカラム（training_date, internal_id, client_name, created_at）。未指定時は training_date。internal_idは数値順、client_nameは画面表示上の氏名で五十音順 |
| direction | string | ソート方向（asc, desc）。asc以外はdesc |

**レスポンス**:

- view `training-records.index`

---

##### S-0403 トレーニング記録詳細画面

###### GET /training-records/{id}

**概要**: トレーニング記録詳細画面を表示する。

**処理**:
- 対象トレーニング記録を取得する
- この記録に紐づくメディアを sort_order 順（昇順）で取得し、view に渡す（閲覧用。再生は `GET /api/media-records/{id}/play` を利用する）

**レスポンス**:
- view `training-records.show`


###### DELETE /training-records/{id}

**概要**: トレーニング記録を削除する。

**処理**:
- 物理削除（論理削除ではない）

**レスポンス**:
- 成功：そのトレーニング記録が紐づくクライアントの詳細画面へリダイレクト（`redirect('/clients/{client_id}')`）
- 権限なし：403エラー（「管理者のみ削除できます。」）

---

##### S-0404 トレーニング記録編集画面

###### GET /training-records/{id}/edit

**概要**: トレーニング記録編集画面を表示する。

**処理**:
- 対象トレーニング記録を取得する
- この記録に紐づくメディアを sort_order 順（昇順）で取得し、view に渡す（メディアセクションの初期表示用）

**レスポンス**:
- view `training-records.edit`


###### PUT /training-records/{id}

**概要**: トレーニング記録を更新する。

**リクエスト**:
POST /training-records に以下を追加する。

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| media_record_ids | array | | nullable, array | この記録に紐づけるメディアのID配列。**配列の順序が表示順**となる。空配列・未送信は紐づけなし |
| media_record_ids.* | integer | | integer, distinct, exists:media_records,id | 各メディアID（重複不可、実在すること） |

**処理**:
- トレーニング記録本体を更新する
- メディア紐づけを更新する（記録本体の更新と同一トランザクション）：
  - media_record_ids の配列で中間テーブル（media_record_training_record）を総入れ替えする。配列にないメディアは紐づけ解除、新たに含まれるメディアは紐づけ追加となる
  - 配列の順序で sort_order を 0 始まりの連番（0, 1, 2, ...）として振り直す
  - 紐づけの追加・解除・並べ替えは、いずれもトレーニング記録の更新として扱い、本記録の updated_at / updated_by を更新する
- メディア紐づけの追加・解除・並べ替えとトレーニング記録本体の更新は、すべて1トランザクションで反映する（いずれかが失敗した場合はロールバックする）

**レスポンス**:
- 成功：そのトレーニング記録が紐づくクライアントの詳細画面へリダイレクト（`redirect('/clients/{client_id}')`）
- 失敗：`back()` ＋ バリデーションエラーメッセージ

---


#### 4-1-5. 音声記録管理

##### S-0501 録音準備画面

###### GET /recording-v2

**概要**: 録音準備画面を表示する。

**レスポンス**:
- view `recording-v2.index`（クライアント選択はSelect2で内部API `/api/clients/search` を使用）


###### POST /recording-v2/start

**概要**: 録音実行画面に遷移する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| client_id | integer | ● | required, exists:clients,id | 録音対象クライアントID |

**処理**:
- client_idをセッションに保存し、録音実行画面（GET /recording-v2/session）へリダイレクト

**レスポンス**:
- 成功：`redirect` で GET /recording-v2/session（録音実行画面）へ
- 失敗：`back()` ＋ バリデーションエラーメッセージ

---

##### S-0502 録音実行画面

録音して音声記録を作成することがこの画面の主機能。さらに、作成した音声記録に文字起こし・要約を行い、その要約からトレーニング記録を作成することも可能。

処理フロー：
1. 録音した音声を POST /audio-records/recording でアップロードし、音声記録を作成
2. POST /api/audio-records/{id}/transcribe で文字起こし
3. POST /api/audio-records/{id}/summarize で要約
4. POST /api/training-records/auto-create で要約からトレーニング記録を作成

###### GET /recording-v2/session

**概要**: 録音実行画面を表示する。

**処理**:
- セッションから client_id を取得し、対象クライアントを取得する
- client_id がない、または該当クライアントが存在しない場合は録音準備画面（S-0501）へリダイレクト

**レスポンス**:
- view `recording-v2.session`


###### POST /audio-records/recording

**概要**: 録音した音声をアップロードして音声記録を作成する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| client_id | integer | ● | required, exists:clients,id | 紐付けるクライアントID |
| file | file | ● | required, file, max:512000 | 録音した音声 |

**処理**:
- タイトルを `YYYYMMDD_HHMM_{ログインユーザーのlogin_id}` で自動生成
- 音声ソース種別は recording、状態（status）は未処理（unprocessed）で作成

**レスポンス**（JSON）:
- 成功：`{ "data": <音声記録> }`（201）

---

##### S-0503 音声記録登録（文字起こしテキスト）画面

###### GET /audio-records/text-paste/create

**概要**: 音声記録登録（文字起こしテキスト）画面を表示する。

**処理**:
- タイトルのデフォルト値（`YYYYMMDD_HHMM`形式）をサーバー側で生成する

**レスポンス**:
- view `audio.text-paste-create`


###### POST /audio-records/text-paste

**概要**: 文字起こしテキストから音声記録を登録する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| client_id | integer | ● | required, exists:clients,id | 紐付けるクライアントID |
| title | string | ● | required, string, max:255 | タイトル |
| transcription_text | string | ● | required, string | 文字起こしテキスト |

**処理**:

- 入力された文字起こしテキストで音声記録を作成する
- 音声ソース種別は text_paste、状態（status）は文字起こし済み（transcribed）で作成

**レスポンス**:
- `redirect('/audio-records')`

---

##### S-0504 音声記録登録（音声ファイルのアップロード）画面

###### GET /audio-records/upload/create

**概要**: 音声記録登録（音声ファイルのアップロード）画面を表示する。

**レスポンス**:
- view `audio.upload-create`


###### POST /audio-records/upload

**概要**: 音声ファイルをアップロードして音声記録を登録する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| client_id | integer | ● | required, exists:clients,id | 紐付けるクライアントID |
| file | file | ● | required, file, max:512000 | 音声ファイル（最大500MB） |
許可拡張子：mp3, m4a, wav, mp4, webm

**処理**:
- タイトルをアップロードファイル名（拡張子を除く）で自動生成
- 音声ソース種別は upload、状態（status）は未処理（unprocessed）で作成

**レスポンス**:
- `redirect('/audio-records')`

---

##### S-0505 音声記録一覧画面

###### GET /audio-records

**概要**: 音声記録一覧画面を表示する。

**クエリパラメータ**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| trainer_id | string | | — | トレーナーで絞り込む。未指定はログイン中のトレーナー、`all` で全トレーナー、トレーナーIDの指定でそのトレーナーの音声記録 |

**レスポンス**:
- view `audio.index`


###### GET /audio-records/{id}

**概要**: 音声記録の詳細を取得する。

**レスポンス**（JSON）:
- `{ "data": { ... } }`

`data` の内容（音声記録一覧画面の編集部分で使用する項目）：

| フィールド | 型 | 説明 |
|-----------|-----|------|
| title | string | 表示用タイトル |
| transcription_text | string\|null | 文字起こし結果 |
| summary_text | string\|null | 要約結果 |
| can_delete | boolean | 音声記録を削除できるか |
| has_audio_file | boolean | 音声ファイルを保持しているか |
| delete_audio_url | string\|null | 音声ファイル削除APIのURL。音声ファイルがない場合はnull |


###### PUT /audio-records/{id}

**概要**: 音声記録のタイトル・文字起こしテキスト・要約テキストを編集する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| title | string | ● | required, string, max:255 | タイトル |
| transcription_text | string | | nullable, string | 文字起こしテキスト |
| summary_text | string | | nullable, string | 要約テキスト |

**レスポンス**:
- `redirect('/audio-records')`


###### DELETE /audio-records/{id}

**概要**: 音声記録を削除する（物理削除。サーバー上の音声ファイルとレコードの両方を削除する）。

**処理**:
- 処理中（文字起こし中・要約中）の場合は削除せず、音声記録一覧へリダイレクト

**レスポンス**:
- `redirect('/audio-records')`


###### DELETE /audio-records/{id}/delete-audio

**概要**: 音声記録の音声ファイルのみ削除する（文字起こし・要約は保持）。

**処理**:
- 処理中（文字起こし中・要約中）の場合は削除せず、音声記録一覧へリダイレクト

**レスポンス**:
- `redirect('/audio-records')`


###### GET /audio-records/{id}/play

**概要**: 音声ファイルを再生する（ストリーミング）。

**処理**:
- 音声ファイル（file_path）が存在しない場合（テキスト貼り付け・音声削除済みなど）は 404 を返す

**レスポンス**:
- 音声ファイル（Content-Type は拡張子に応じて設定）

---


#### 4-1-6. 要約

##### S-0601 要約プロンプト画面

###### GET /settings/summary-prompts

**概要**: 要約プロンプト画面を表示する。

**レスポンス**:
- view `settings.summary-prompts`


###### PUT /settings/summary-prompts

**概要**: 要約プロンプトを更新する。

**リクエスト**:
| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| current_prompt | string | ● | required, string, max:2000 | 要約プロンプト |

**レスポンス**:
- `redirect('/settings/summary-prompts')`

---


#### 4-1-7. 音声ファイル容量管理

##### S-0701 音声ファイル一覧画面

###### GET /usage-stats

**概要**: 音声ファイル一覧画面を表示する（サーバー容量管理用）。

**処理**:
- 実ファイルが存在する音声記録（`file_path` があり `file_size > 0`）を対象に、総ファイル数・総容量を集計する
- 一覧を日時の降順で表示する

**レスポンス**:
- view `usage-stats.index`

---


#### 4-1-8. トレーナー管理

##### S-0801 トレーナー登録画面

###### GET /trainers/create

**概要**: トレーナー登録画面を表示する。

**レスポンス**:
- view `trainers.create`


###### POST /trainers

**概要**: トレーナーを登録する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| login_id | string | ● | required, string, max:50, regex:`/^[a-zA-Z0-9_]+$/`, unique:trainers | ログインID（半角英数字とアンダースコア） |
| name | string | ● | required, string, max:100 | 氏名 |
| password | string | ● | required, string, confirmed, StrongPassword | パスワード |
| password_confirmation | string | ● | — | パスワード（確認） |
| role | string | ● | required, in:admin,staff | 権限 |

**処理**:
- 初回ログイン時にパスワード変更を必須とする状態で作成する
- 表示順は末尾に自動で付番する

**レスポンス**:
- `redirect('/trainers')`

---

##### S-0802 トレーナー管理画面

###### GET /trainers

**概要**: トレーナー管理画面を表示する。

**処理**:
- システム管理者を除くトレーナーの一覧を表示する（各トレーナーの主担当クライアント数、トレーニング記録の担当件数を含む）

**レスポンス**:
- view `trainers.index`


###### PATCH /trainers/{id}/move-up

**概要**: トレーナーの表示順を上に移動する。

**処理**:
- 一覧の表示順で、ひとつ上のトレーナーと表示順を入れ替える

**レスポンス**:
- `redirect('/trainers')`


###### PATCH /trainers/{id}/move-down

**概要**: トレーナーの表示順を下に移動する。

**処理**:
- 一覧の表示順で、ひとつ下のトレーナーと表示順を入れ替える

**レスポンス**:
- `redirect('/trainers')`


###### PATCH /trainers/{id}/unlock

**概要**: アカウントロックを解除する。

**処理**:
- アカウントのロックを解除し、ログイン失敗の記録をリセットする

**レスポンス**:
- `redirect('/trainers')`


###### PATCH /trainers/{id}/toggle-active

**概要**: アカウントの有効/無効を切り替える。

**処理**:
- アカウントの有効・無効を切り替える
- 次の場合は無効化できない：システム管理者、自分自身、有効な管理者が自分ひとりだけの管理者
- 無効化されたトレーナーはログインできなくなる。主担当の割り当ては残るが、主担当の付け替え候補には表示されない

**レスポンス**:
- `redirect('/trainers')`


###### DELETE /trainers/{id}

**概要**: トレーナーを削除する。

**処理**:
- トレーナーを削除する（物理削除）
- 次の場合は削除できない：システム管理者、自分自身、最後の管理者、主担当クライアントがいる、トレーニング記録の担当者になっている

**レスポンス**:
- `redirect('/trainers')`

---

##### S-0803 トレーナー編集画面

###### GET /trainers/{id}/edit

**概要**: トレーナー編集画面を表示する。

**レスポンス**:
- view `trainers.edit`


###### PUT /trainers/{id}

**概要**: トレーナーを更新する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| name | string | ● | required, string, max:100 | 氏名 |
| role | string | ● | required, in:admin,staff | 権限 |

**処理**:
- 最後の管理者を一般に変更することはできない

**レスポンス**:
- `redirect('/trainers')`

---

##### S-0804 パスワードリセット画面

###### GET /trainers/{id}/reset-password

**概要**: パスワードリセット画面を表示する。

**レスポンス**:
- view `trainers.reset-password`


###### PUT /trainers/{id}/reset-password

**概要**: パスワードをリセットする。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| password | string | ● | required, string, confirmed, StrongPassword | 新しいパスワード |
| password_confirmation | string | ● | — | パスワード（確認） |

**処理**:
- パスワードを更新し、次回ログイン時にパスワード変更を必須とする
- システム管理者のパスワードはリセットできない

**レスポンス**:
- `redirect('/trainers')`

---

##### S-0805 トレーナー操作履歴画面

###### GET /access-logs

**概要**: トレーナー操作履歴画面を表示する。

**クエリパラメータ**（すべて任意）:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| trainer_id | integer | | — | トレーナーで絞り込む |
| action | string | | — | 操作種別で絞り込む |
| date_from | date | | — | 期間の開始日（その日を含む） |
| date_to | date | | — | 期間の終了日（その日を含む） |

**処理**:
- 操作履歴を日時の降順で表示する

**レスポンス**:
- view `access-logs.index`

---


#### 4-1-9. マスタ管理

##### S-0901 支援状態マスタ画面

###### GET /master/support-statuses

**概要**: 支援状態マスタ管理画面を表示する。

**処理**:
- 支援状態の一覧を表示順で表示する（各支援状態を参照しているクライアント数を含む）

**レスポンス**:
- view `master.support-statuses.index`


###### POST /master/support-statuses

**概要**: 支援状態の選択肢を追加する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| name | string | ● | required, string, max:50, unique:support_statuses | 支援状態名 |

**処理**:
- 表示順は末尾に自動で付番する

**レスポンス**:
- `redirect('/master/support-statuses')`


###### PUT /master/support-statuses/{id}

**概要**: 支援状態の選択肢を更新する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| name | string | ● | required, string, max:50, unique:support_statuses（自身を除く） | 支援状態名 |
| show_in_dashboard | boolean | | — | ダッシュボードに表示するか |

**レスポンス**:
- `redirect('/master/support-statuses')`


###### DELETE /master/support-statuses/{id}

**概要**: 支援状態の選択肢を削除する。

**処理**:
- この支援状態を参照しているクライアントがいる場合は削除できない（物理削除）

**レスポンス**:
- `redirect('/master/support-statuses')`


###### PATCH /master/support-statuses/{id}/move-up

**概要**: 支援状態の表示順を上に移動する。

**処理**:
- 表示順で、ひとつ上の支援状態と表示順を入れ替える

**レスポンス**:
- `redirect('/master/support-statuses')`


###### PATCH /master/support-statuses/{id}/move-down

**概要**: 支援状態の表示順を下に移動する。

**処理**:
- 表示順で、ひとつ下の支援状態と表示順を入れ替える

**レスポンス**:
- `redirect('/master/support-statuses')`

---

##### S-0902 トレーニング内容マスタ画面

###### GET /master/training-types

**概要**: トレーニング内容マスタ管理画面を表示する。

**処理**:
- トレーニング内容の一覧を表示順で表示する（各トレーニング内容を参照しているトレーニング記録数を含む）

**レスポンス**:
- view `master.training-types.index`


###### POST /master/training-types

**概要**: トレーニング内容の選択肢を追加する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| name | string | ● | required, string, max:50, unique:training_types | トレーニング内容名 |

**処理**:
- 表示順は末尾に自動で付番する

**レスポンス**:
- `redirect('/master/training-types')`


###### PUT /master/training-types/{id}

**概要**: トレーニング内容の選択肢を更新する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| name | string | ● | required, string, max:50, unique:training_types（自身を除く） | トレーニング内容名 |

**レスポンス**:
- `redirect('/master/training-types')`


###### DELETE /master/training-types/{id}

**概要**: トレーニング内容の選択肢を削除する。

**処理**:
- このトレーニング内容を参照しているトレーニング記録がある場合は削除できない（物理削除）

**レスポンス**:
- `redirect('/master/training-types')`


###### PATCH /master/training-types/{id}/move-up

**概要**: トレーニング内容の表示順を上に移動する。

**処理**:
- 表示順で、ひとつ上のトレーニング内容と表示順を入れ替える

**レスポンス**:
- `redirect('/master/training-types')`


###### PATCH /master/training-types/{id}/move-down

**概要**: トレーニング内容の表示順を下に移動する。

**処理**:
- 表示順で、ひとつ下のトレーニング内容と表示順を入れ替える

**レスポンス**:
- `redirect('/master/training-types')`

---

##### S-0903 フェーズマスタ画面

###### GET /master/phases

**概要**: フェーズマスタ管理画面を表示する。

**処理**:
- フェーズの一覧を表示順で表示する（各フェーズを参照しているトレーニング記録数を含む）

**レスポンス**:
- view `master.phases.index`


###### POST /master/phases

**概要**: フェーズの選択肢を追加する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| name | string | ● | required, string, max:100, unique:phases | フェーズ名 |

**処理**:
- 表示順は末尾に自動で付番する

**レスポンス**:
- `redirect('/master/phases')`


###### PUT /master/phases/{id}

**概要**: フェーズの選択肢を更新する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| name | string | ● | required, string, max:100, unique:phases（自身を除く） | フェーズ名 |

**レスポンス**:
- `redirect('/master/phases')`


###### DELETE /master/phases/{id}

**概要**: フェーズの選択肢を削除する。

**処理**:
- このフェーズを参照しているトレーニング記録がある場合は削除できない（物理削除）

**レスポンス**:
- `redirect('/master/phases')`


###### PATCH /master/phases/{id}/move-up

**概要**: フェーズの表示順を上に移動する。

**処理**:
- 表示順で、ひとつ上のフェーズと表示順を入れ替える

**レスポンス**:
- `redirect('/master/phases')`


###### PATCH /master/phases/{id}/move-down

**概要**: フェーズの表示順を下に移動する。

**処理**:
- 表示順で、ひとつ下のフェーズと表示順を入れ替える

**レスポンス**:
- `redirect('/master/phases')`

---

#### 4-1-10. セキュリティ設定

##### S-1002 IPアドレス制限画面

###### GET /settings/ip-restriction

**概要**: IPアドレス制限画面を表示する。

**処理**:
- 現在のIP制限の有効・無効の状態、許可IPアドレスの一覧、接続元のIPアドレスを表示する

**レスポンス**:
- view `settings.ip-restriction`

###### PUT /settings/ip-restriction

**概要**: IPアドレス制限設定を更新する。

**リクエスト**:
| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| enable_ip_restriction | boolean | | — | IPアドレス制限の有効・無効 |
| ip_addresses[] | array | | 各要素：IPv4またはCIDR形式、重複不可 | 許可するIPアドレス |
| descriptions[] | array | | 各要素：100文字以内 | 各IPアドレスの備考（拠点名など） |

**処理**:
- IP制限の有効・無効を保存する
- 許可IPアドレスの一覧を入れ替える（空の行はスキップ。形式・重複・備考の文字数に誤りがある場合は保存せず、エラーを表示）
- 制限が無効でも、許可IPアドレスの一覧は保持される

**レスポンス**:
- `redirect('/settings/ip-restriction')`

---


#### 4-1-11. レポート

##### S-1101 トレーニング記録数推移画面

###### GET /statistics/clients

**概要**: トレーニング記録数推移画面を表示する。

**クエリパラメータ**（すべて任意）:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| trainer_id | string | | — | トレーナーで絞り込む（`all` または トレーナーID）。一般トレーナー（staff）の場合は無視され、自分が担当するトレーニング記録のみが集計される |
| view_type | string | | — | 表示タイプ（`fiscal_year`：年度別／`calendar_year`：年別）。既定は年度別 |
| period | integer | | — | 表示する期間（年度別なら年度、年別なら年） |

**処理**:
- トレーニング記録数の年度別または年別の推移、月別の推移、性別・年代別の内訳を集計する

**レスポンス**:
- view `statistics.clients`

---


#### 4-1-12. マイプロフィール

##### S-1201 マイプロフィール画面

###### GET /profile

**概要**: マイプロフィール画面を表示する。

**レスポンス**:
- view `profile.edit`

###### PUT /profile

**概要**: プロフィールを更新する。

**リクエスト**:
| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| name | string | ● | required, string, max:100 | 氏名 |

**レスポンス**:
- `redirect('/profile')`

---

##### S-1202 パスワード変更画面

###### GET /profile/password

**概要**: パスワード変更画面を表示する。

**レスポンス**:
- view `profile.password`


###### PUT /profile/password

**概要**: パスワードを変更する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| current_password | string | ● | required, string | 現在のパスワード |
| new_password | string | ● | required, string, confirmed, StrongPassword | 新しいパスワード |
| new_password_confirmation | string | ● | — | 新しいパスワード（確認） |

**処理**:
- 現在のパスワードが一致しない場合は、エラーを表示してパスワード変更画面に戻る

**レスポンス**:
- `redirect('/profile')`

---


#### 4-1-13. メディア管理

##### S-1302 メディア一覧画面

メディアの一覧表示・詳細表示・編集・削除を行う。アップロードはメディア登録モーダル（S-1302-M02）から内部API（署名付きURL発行→レコード作成）で行う。

###### GET /media-records

**概要**: メディア一覧画面を表示する。

**リクエスト**（クエリパラメータ）:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| trainer_id | integer | | nullable, exists:trainers,id | 登録者で絞り込む。未指定時はログイン中のトレーナー、「全員」選択時は全件 |
| page | integer | | nullable, integer, min:1 | ページ番号 |

**処理**:
- メディアをサムネイルのグリッドで一覧表示する
- 登録者で絞り込む（デフォルトはログイン中のトレーナー）
- 登録日時の新しい順に並べ、最大表示件数を超える場合はページネーションする

**レスポンス**:
- view `media-records.index`

###### GET /media-records/{id}

**概要**: メディアの詳細を取得する（詳細モーダル S-1302-M01 用）。

**処理**:
- 対象メディア1件を取得して返す

**レスポンス**（JSON）:
- 成功：`{ "data": <メディア> }`
- 該当なし：エラーを返す（HTTP 404）

###### PUT /media-records/{id}

**概要**: メディアの表示名を更新する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| title | string | | nullable, string, max:255 | 表示名。未入力時は元ファイル名を表示 |

**処理**:
- 表示名を更新する

**レスポンス**（JSON）:
- 成功：`{ "data": <メディア> }`

###### DELETE /media-records/{id}

**概要**: メディアを削除する。

**処理**:
- メディアレコードを削除し、あわせてオブジェクトストレージ上のファイル実体（本体・サムネイル）を削除する

**レスポンス**（JSON）:
- 成功：`{ "success": true }`

---


#### 4-1-14. 共通

##### ログアウト

###### POST /logout

**概要**: ログアウトする。

**処理**:
- ログアウトを操作履歴に記録する

**レスポンス**:
- `redirect('/login')`

---


#### 4-1-15. クライアント閲覧

##### S-1401 クライアントログイン画面

###### GET /client-portal/login

**概要**: クライアントログイン画面を表示する。

**レスポンス**:
- 未ログインの場合：view `client.login`
- ログイン済みの場合：クライアントダッシュボード画面へリダイレクト


###### POST /client-portal/login

**概要**: クライアントとしてログインする。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| email | string | ● | required, email | メールアドレス |
| password | string | ● | required, string | パスワード |

**処理**:
- トレーナーによって閲覧が解放されている（is_viewable が true）クライアントのみログインできる
- トレーナーのログイン（web guard）とは独立した client guard で認証する

**レスポンス**:
- 成功：クライアントダッシュボード画面へリダイレクト
- 認証失敗、または閲覧が解放されていない場合：エラーを表示（「メールアドレスまたはパスワードが正しくありません。」）

---

##### S-1402 クライアントダッシュボード画面

###### GET /client-portal/dashboard

**概要**: クライアントダッシュボード画面を表示する。

**処理**:
- ログイン中のクライアント自身のトレーニング記録の一覧を、日付の新しい順（降順）で取得し、view に渡す

**レスポンス**:
- view `client.dashboard`

---

##### S-1403 クライアントパスワード設定画面

クライアントが招待メールで受け取ったURLからアクセスする、認証不要の公開画面。ログイン後にかかるクライアント用ミドルウェア（auth:client）は適用されない。

###### GET /client-portal/password-setup/{token}

**概要**: トークンを検証し、パスワード設定画面を表示する。

**処理**:
- トークンの有効性を「存在する／期限内／未使用」の順にチェック
- いずれかを満たさない場合はエラー画面を表示（無効・期限切れ・使用済みでメッセージを出し分ける）

**レスポンス**:
- 有効：view（パスワード設定フォーム。対象クライアントのメールアドレスを表示）
- 無効：view（トークンエラー画面）


###### POST /client-portal/password-setup/{token}

**概要**: パスワードを設定する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| password | string | ● | required, string, confirmed, StrongPassword | 設定するパスワード |
| password_confirmation | string | ● | required | パスワード（確認） |

**処理**:
- トークンの有効性を再チェック（無効ならエラー画面）
- 以下を1つのトランザクションで実行する：
  - 対象クライアントのパスワードを設定する
  - トークンを使用済み（is_used=true）にする

**レスポンス**:
- 成功：クライアントログイン画面へリダイレクト（パスワード設定完了を伝える）
- トークン無効：トークンエラー画面
- 失敗：`back()` ＋ バリデーションエラーメッセージ

---

##### S-1404 クライアントトレーニング記録詳細画面

###### GET /client-portal/training-records/{id}

**概要**: クライアントが自分のトレーニング記録の詳細を表示する。

**処理**:
- 対象トレーニング記録が、ログイン中のクライアント自身のもの（記録の client_id がログイン中クライアントと一致）であることを確認する。本人のものでない場合は403
- 記録の詳細（基本情報・トレーニング内容・トレーニング記録）を view に渡す。所感などクライアント非開示の情報は渡さない
- この記録に紐づくメディアを sort_order 順（昇順）で取得し、各メディアのサムネイルの署名付きURLを発行して view に渡す（閲覧用。再生は `GET /client-portal/media/{id}/play` を利用する）。本人の記録に紐づくメディアであることは記録レベルの本人認可により保証されるため、サムネイル発行に個別の認可は要しない

**レスポンス**:
- view `client.training-records.show`
- 本人の記録でない場合：403

---

##### GET /client-portal/media/{id}/play

**概要**: クライアントが、自分のトレーニング記録に紐づくメディアを表示・再生する。メディアIDを直接受け取るため、記録レベルの認可では守れない。このエンドポイント自身でメディア単位の本人認可を行う。

**処理**:
- 対象メディアが、ログイン中のクライアント自身のトレーニング記録に紐づくこと（メディアが紐づくいずれかの記録の client_id がログイン中クライアントと一致すること）を確認する。紐づかない場合は403
- 認可を通ったら、対象メディアの表示用ファイル（display_path）に対する署名付きGET URL（presigned URL）を発行して返す
- display_path が NULL の場合（変換前・変換中・変換失敗）はエラーを返す

**レスポンス**（JSON）:
- 成功：`{ "data": { "url": <署名付きURL>, "expires_at": <有効期限> } }`
- 認可なし（本人の記録に紐づかない）：403
- display_path が NULL（表示用未生成）：エラーを返す（HTTP 409 等）
- 該当なし：エラーを返す（HTTP 404）

---

##### ログアウト

###### POST /client-portal/logout

**概要**: クライアントとしてログアウトする。

**レスポンス**:
- クライアントログイン画面へリダイレクト

---


### 4-2. 内部API（routes/api.php）

画面のJavaScriptがAjaxで呼び出すエンドポイント。`routes/api.php` に定義し、`/api` プレフィックスが付く。
レスポンスはJSON（データ）。
画面と同じセッション認証を共有するため `web` ミドルウェアグループを適用し、認証を必須とする。


#### 4-2-1. クライアント

##### GET /api/clients/search

**概要**: クライアントを検索する内部API。トレーニング記録の作成・編集フォームなどで、クライアントを検索して選択（Select2）するために使用する。

**リクエスト**（クエリパラメータ）:
| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| q | string | | — | 検索キーワード。管理番号、クライアントの氏名・かなで部分一致検索する |
| id | integer | | — | クライアントID。指定したクライアント1件を返す（入力エラー後に選択状態を復元するために使用する） |

**処理**:
- `q` での検索結果を、管理番号順に最大20件返す
- `id` が指定された場合は、そのクライアント1件を返す（`q` は無視される）

**レスポンス**（JSON）:
- `{ "results": [ ... ] }`

| フィールド | 型 | 説明 |
|-----------|-----|------|
| id | integer | クライアントID |
| text | string | 管理番号と氏名 |

---


#### 4-2-2. トレーニング記録

##### POST /api/training-records/auto-create

**概要**: 音声記録の要約からトレーニング記録を作成する内部API。

**リクエスト**（JSON）:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| audio_record_id | integer | ● | required, exists:audio_records,id | 元の音声記録ID |
| client_id | integer | ● | required, exists:clients,id | クライアントID |
| training_date | date | ● | required, date | 日付 |
| training_time | time | | nullable, date_format:H:i | 時刻 |
| trainer1_id | integer | ● | required, exists:trainers,id | 担当1 |
| trainer2_id | integer | | nullable, exists:trainers,id, different:trainer1_id | 担当2（担当1と異なること） |

**処理**:
- 音声記録の要約テキストをトレーニング記録の記録内容に取り込み、トレーニング記録を作成する

**レスポンス**（JSON）:
- 成功：`{ "success": true, "training_record_id": <作成したトレーニング記録のID> }`
- 失敗：`{ "success": false }`

##### GET /api/training-records/available-media

**概要**: トレーニング記録に紐づけ可能な（まだ紐づいていない）メディアの一覧を取得する内部API。編集画面（S-0404）のメディア追加モーダル（S-0404-M01）および登録画面（S-0401）のメディア追加モーダル（S-0401-M02）で使用する。

**リクエスト**（クエリパラメータ）:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| training_record_id | integer | | nullable, exists:training_records,id | 指定時、その記録に紐づけ済みのメディアを候補から除外する。未指定（登録画面）時は除外しない |
| trainer_id | integer | | nullable, exists:trainers,id | 登録者で絞り込む。未指定時はログイン中のトレーナー、「全員」選択時は全件 |
| page | integer | | nullable, integer, min:1 | ページ番号 |

**処理**:
- training_record_id が指定された場合、その記録に**まだ紐づいていない**メディアのみを候補とする（紐づけ済みは除外する）。未指定（登録画面）の場合は除外しない
- 登録者で絞り込む（デフォルトはログイン中のトレーナー）
- 登録日時の新しい順に並べ、最大表示件数（24件）を超える場合はページネーションする

**レスポンス**（JSON）:
- 成功：`{ "data": [ <メディア>, ... ], "meta": { <ページネーション情報> } }`
- training_record_id 指定時に該当記録が無い場合：エラーを返す（HTTP 404）

---


#### 4-2-3. 音声記録

##### POST /api/audio-records/{id}/transcribe

**概要**: 音声記録の文字起こしを実行する内部API。

**処理**:
- 音声ファイルを文字起こしする（同期実行）

**レスポンス**（JSON）:
- 成功：`{ "data": { ... } }`
- 処理できない状態の場合：エラーを返す（HTTP 409）
- APIキーが設定されていない場合：エラーを返す（HTTP 400）

成功時の `data`：

| フィールド | 型 | 説明 |
|-----------|-----|------|
| id | integer | 音声記録ID |
| status | string | 処理状態 |
| message | string | 完了メッセージ |


##### POST /api/audio-records/{id}/summarize

**概要**: 音声記録の要約を実行する内部API。

**処理**:
- 文字起こしテキストを要約する（同期実行）

**レスポンス**（JSON）:
- 成功：`{ "data": { ... } }`
- 要約できない状態の場合：エラーを返す（HTTP 409）
- APIキーが設定されていない場合：エラーを返す（HTTP 400）

成功時の `data`：

| フィールド | 型 | 説明 |
|-----------|-----|------|
| id | integer | 音声記録ID |
| status | string | 処理状態 |
| message | string | 完了メッセージ |


##### GET /api/audio-records/{id}/summary

**概要**: 指定した音声記録の要約テキストを返す内部API。録音実行画面からトレーニング記録の作成画面に遷移する際、対象の音声記録の要約をトレーニング記録の記録内容に取り込むために使用する。

**レスポンス**（JSON）:
- `{ "summary_text": ... }`

| フィールド | 型 | 説明 |
|-----------|-----|------|
| summary_text | string | 要約テキスト |


##### GET /api/audio-records/summaries

**概要**: トレーニング記録の作成・編集フォームで、音声記録の要約を取り込むために、候補となる音声記録の一覧を返す内部API。

**リクエスト**（クエリパラメータ）:
| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| client_id | integer | ● | required, exists:clients,id | クライアントID。指定したクライアントに紐づく音声記録のみを返す |
| search | string | | — | タイトルで部分一致検索する |

**処理**:
- ログイン中のトレーナーが所有し、指定したクライアントに紐づき、処理が完了して要約済みの音声記録を、作成日の降順で返す

**レスポンス**（JSON）:
- `{ "success": true, "data": [ ... ] }`
- `data` の各要素：

| フィールド | 型 | 説明 |
|-----------|-----|------|
| id | integer | 音声記録ID |
| client_id | integer | クライアントID |
| title | string | タイトル |
| source | string | 音声ソース種別 |
| file_name | string\|null | ファイル名（テキスト貼り付けの場合はnull） |
| created_at | datetime | 作成日時 |
| summary_text | string | 要約テキスト |

---


#### 4-2-4. トレーナー

##### GET /api/trainers

**概要**: 担当トレーナーの選択候補を返す内部API。録音実行画面で担当トレーナーを選択するために使用する。

**処理**:
- システム管理者を除くトレーナーを表示順に返す

**レスポンス**（JSON）:
- `[ ... ]`

| フィールド | 型 | 説明 |
|-----------|-----|------|
| id | integer | トレーナーID |
| name | string | 氏名 |

---


#### 4-2-5. メディア

##### POST /api/media-records/upload-url

**概要**: メディアアップロード用の署名付きURL（presigned PUT URL）を発行する内部API。メディア登録モーダル（S-1302-M02）で登録ボタン押下後、実ファイルのアップロードに先立って呼び出す。

**リクエスト**（JSON）:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| original_filename | string | ● | required, string, max:255 | アップロードするファイルの元ファイル名 |
| mime_type | string |  | nullable, string | ファイルのMIMEタイプ。クライアントから受け取るが採用しない（ブラウザの file.type は heic 等で空文字や image/heif になるばらつきがあり信頼できないため、サーバは original_filename の拡張子から決定する） |
| file_size | integer | ● | required, integer, min:1 | ファイルサイズ（バイト） |

**処理**:
- original_filename の拡張子から正規のMIMEタイプを決定する。許可形式（写真 jpeg/png/heic/heif、動画 mp4/mov）以外は拒否する
- 決定したMIMEタイプから種別（photo / video）を判定する
- file_size を種別ごとの上限（写真20MB、動画1GB）と照合し、超過は拒否する
- 保存キー（オブジェクトストレージ上のパス）を採番し、そのキーに対する署名付きPUT URLを発行する
- ※申請値（original_filename・file_size）に基づく事前検証であり、実ファイルの検証は別途行う（直アップロードのためサーバを経由しない）

**レスポンス**（JSON）:
- 成功：`{ "data": { ... } }`

成功時の `data`：

| フィールド | 型 | 説明 |
|-----------|-----|------|
| upload_url | string | 署名付きPUT URL（このURLへブラウザから直接アップロードする） |
| storage_key | string | 採番された保存キー。レコード作成時に渡す |

- 形式・サイズが不適合：エラーを返す（HTTP 422）

##### POST /api/media-records

**概要**: アップロード完了後、メディアレコードを作成する内部API。ブラウザが署名付きURLへのアップロードを完了した後に呼び出す。

**リクエスト**（JSON）:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| storage_key | string | ● | required, string | 署名付きURL発行時に採番された保存キー |
| original_filename | string | ● | required, string, max:255 | 元ファイル名 |
| mime_type | string |  | nullable, string | MIMEタイプ。クライアントから受け取るが採用しない（upload-url と同じ理由。サーバは original_filename の拡張子から決定する） |
| file_size | integer | ● | required, integer, min:1 | ファイルサイズ（バイト） |
| title | string | | nullable, string, max:255 | 表示名。未入力時は元ファイル名を表示 |

**処理**:
- storage_key の指すファイルを original_path として、media_records レコードを作成する
- original_filename の拡張子から正規のMIMEタイプを決定し、許可形式以外は拒否する
- 形式に応じて conversion_status と display_path を設定する：
  - 変換不要な形式（jpeg / png / mp4）：conversion_status を not_required とし、display_path に original_path と同じ値をセットする（原本がそのまま表示用）
  - 変換が必要な形式（heic / mov）：conversion_status を pending とし、display_path は NULL のままとする（変換は別途 convert API で起動する）
- 決定したMIMEタイプから種別（photo / video）を確定する
- 決定したMIMEタイプを mime_type カラムに保存する
- 登録者は、ログイン中のトレーナーを設定する
  
**レスポンス**（JSON）:
- 成功：`{ "data": <メディア> }`（201）

##### GET /api/media-records/{id}/play

**概要**: メディアを表示・再生する内部API（ストリーミング）。メディア一覧の詳細モーダルのほか、将来的にトレーニング記録への紐付け・共有ビューなど複数の画面から横断的に利用するため、画面に依存しない内部APIとして配置する。

**処理**:
- 対象メディアの表示用ファイル（display_path）に対する署名付きGET URL（presigned URL）を発行して返す
- display_path が NULL の場合（変換前・変換中・変換失敗）は、表示用ファイルが未生成のため表示・再生できない。この場合はエラー（または未生成を示すレスポンス）を返し、呼び出し側は conversion_status に応じて「変換中」「変換失敗」を表示する

**レスポンス**（JSON）:
- 成功：`{ "data": { "url": <署名付きURL>, "expires_at": <有効期限> } }`
- display_path が NULL（表示用未生成）：エラーを返す（HTTP 409 等）
- 該当なし：エラーを返す（HTTP 404）

##### POST /api/media-records/{id}/convert

**概要**: メディアの表示用変換（heic→jpeg / mov→mp4）を起動する内部API。メディア登録モーダル（S-1302-M02）で、レコード作成（store）後に conversion_status が pending（変換が必要）の場合に呼び出す。変換不要な形式では呼び出さない。

**処理**:
- 対象メディアの conversion_status が pending であることを確認する（既に処理中・完了・変換不要の場合は何もしないか、対象外として扱う）
- 変換ジョブ（ConvertMediaJob）を起動する。ジョブは非同期（ShouldQueue）で、開発環境では同期実行、本番環境ではキューワーカーで非同期実行する
- ジョブ内で、原本（original_path）をストレージから取得 → 変換（写真は ImageMagick で heic→jpeg、動画は FFmpeg で mov→mp4）→ 変換後ファイルをストレージに保存 → display_path にパスをセット → conversion_status を done に更新する
- 変換中は conversion_status を processing、失敗時は error とする

**レスポンス**（JSON）:
- 成功（変換起動）：`{ "data": <メディア> }`（同期実行の場合は変換完了後の状態、非同期の場合は processing 状態）
- 該当なし：エラーを返す（HTTP 404）