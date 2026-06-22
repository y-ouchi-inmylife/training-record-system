**位置づけ**: 仕様文書（API設計書）
**対象読者**: 開発者
**上位文書**: requirements.md（機能一覧、6章）
**詳細**: 詳細は doc-index.md を参照

---

# API設計書: トレーニング記録管理システム

## 1. 認証方式

- Laravelの標準セッション認証（Cookie + CSRFトークン）を使用する。
- JWT（トークンベース認証）は使用しない。ブラウザ経由の利用のみのため、セッション認証で十分。

---

## 2. 共通ミドルウェア

認証後の全エンドポイントに横断的に適用される挙動を記述する。これらは routes/web.php のルート全体に適用され、各エンドポイントの詳細では繰り返さない。リクエストは、エンドポイントの処理に到達する前に、以下のミドルウェアを順に通過する。

### 2-1. CheckIpRestriction（IPアドレス制限）

IPアドレス制限が有効で、許可リストが登録されている場合、許可されたIPアドレス以外からのアクセスを遮断する（403）。許可リストは完全一致またはCIDR範囲で判定する。IPアドレス制限が無効、または許可リストが空の場合は、すべて許可する。

ただし、次のアクセスは制限の対象外とする。
- ログイン・ログアウト
- システム管理者
- ローカルホストからのアクセス

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
| クライアント管理 | S-0305 クライアント詳細画面 | DELETE | `/clients/{id}` | クライアントを削除する | auth | 管理者、一般 |
| クライアント管理 | S-0306 クライアント編集画面 | GET | `/clients/{id}/edit` | クライアント編集画面を表示する | auth | 管理者、一般 |
| クライアント管理 | S-0306 クライアント編集画面 | PUT | `/clients/{id}` | クライアント情報を更新する | auth | 管理者、一般 |
| トレーニング記録管理 | S-0401 トレーニング記録登録画面 | GET | `/counseling-records/create` | トレーニング記録登録画面を表示する | auth | 管理者、一般 |
| トレーニング記録管理 | S-0401 トレーニング記録登録画面 | POST | `/counseling-records` | トレーニング記録を新規登録する | auth | 管理者、一般 |
| トレーニング記録管理 | S-0402 トレーニング記録一覧画面 | GET | `/counseling-records` | トレーニング記録一覧画面を表示する | auth | 管理者、一般 |
| トレーニング記録管理 | S-0403 トレーニング記録詳細画面 | GET | `/counseling-records/{id}` | トレーニング記録詳細画面を表示する | auth | 管理者、一般 |
| トレーニング記録管理 | S-0403 トレーニング記録詳細画面 | DELETE | `/counseling-records/{id}` | トレーニング記録を削除する | auth | 管理者、一般 |
| トレーニング記録管理 | S-0404 トレーニング記録編集画面 | GET | `/counseling-records/{id}/edit` | トレーニング記録編集画面を表示する | auth | 管理者、一般 |
| トレーニング記録管理 | S-0404 トレーニング記録編集画面 | PUT | `/counseling-records/{id}` | トレーニング記録を更新する | auth | 管理者、一般 |
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
| トレーナー管理 | S-0801 トレーナー登録画面 | GET | `/counselors/create` | トレーナー登録画面を表示する | auth | 管理者 |
| トレーナー管理 | S-0801 トレーナー登録画面 | POST | `/counselors` | トレーナーを登録する | auth | 管理者 |
| トレーナー管理 | S-0802 トレーナー管理画面 | GET | `/counselors` | トレーナー管理画面を表示する | auth | 管理者 |
| トレーナー管理 | S-0802 トレーナー管理画面 | PATCH | `/counselors/{id}/move-up` | トレーナーの表示順を上に移動する | auth | 管理者 |
| トレーナー管理 | S-0802 トレーナー管理画面 | PATCH | `/counselors/{id}/move-down` | トレーナーの表示順を下に移動する | auth | 管理者 |
| トレーナー管理 | S-0802 トレーナー管理画面 | PATCH | `/counselors/{id}/unlock` | アカウントロックを解除する | auth | 管理者 |
| トレーナー管理 | S-0802 トレーナー管理画面 | PATCH | `/counselors/{id}/toggle-active` | アカウントの有効/無効を切り替える | auth | 管理者 |
| トレーナー管理 | S-0802 トレーナー管理画面 | DELETE | `/counselors/{id}` | トレーナーを削除する | auth | 管理者 |
| トレーナー管理 | S-0803 トレーナー編集画面 | GET | `/counselors/{id}/edit` | トレーナー編集画面を表示する | auth | 管理者 |
| トレーナー管理 | S-0803 トレーナー編集画面 | PUT | `/counselors/{id}` | トレーナーを更新する | auth | 管理者 |
| トレーナー管理 | S-0804 パスワードリセット画面 | GET | `/counselors/{id}/reset-password` | パスワードリセット画面を表示する | auth | 管理者 |
| トレーナー管理 | S-0804 パスワードリセット画面 | PUT | `/counselors/{id}/reset-password` | パスワードをリセットする | auth | 管理者 |
| トレーナー管理 | S-0805 トレーナー操作履歴画面 | GET | `/access-logs` | トレーナー操作履歴画面を表示する | auth | 管理者 |
| マスタ管理 | S-0901 支援状態マスタ画面 | GET | `/master/support-statuses` | 支援状態マスタ管理画面を表示する | auth | 管理者 |
| マスタ管理 | S-0901 支援状態マスタ画面 | POST | `/master/support-statuses` | 支援状態の選択肢を追加する | auth | 管理者 |
| マスタ管理 | S-0901 支援状態マスタ画面 | PATCH | `/master/support-statuses/{id}/move-up` | 支援状態の表示順を上に移動する | auth | 管理者 |
| マスタ管理 | S-0901 支援状態マスタ画面 | PATCH | `/master/support-statuses/{id}/move-down` | 支援状態の表示順を下に移動する | auth | 管理者 |
| マスタ管理 | S-0901 支援状態マスタ画面 | PUT | `/master/support-statuses/{id}` | 支援状態の選択肢を更新する | auth | 管理者 |
| マスタ管理 | S-0901 支援状態マスタ画面 | DELETE | `/master/support-statuses/{id}` | 支援状態の選択肢を削除する | auth | 管理者 |
| マスタ管理 | S-0902 トレーニング内容マスタ画面 | GET | `/master/consultation-types` | トレーニング内容マスタ管理画面を表示する | auth | 管理者 |
| マスタ管理 | S-0902 トレーニング内容マスタ画面 | POST | `/master/consultation-types` | トレーニング内容の選択肢を追加する | auth | 管理者 |
| マスタ管理 | S-0902 トレーニング内容マスタ画面 | PATCH | `/master/consultation-types/{id}/move-up` | トレーニング内容の表示順を上に移動する | auth | 管理者 |
| マスタ管理 | S-0902 トレーニング内容マスタ画面 | PATCH | `/master/consultation-types/{id}/move-down` | トレーニング内容の表示順を下に移動する | auth | 管理者 |
| マスタ管理 | S-0902 トレーニング内容マスタ画面 | PUT | `/master/consultation-types/{id}` | トレーニング内容の選択肢を更新する | auth | 管理者 |
| マスタ管理 | S-0902 トレーニング内容マスタ画面 | DELETE | `/master/consultation-types/{id}` | トレーニング内容の選択肢を削除する | auth | 管理者 |
| マスタ管理 | S-0903 フェーズマスタ画面 | GET | `/master/phases` | フェーズマスタ管理画面を表示する | auth | 管理者 |
| マスタ管理 | S-0903 フェーズマスタ画面 | POST | `/master/phases` | フェーズの選択肢を追加する | auth | 管理者 |
| マスタ管理 | S-0903 フェーズマスタ画面 | PATCH | `/master/phases/{id}/move-up` | フェーズの表示順を上に移動する | auth | 管理者 |
| マスタ管理 | S-0903 フェーズマスタ画面 | PATCH | `/master/phases/{id}/move-down` | フェーズの表示順を下に移動する | auth | 管理者 |
| マスタ管理 | S-0903 フェーズマスタ画面 | PUT | `/master/phases/{id}` | フェーズの選択肢を更新する | auth | 管理者 |
| マスタ管理 | S-0903 フェーズマスタ画面 | DELETE | `/master/phases/{id}` | フェーズの選択肢を削除する | auth | 管理者 |
| セキュリティ設定 | S-1001 自動ログアウト画面 | GET | `/settings/auto-logout` | 自動ログアウト画面を表示する | auth | 管理者 |
| セキュリティ設定 | S-1001 自動ログアウト画面 | PUT | `/settings/auto-logout` | 自動ログアウト設定を更新する | auth | 管理者 |
| セキュリティ設定 | S-1002 IPアドレス制限画面 | GET | `/settings/ip-restriction` | IPアドレス制限画面を表示する | auth | システム管理者 |
| セキュリティ設定 | S-1002 IPアドレス制限画面 | PUT | `/settings/ip-restriction` | IPアドレス制限設定を更新する | auth | システム管理者 |
| 統計・集計 | S-1101 トレーニング記録数推移画面 | GET | `/statistics/clients` | トレーニング記録数推移画面を表示する | auth | 管理者、一般 |
| マイプロフィール | S-1201 マイプロフィール画面 | GET | `/profile` | マイプロフィール画面を表示する | auth | 全員 |
| マイプロフィール | S-1201 マイプロフィール画面 | PUT | `/profile` | プロフィールを更新する | auth | 全員 |
| マイプロフィール | S-1202 パスワード変更画面 | GET | `/profile/password` | パスワード変更画面を表示する | auth | 全員 |
| マイプロフィール | S-1202 パスワード変更画面 | PUT | `/profile/password` | パスワードを変更する | auth | 全員 |
| 共通 | ログアウト | POST | `/logout` | ログアウトする | auth | 全員 |
| 内部API | - | GET | `/api/clients/search` | クライアントを検索する | auth | 管理者、一般 |
| 内部API | - | POST | `/api/counseling-records/auto-create` | 音声記録の要約からトレーニング記録を作成する | auth | 管理者、一般 |
| 内部API | - | POST | `/api/audio-records/{id}/transcribe` | 文字起こしを実行する | auth | 管理者、一般 |
| 内部API | - | POST | `/api/audio-records/{id}/summarize` | 要約を実行する | auth | 管理者、一般 |
| 内部API | - | GET | `/api/audio-records/{id}/summary` | 音声記録の要約テキストを取得する | auth | 管理者、一般 |
| 内部API | - | GET | `/api/audio-records/summaries` | 要約取り込み候補の音声記録一覧を取得する | auth | 管理者、一般 |
| 内部API | - | GET | `/api/counselors` | 担当トレーナーの選択候補を取得する | auth | 管理者、一般 |

