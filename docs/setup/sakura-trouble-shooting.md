# トラブルシューティング: さくらのレンタルサーバー

**対象システム**: カウンセリング記録管理システム
**作成日**: 2026-05-22
**対象サーバー**: さくらのレンタルサーバー

---

# トラブルシューティング

## 1. 500 Internal Server Error

**原因と対処法**:

| # | 原因 | 対処法 |
|---|------|-------|
| 1 | `.htaccess` の設定誤り | `public/.htaccess` の内容を確認する |
| 2 | PHPバージョンが古い | コントロールパネルでPHP 8.2以上に変更する |
| 3 | `storage/` の権限不足 | `chmod -R 775 storage bootstrap/cache` を実行する |
| 4 | `.env` ファイルがない | `.env` ファイルを作成し、設定を入力する |
| 5 | `APP_KEY` が未設定 | `php artisan key:generate` を実行する |

**ログの確認方法**:

```bash
# Laravelのログを確認
tail -50 ~/app/counseling-record-system/src/storage/logs/laravel.log

# Apacheのエラーログを確認（さくらの場合）
tail -50 ~/logs/error_log
```

## 2. Database Connection Error

**原因と対処法**:

| # | 原因 | 対処法 |
|---|------|-------|
| 1 | ホスト名が間違っている | コントロールパネルで正しいホスト名を確認する |
| 2 | パスワードが間違っている | コントロールパネルでパスワードをリセットする |
| 3 | データベース名が間違っている | `xxx_counseling` 形式であることを確認する |

```bash
# データベース接続テスト
php artisan db:show

# 接続情報の確認
grep DB_ .env
```

## 3. CSRF Token Mismatch（419エラー）

**原因と対処法**:

| # | 原因 | 対処法 |
|---|------|-------|
| 1 | セッションの保存先に権限がない | `chmod -R 775 storage/framework/sessions` を実行する |
| 2 | セッションドライバーの設定 | `.env` で `SESSION_DRIVER=database` を確認する |
| 3 | `APP_URL` が実際のURLと異なる | `.env` の `APP_URL` を実際のURLに合わせる |

```bash
# セッションファイルのクリア
php artisan session:clear

# キャッシュのクリア
php artisan cache:clear
php artisan config:clear
```

## 4. 文字起こし・要約が実行されない

**原因と対処法**:

| # | 原因 | 対処法 |
|---|------|-------|
| 1 | `QUEUE_CONNECTION` が `sync` でない | `.env` で `QUEUE_CONNECTION=sync` を確認する |
| 2 | APIキーが未設定 | `.env` で `OPENAI_API_KEY` と `ANTHROPIC_API_KEY` を確認する |
| 3 | 設定キャッシュが古い | `php artisan config:clear` を実行する |

```bash
# 設定キャッシュをクリア
php artisan config:clear

# エラーログを確認
tail -50 ~/app/counseling-record-system/src/storage/logs/laravel.log
```

## 5. 文字起こし・要約がタイムアウトする

**原因と対処法**:

| # | 原因 | 対処法 |
|---|------|-------|
| 1 | タイムアウト設定が短い | `.env` で `OPENAI_REQUEST_TIMEOUT=300` を確認する |
| 2 | 音声ファイルが非常に大きい | 2時間を超える音声は分割を検討する |
| 3 | PHPの実行時間制限 | `php.ini` で `max_execution_time = 300` を確認する |

## 6. Viteマニフェストエラー・不可解な500エラー

フロントエンド資産（CSS/JS）に関連する500エラーや `Vite manifest not found` エラーが発生した場合の対処法です。

**原因と対処法**:

| # | 原因 | 対処法 |
|---|------|-------|
| 1 | `public/build/manifest.json` が存在しない | ローカルで `npm run build` → `git add -f src/public/build` → push → サーバーで pull |
| 2 | manifest.json内のファイル名と実際のファイルが不一致 | `public/build/assets/` 内のファイル名が manifest.json に記載されたファイル名と一致しているか確認する |
| 3 | ビルド成果物が古い | ローカルで `npm run build` を再実行し、最新のビルド成果物をデプロイする |

```bash
# サーバー上での確認手順
ssh sakura
cd ~/app/counseling-record-system/src

# manifest.json の存在確認
ls -la public/build/manifest.json

# manifest.json の内容確認（参照しているファイル名を確認）
cat public/build/manifest.json

# assets ディレクトリ内のファイル一覧と照合
ls -la public/build/assets/
```

**補足**: さくらの共有サーバーにはNode.js環境がないため、`npm run build` はローカルで実行する必要があります。Bladeテンプレートが `@vite()` ディレクティブでCSS/JSを読み込んでいる場合、manifest.json が存在しないと500エラーになります。

---

## 7. 音声ファイルのアップロードに失敗する

**原因と対処法**:

| # | 原因 | 対処法 |
|---|------|-------|
| 1 | PHPのアップロード上限 | `php.ini` で `upload_max_filesize` と `post_max_size` を増やす |
| 2 | ストレージの権限 | `chmod -R 775 storage/app` を実行する |

さくらのレンタルサーバーでのPHP設定変更方法:

```bash
# ホームディレクトリに php.ini を作成
cat > ~/www/php.ini << 'EOF'
upload_max_filesize = 512M
post_max_size = 520M
max_execution_time = 300
memory_limit = 512M
EOF
```

**注意**: `php.ini` の配置先はサーバーの構成（特にシンボリックリンク使用時）によって有効にならない場合があります。上記で動作しない場合は、以下の配置先も試してください:

- `~/www/counseling/php.ini`（サブディレクトリで公開している場合）
- `~/app/counseling-record-system/src/public/php.ini`（Laravelのpublicディレクトリ直下）

実際にどの配置で有効になるかは、デプロイ後に `phpinfo()` で確認してください。

## 8. sparse-checkout 設定後の500エラー

**発生日**: 2026年4月16日

**原因**: sparse-checkout の `set` コマンドでワーキングツリーが再構築され、`.env` と `vendor/` が削除された

**症状**: HTTP 500 Internal Server Error。`storage/logs/laravel.log` が存在しない（storage/ 自体が消失している場合がある）

**復旧手順**:

```bash
ssh sakura
cd ~/app/counseling-record-system/src

# 1. .env を復元（1Passwordのセキュアノートから）
vi .env
# セキュアノートの内容を貼り付けて保存

# 2. vendor/ を復元
composer install --no-dev --optimize-autoloader

# 3. ストレージ権限の再設定
chmod -R 775 storage bootstrap/cache

# 4. DB接続確認（エラーが出なければDB接続OK）
php artisan cache:clear

# 5. キャッシュクリア
php artisan config:clear
php artisan view:clear
php artisan route:clear

# 6. ストレージリンクの再作成
php artisan storage:link

# 7. ブラウザで動作確認
```

> **教訓**: sparse-checkout の `set` コマンドを実行する前に、必ず `.env` のバックアップを取得すること。1Passwordへの保存手順は「[8-6. 環境変数のバックアップ](#8-6-環境変数のバックアップ推奨)」を参照。