※ 認証列は実装のミドルウェア区分を示す。`public`＝認証不要（誰でもアクセス可）、`guest`＝未認証ユーザー向け（ログイン済みはホーム画面へリダイレクト）、`auth`＝要認証（ログイン済みのトレーナー）。
※ 権限列は認証後のロール制限を示す。`-`＝認証不要のため対象外、`全員`＝ログイン済みの全トレーナー（システム管理者を含む）、`管理者、一般`＝システム管理者を除く実務トレーナー、`管理者`＝管理者のみ、`システム管理者`＝システム管理者のみ。

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
- 最終トレーニング日が新しい順にソート（NULLは最後）

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
| primary_counselor_id | integer | | nullable, exists:counselors,id | |
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
| keyword | string | 氏名・かなの複合検索（本人の姓名およびそのかな、計4項目を部分一致でOR検索） |
| support_status_id | integer | 支援状態ID |
| primary_counselor_id | integer | 主担当トレーナーID |
| date_from | date | 最終トレーニング日（開始日） |
| date_to | date | 最終トレーニング日（終了日） |
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
- view `clients.show`（クライアント情報とトレーニング記録一覧。トレーニング記録は新しい順）


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

###### GET /counseling-records/create

**概要**: トレーニング記録登録画面を表示する。

**クエリパラメータ**:
| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| client_id | integer | ● | 登録対象クライアントID |
| audio_file_id | integer | | 録音実行画面（S-0502）からの遷移時に引き継ぐ音声記録ID |

**レスポンス**:
- view `counseling-records.create`
- client_id 未指定・不存在：クライアント一覧へリダイレクト


###### POST /counseling-records

**概要**: トレーニング記録を新規登録する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| client_id | integer | ● | required, exists:clients,id | クライアントID |
| consultation_date | date | ● | required, date | トレーニング日（過去・未来日付とも入力可） |
| consultation_time | time | | nullable, date_format:H:i | トレーニング時刻 |
| consultation_type_id | integer | | nullable, exists:consultation_types,id | トレーニング内容マスタID |
| consultation_detail | string | | nullable, string, max:255 | トレーニング内容の詳細 |
| counselor1_id | integer | ● | required, exists:counselors,id | 担当1 |
| counselor2_id | integer | | nullable, exists:counselors,id, different:counselor1_id | 担当2（担当1と異なること） |
| record_content | text | | nullable, string | トレーニング記録 |
| impression | text | | nullable, string | 所感 |
| phase_id | integer | | nullable, exists:phases,id | フェーズID |

**レスポンス**:
- 成功：`redirect('/clients/{client_id}')`
- 失敗：`back()` ＋ バリデーションエラーメッセージ

---

##### S-0402 トレーニング記録一覧画面

###### GET /counseling-records

**概要**: トレーニング記録一覧画面を表示する。

**クエリパラメータ**（すべて任意）:

| パラメータ | 型 | 説明 |
|-----------|-----|------|
| internal_id | string | クライアントの内部ID（部分一致） |
| name | string | クライアントの氏名（本人の姓名およびそのかな、計4項目を部分一致でOR検索） |
| date_from | date | トレーニング日（開始日） |
| date_to | date | トレーニング日（終了日） |
| counselor_id | integer | 担当トレーナーID（担当1・担当2のいずれかに一致） |
| keyword | string | 記録内容・所感・トレーニング内容詳細を対象としたキーワード検索（部分一致でOR検索） |
| sort | string | ソートカラム（consultation_date, internal_id, client_name, created_at）。未指定時は consultation_date。internal_idは数値順、client_nameは画面表示上の氏名で五十音順 |
| direction | string | ソート方向（asc, desc）。asc以外はdesc |

**レスポンス**:

- view `counseling-records.index`

---

##### S-0403 トレーニング記録詳細画面

###### GET /counseling-records/{id}

**概要**: トレーニング記録詳細画面を表示する。

**レスポンス**:
- view `counseling-records.show`


###### DELETE /counseling-records/{id}

**概要**: トレーニング記録を削除する。

**処理**:
- 物理削除（論理削除ではない）

**レスポンス**:
- 成功：そのトレーニング記録が紐づくクライアントの詳細画面へリダイレクト（`redirect('/clients/{client_id}')`）
- 権限なし：403エラー（「管理者のみ削除できます。」）

---

##### S-0404 トレーニング記録編集画面

###### GET /counseling-records/{id}/edit

**概要**: トレーニング記録編集画面を表示する。

**レスポンス**:
- view `counseling-records.edit`


###### PUT /counseling-records/{id}

**概要**: トレーニング記録を更新する。

**リクエスト**:
POST /counseling-records と同じ。

**処理**:
- トレーニング記録を更新する

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
4. POST /api/counseling-records/auto-create で要約からトレーニング記録を作成

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
| counselor_id | string | | — | トレーナーで絞り込む。未指定はログイン中のトレーナー、`all` で全トレーナー、トレーナーIDの指定でそのトレーナーの音声記録 |

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

###### GET /counselors/create

**概要**: トレーナー登録画面を表示する。

**レスポンス**:
- view `counselors.create`


###### POST /counselors

**概要**: トレーナーを登録する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| login_id | string | ● | required, string, max:50, regex:`/^[a-zA-Z0-9_]+$/`, unique:counselors | ログインID（半角英数字とアンダースコア） |
| name | string | ● | required, string, max:100 | 氏名 |
| password | string | ● | required, string, confirmed, StrongPassword | パスワード |
| password_confirmation | string | ● | — | パスワード（確認） |
| role | string | ● | required, in:admin,staff | 権限 |

**処理**:
- 初回ログイン時にパスワード変更を必須とする状態で作成する
- 表示順は末尾に自動で付番する

**レスポンス**:
- `redirect('/counselors')`

---

##### S-0802 トレーナー管理画面

###### GET /counselors

**概要**: トレーナー管理画面を表示する。

**処理**:
- システム管理者を除くトレーナーの一覧を表示する（各トレーナーの主担当クライアント数、トレーニング記録の担当件数を含む）

**レスポンス**:
- view `counselors.index`


###### PATCH /counselors/{id}/move-up

**概要**: トレーナーの表示順を上に移動する。

**処理**:
- 一覧の表示順で、ひとつ上のトレーナーと表示順を入れ替える

**レスポンス**:
- `redirect('/counselors')`


###### PATCH /counselors/{id}/move-down

**概要**: トレーナーの表示順を下に移動する。

**処理**:
- 一覧の表示順で、ひとつ下のトレーナーと表示順を入れ替える

**レスポンス**:
- `redirect('/counselors')`


###### PATCH /counselors/{id}/unlock

**概要**: アカウントロックを解除する。

**処理**:
- アカウントのロックを解除し、ログイン失敗の記録をリセットする

**レスポンス**:
- `redirect('/counselors')`


###### PATCH /counselors/{id}/toggle-active

**概要**: アカウントの有効/無効を切り替える。

**処理**:
- アカウントの有効・無効を切り替える
- 次の場合は無効化できない：システム管理者、自分自身、有効な管理者が自分ひとりだけの管理者
- 無効化されたトレーナーはログインできなくなる。主担当の割り当ては残るが、主担当の付け替え候補には表示されない

**レスポンス**:
- `redirect('/counselors')`


###### DELETE /counselors/{id}

**概要**: トレーナーを削除する。

**処理**:
- トレーナーを削除する（物理削除）
- 次の場合は削除できない：システム管理者、自分自身、最後の管理者、主担当クライアントがいる、トレーニング記録の担当者になっている

**レスポンス**:
- `redirect('/counselors')`

---

##### S-0803 トレーナー編集画面

###### GET /counselors/{id}/edit

**概要**: トレーナー編集画面を表示する。

**レスポンス**:
- view `counselors.edit`


###### PUT /counselors/{id}

**概要**: トレーナーを更新する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| name | string | ● | required, string, max:100 | 氏名 |
| role | string | ● | required, in:admin,staff | 権限 |

**処理**:
- 最後の管理者を一般に変更することはできない

**レスポンス**:
- `redirect('/counselors')`

---

##### S-0804 パスワードリセット画面

###### GET /counselors/{id}/reset-password

**概要**: パスワードリセット画面を表示する。

**レスポンス**:
- view `counselors.reset-password`


###### PUT /counselors/{id}/reset-password

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
- `redirect('/counselors')`

---

##### S-0805 トレーナー操作履歴画面

###### GET /access-logs

**概要**: トレーナー操作履歴画面を表示する。

**クエリパラメータ**（すべて任意）:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| counselor_id | integer | | — | トレーナーで絞り込む |
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

###### GET /master/consultation-types

**概要**: トレーニング内容マスタ管理画面を表示する。

**処理**:
- トレーニング内容の一覧を表示順で表示する（各トレーニング内容を参照しているトレーニング記録数を含む）

**レスポンス**:
- view `master.consultation-types.index`


###### POST /master/consultation-types

**概要**: トレーニング内容の選択肢を追加する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| name | string | ● | required, string, max:50, unique:consultation_types | トレーニング内容名 |

**処理**:
- 表示順は末尾に自動で付番する

**レスポンス**:
- `redirect('/master/consultation-types')`


###### PUT /master/consultation-types/{id}

**概要**: トレーニング内容の選択肢を更新する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| name | string | ● | required, string, max:50, unique:consultation_types（自身を除く） | トレーニング内容名 |

**レスポンス**:
- `redirect('/master/consultation-types')`


###### DELETE /master/consultation-types/{id}

**概要**: トレーニング内容の選択肢を削除する。

**処理**:
- このトレーニング内容を参照しているトレーニング記録がある場合は削除できない（物理削除）

**レスポンス**:
- `redirect('/master/consultation-types')`


###### PATCH /master/consultation-types/{id}/move-up

**概要**: トレーニング内容の表示順を上に移動する。

**処理**:
- 表示順で、ひとつ上のトレーニング内容と表示順を入れ替える

**レスポンス**:
- `redirect('/master/consultation-types')`


###### PATCH /master/consultation-types/{id}/move-down

**概要**: トレーニング内容の表示順を下に移動する。

**処理**:
- 表示順で、ひとつ下のトレーニング内容と表示順を入れ替える

**レスポンス**:
- `redirect('/master/consultation-types')`

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

##### S-1001 自動ログアウト画面

###### GET /settings/auto-logout

**概要**: 自動ログアウト画面を表示する。

**レスポンス**:
- view `settings.auto-logout`

###### PUT /settings/auto-logout

**概要**: 自動ログアウト設定を更新する。

**リクエスト**:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| auto_logout_minutes | integer | ● | required, integer, in:0,3,5,10,15,30,60 | 自動ログアウトまでの時間（分）。0は無効 |

**レスポンス**:
- `redirect('/settings/auto-logout')`

---

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


#### 4-1-11. 統計・集計

##### S-1101 トレーニング記録数推移画面

###### GET /statistics/clients

**概要**: トレーニング記録数推移画面を表示する。

**クエリパラメータ**（すべて任意）:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| counselor_id | string | | — | トレーナーで絞り込む（`all` または トレーナーID）。一般トレーナー（staff）の場合は無視され、自分が担当するトレーニング記録のみが集計される |
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


#### 4-1-13. 共通

##### ログアウト

###### POST /logout

**概要**: ログアウトする。

**処理**:
- ログアウトを操作履歴に記録する

**レスポンス**:
- `redirect('/login')`

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
| q | string | | — | 検索キーワード。管理番号、本人の氏名・かなで部分一致検索する |
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

##### POST /api/counseling-records/auto-create

**概要**: 音声記録の要約からトレーニング記録を作成する内部API。

**リクエスト**（JSON）:

| パラメータ | 型 | 必須 | バリデーション | 説明 |
|-----------|-----|------|---------------|------|
| audio_record_id | integer | ● | required, exists:audio_records,id | 元の音声記録ID |
| client_id | integer | ● | required, exists:clients,id | クライアントID |
| consultation_date | date | ● | required, date | トレーニング日 |
| consultation_time | time | | nullable, date_format:H:i | トレーニング時刻 |
| counselor1_id | integer | ● | required, exists:counselors,id | 担当1 |
| counselor2_id | integer | | nullable, exists:counselors,id, different:counselor1_id | 担当2（担当1と異なること） |

**処理**:
- 音声記録の要約テキストをトレーニング記録の記録内容に取り込み、トレーニング記録を作成する

**レスポンス**（JSON）:
- 成功：`{ "success": true, "counseling_record_id": <作成したトレーニング記録のID> }`
- 失敗：`{ "success": false }`

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

##### GET /api/counselors

**概要**: 担当トレーナーの選択候補を返す内部API。録音実行画面で担当トレーナーを選択するために使用する。

**処理**:
- システム管理者を除くトレーナーを表示順に返す

**レスポンス**（JSON）:
- `[ ... ]`

| フィールド | 型 | 説明 |
|-----------|-----|------|
| id | integer | トレーナーID |
| name | string | 氏名 |
