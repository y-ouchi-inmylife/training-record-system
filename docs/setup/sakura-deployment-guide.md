# デプロイ手順書: さくらのレンタルサーバー

**対象システム**: カウンセリング記録管理システム
**作成日**: 2026-03-20
**対象サーバー**: さくらのレンタルサーバー

---

## 目次

[前提条件](#1-前提条件)

[初回デプロイ手順](#2-初回デプロイ手順)
1. [SSH接続設定](#2-1-ssh接続設定)
2. [ドメイン・SSL設定](#2-2-ドメインssl設定)
3. [データベース作成](#2-3-データベース作成)
4. [ファイルのアップロード](#2-4-ファイルのアップロード)
5. [Composerインストール](#2-5-composerインストール)
6. [PHPバージョンの確認・切り替え](#2-6-phpバージョンの確認・切り替え)

7. [環境変数設定](#2-7-環境変数設定)
8. [マイグレーション実行](#2-8-マイグレーション実行)
9. [公開ディレクトリの設定](#2-9-公開ディレクトリの設定)
10. [CRON設定](#2-10-cron設定)
11. [動作確認](#2-11-動作確認)

[再デプロイ手順](#3-再デプロイ手順)
1. [基本手順（コード変更のみ）](#3-1-基本手順コード変更のみ)
2. [フロントエンド変更を含む場合](#3-2-フロントエンド変更を含む場合)
3. [マイグレーションを含む場合](#3-3-マイグレーションを含む場合)
4. [Composer依存パッケージの変更を含む場合](#3-4-composer依存パッケージの変更を含む場合)
5. [環境変数（.env）の変更のみ](#3-5-環境変数envの変更のみ)
6. [再デプロイ時の注意事項](#3-6-再デプロイ時の注意事項)

---

## 1. 前提条件

### サーバー要件

| 項目 | 要件 |
|------|------|
| サーバー | さくらのレンタルサーバー |
| PHP | 8.2 以上 |
| データベース | MySQL 8.0（さくらはMySQL 8.0を提供） |
| ディスク容量 | 300GB（スタンダードプラン） |
| SSH | 利用可能（要有効化） |

### ローカル環境

| 項目 | 要件 |
|------|------|
| OS | Windows 10/11 |
| Git | インストール済み |
| SSH クライアント | Windows PowerShell または Git Bash |
| FTPクライアント | WinSCP または FileZilla（代替手順用） |

### 事前に用意するもの

- さくらのレンタルサーバーのアカウント情報（初期ドメイン: `xxx.sakura.ne.jp`）
- 独自ドメイン（任意）
- 本システムのソースコード

---

## 2. 初回デプロイ手順

### 2-1. SSH接続設定

#### 2-1-1. SSH機能の有効化

1. さくらのコントロールパネルにログインする
2. 「サーバー情報」→「SSH」を選択する
3. 「SSHを有効にする」をクリックする

#### 2-1-2. SSH鍵の作成（Windows PowerShell）

PowerShellを開いて以下のコマンドを実行する:

```powershell
# SSH鍵を作成（Ed25519推奨）
ssh-keygen -t ed25519 -C "your-email@example.com"

# 保存先はデフォルト（C:\Users\ユーザー名\.ssh\id_ed25519）でEnter
# パスフレーズを設定（推奨）

# 公開鍵の内容を表示
Get-Content ~/.ssh/id_ed25519.pub
```

#### 2-1-3. 公開鍵の登録

1. コントロールパネルの「サーバー情報」→「SSH公開鍵」を選択する
2. 「公開鍵の登録」をクリックする
3. 手順3-2で表示された公開鍵の内容を貼り付ける
4. 「登録」をクリックする

#### 2-1-4. SSH接続テスト

```powershell
# SSH接続テスト
ssh xxx@xxx.sakura.ne.jp

# 接続成功後、サーバー情報を確認
php -v
pwd
```

#### 2-1-5. SSH設定ファイルの作成（任意）

接続を簡略化するため、`~/.ssh/config` に設定を追加する:

```
Host sakura
    HostName xxx.sakura.ne.jp
    User xxx
    IdentityFile ~/.ssh/id_ed25519
    Port 22
```

以降、`ssh sakura` で接続可能になります。


### 2-2. ドメイン・SSL設定

#### 2-2-1. 独自ドメインの設定（任意）

1. コントロールパネルの「ドメイン」→「ドメイン追加」を選択する
2. 独自ドメインを入力して追加する
3. ドメインのネームサーバーをさくらのネームサーバーに変更する:
   - `ns1.dns.ne.jp`
   - `ns2.dns.ne.jp`

#### 2-2-2. SSL証明書の設定（Let's Encrypt）

1. コントロールパネルの「ドメイン」→対象ドメインの「SSL」を選択する
2. 「無料SSL（Let's Encrypt）」を選択する
3. 「設定する」をクリックする
4. 証明書の発行完了まで数分〜数時間待つ

#### 2-2-3. HTTPS リダイレクト設定

SSL設定完了後、HTTPからHTTPSへのリダイレクトを設定します。Laravelの `public/.htaccess` に統合して管理します。

```bash
# SSH接続後、Laravelの public/.htaccess を編集
cd ~/app/counseling-record-system/src/public

# .htaccess の先頭（<IfModule mod_rewrite.c> の直後）にHTTPSリダイレクトルールを追加
sed -i '/<IfModule mod_rewrite.c>/,/RewriteEngine On/ {
    /RewriteEngine On/a\
\
    # Force HTTPS\
    RewriteCond %{HTTPS} off\
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
}' .htaccess

# 変更内容を確認
cat .htaccess
```

**注意**: この変更はサーバー上の `public/.htaccess` に直接行います。Laravelの `.htaccess` にはデフォルトで `RewriteEngine On` が含まれているため、HTTPSリダイレクトルールのみ追加します。ローカル環境には影響しません。


### 2-3. データベース作成

#### 2-3-1. データベースの作成

1. コントロールパネルの「データベース」を選択する
2. 「データベース新規作成」をクリックする
3. 以下の情報を入力する:

| 項目 | 設定値 | メモ |
|------|-------|------|
| データベース名 | `xxx_counseling`（`xxx`はアカウント名） | |
| データベース文字コード | `UTF-8（utf8mb4）` | |
| データベースパスワード | 強力なパスワードを設定 | |

4. 「作成する」をクリックする

#### 2-3-2. 接続情報の記録

データベース作成後、以下の接続情報を記録する:

| 項目 | 値 | メモ |
|------|---|------|
| ホスト名 | `mysql○○.db.sakura.ne.jp` | |
| データベース名 | `xxx_counseling` | |
| ユーザー名 | `xxx` | |
| パスワード | （設定したパスワード） | |

**注意**: この情報は後の `.env` ファイル設定で使用します。安全に保管してください。


### 2-4. ファイルのアップロード

#### 2-4-1. ディレクトリ構成

さくらのレンタルサーバーでのディレクトリ構成:

```
/home/xxx/                          ← ホームディレクトリ
├── www/                            ← 公開ディレクトリ（ドキュメントルート）
│   └── counseling/                 ← アプリの公開ディレクトリ（シンボリックリンク先）
├── app/                            ← アプリケーション本体（非公開）
│   └── counseling-record-system/
│       └── src/                    ← Laravelプロジェクト
│           ├── app/
│           ├── public/             ← 公開ファイル
│           ├── storage/
│           ├── .env
│           └── ...
└── .ssh/
```

#### 2-4-2. Gitを使用したデプロイ

##### ローカルからの準備

```powershell
# ローカルリポジトリに移動
cd C:\Users\y-ouchi\workspace\dev\counseling-record-system

# Viteビルドを実行（CSS/JSの本番ビルド）
cd src
npm run build
cd ..

# ※ さくらの共有サーバーにはNode.js環境がないため、
#    ローカルでビルドした public/build をGit経由でデプロイする
#    （public/build は追跡対象のため通常の git add で取り込まれる）

# コミット・プッシュ
git add .
git commit -m "deploy: production release"
git push origin main
```

##### サーバー側の準備

```bash
# SSH接続
ssh sakura

# アプリケーションディレクトリを作成
mkdir -p ~/app

# リポジトリをクローン
cd ~/app
git clone --no-checkout https://github.com/your-username/counseling-record-system.git

# `docs/`、`tests/` 等の開発用ディレクトリを配置しないための設定
cd counseling-record-system
git sparse-checkout init --cone
git sparse-checkout set src
git checkout main

# 確認（src のみが対象になっていること）
git sparse-checkout list

# === 2回目以降の更新 ===
cd ~/app/counseling-record-system
git pull origin main
```

### 2-5. Composerインストール

#### 2-5-1. Composerのインストール

```bash
# ホームディレクトリに移動
cd ~

# Composerをダウンロード
curl -sS https://getcomposer.org/installer | php

# composerコマンドとして使えるように配置
mkdir -p ~/bin
mv composer.phar ~/bin/composer

# PATHに追加（.bash_profileに追記）
echo 'export PATH="$HOME/bin:$PATH"' >> ~/.bash_profile
source ~/.bash_profile

# 動作確認
composer --version
```

#### 2-5-2. 依存パッケージのインストール

```bash
# プロジェクトディレクトリに移動
cd ~/app/counseling-record-system/src

# 本番環境用にインストール（開発用パッケージを除外）
composer install --no-dev --optimize-autoloader

# インストール完了を確認
ls vendor/
```

#### 2-6. PHPバージョンの確認・切り替え

#### 2-6-1. PHPバージョンの確認

```bash
# SSH接続
ssh sakura

# 現在のPHPバージョンを確認
php -v

# さくらのレンタルサーバーでPHP 8.2以上を使用する設定
# コントロールパネルの「スクリプト設定」→「PHPバージョン」で変更可能

# コマンドラインでのPHPパスを確認
which php

# さくらのPHP 8.x パス（例）
# /usr/local/php/8.2/bin/php
```

**注意**: さくらのレンタルサーバーでは、コントロールパネルからPHPバージョンを切り替えた場合、CLI版PHPのパスが自動的に変わらない場合があります。その場合、フルパスを指定してください。


#### 2-6-2. PHPプラットフォームバージョンの設定

ローカル環境（PHP 8.4）とサーバー環境（PHP 8.2）のバージョン差異を吸収するため、Composerのプラットフォーム設定を行います。この設定はローカル環境で実行してください。

```powershell
# ローカル環境（Windows PowerShell）で実行
cd C:\Users\y-ouchi\workspace\dev\counseling-record-system\src

# Composerにサーバーと同じPHPバージョンを指定
composer config platform.php 8.2.28

# composer.lock を再生成
composer update --lock

# 変更をコミット
cd ..
git add src/composer.json src/composer.lock
git commit -m "chore: set platform PHP version to 8.2 for server compatibility"
git push origin main
```

**注意**: この設定により、ローカルでPHP 8.3以上の機能を使うパッケージが誤ってインストールされることを防ぎます。`composer.json` の `config.platform.php` に設定が追加され、`composer.lock` がサーバーのPHPバージョンに合わせて生成されます。


### 2-7. 環境変数設定

#### 2-7-1. .envファイルの作成

```bash
# プロジェクトディレクトリに移動
cd ~/app/counseling-record-system/src

# .envファイルを作成
cp .env.example .env
```

##### 方法A: viエディタで編集

```bash
vi .env
```

##### 方法B: sedコマンドで編集（viを使わない場合）

```bash
# 各設定値を個別に変更する（以下は主要な設定項目の例）
sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env
sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
sed -i 's|^APP_URL=.*|APP_URL=https://your-domain.com|' .env
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
sed -i 's/^DB_HOST=.*/DB_HOST=mysql○○.db.sakura.ne.jp/' .env
sed -i 's/^DB_PORT=.*/DB_PORT=3306/' .env
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=xxx_counseling/' .env
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=xxx/' .env
sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=ここにパスワードを入力/' .env
sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=database/' .env
sed -i 's/^SESSION_LIFETIME=.*/SESSION_LIFETIME=120/' .env
sed -i 's/^CACHE_STORE=.*/CACHE_STORE=database/' .env
sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' .env

# APIキー・タイムゾーン等の値を設定（.env.exampleに空値で存在するキーはsedで上書き）
sed -i 's|^OPENAI_API_KEY=.*|OPENAI_API_KEY=sk-xxxxxxxxxxxxxxxx|' .env
sed -i 's|^ANTHROPIC_API_KEY=.*|ANTHROPIC_API_KEY=sk-ant-xxxxxxxxxxxxxxxx|' .env
sed -i 's|^OPENAI_REQUEST_TIMEOUT=.*|OPENAI_REQUEST_TIMEOUT=300|' .env
sed -i 's|^APP_TIMEZONE=.*|APP_TIMEZONE=Asia/Tokyo|' .env

# .env.exampleに存在しないキーのみ追記（AUDIO_STORAGE_PATH）
echo '' >> .env
echo '# 音声ファイル保存先' >> .env
echo 'AUDIO_STORAGE_PATH=storage/app/audio' >> .env

# 設定内容を確認
cat .env
```

#### 2-7-2. .envファイルの設定内容（本番環境用）

```env
# アプリケーション設定
APP_NAME="カウンセリング記録管理システム"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

# ログ設定
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DAILY_DAYS=30
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info

# データベース設定（手順5で記録した情報を入力）
DB_CONNECTION=mysql
DB_HOST=mysql○○.db.sakura.ne.jp
DB_PORT=3306
DB_DATABASE=xxx_counseling
DB_USERNAME=xxx
DB_PASSWORD=ここにパスワードを入力

# セッション設定
SESSION_DRIVER=database
SESSION_LIFETIME=120

# キャッシュ設定
CACHE_STORE=database

# キュー設定（同期実行 — キューワーカー不要）
QUEUE_CONNECTION=sync

# 音声機能（API キー）
OPENAI_API_KEY=sk-xxxxxxxxxxxxxxxx
OPENAI_REQUEST_TIMEOUT=300
ANTHROPIC_API_KEY=sk-ant-xxxxxxxxxxxxxxxx

# 音声ファイル保存先
AUDIO_STORAGE_PATH=storage/app/audio

# タイムゾーン
APP_TIMEZONE=Asia/Tokyo

# Backup
BACKUP_DIRECTORY=/home/inmylife1965/app/counseling-record-system/src/storage/app/backups
BACKUP_ENCRYPTION_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
MYSQLDUMP_PATH="/usr/local/bin/mysqldump"
MYSQL_PATH="/usr/local/bin/mysql"
OPENSSL_PATH="/usr/bin/openssl"

# Backup File Storage (S3 compatible)
# 現在の構成: Cloudflare R2（BACKUP_STORAGE_REGION は R2 では未指定でよい。既定 auto）
BACKUP_STORAGE_ENDPOINT=https://7e2d676e31f746acd5bcc20fd6333e6c.r2.cloudflarestorage.com
BACKUP_STORAGE_BUCKET=counseling-record-backup-dev
BACKUP_STORAGE_ACCESS_KEY_ID=xxxxxxxxxxxxxxxxxxxxxxxxxxxx
BACKUP_STORAGE_SECRET_ACCESS_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**セキュリティに関する注意事項**:
- `APP_DEBUG=false` を必ず設定すること（trueだとエラー詳細が表示される）
- APIキーは外部に漏洩しないよう厳重に管理すること
- `.env` ファイルのパーミッションを `600` に設定すること

#### 2-7-3. アプリケーションキーの生成

```bash
cd ~/app/counseling-record-system/src

# アプリケーションキーを生成
php artisan key:generate

# キーが設定されたことを確認
grep APP_KEY .env
```

#### 2-7-4. .envファイルの権限設定

```bash
# .envファイルのパーミッションを制限
chmod 600 .env
```

#### 2-7-5. ストレージディレクトリの権限設定

```bash
cd ~/app/counseling-record-system/src

# ストレージディレクトリに書き込み権限を付与
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# ストレージリンクを作成
php artisan storage:link
```

#### 2-7-6. 環境変数のバックアップ（推奨）

`.env` ファイルの内容を **1Password等のパスワードマネージャーにセキュアノートとして保存** することを強く推奨します。

**保存すべき項目**:

- `.env` ファイル全体（最も確実）
- 特に重要な項目: `APP_KEY`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`

**理由**:

- サーバー障害、誤操作、sparse-checkout 等で `.env` が消失した場合に迅速に復旧できる
- `APP_KEY` が変わると暗号化データが読めなくなるリスクがある
- **2026年4月16日の障害で、1Passwordに保存していたおかげで迅速に復旧できた実績がある**


### 2-8. マイグレーション実行

#### 2-8-1. データベース接続テスト

```bash
cd ~/app/counseling-record-system/src

# データベース接続テスト
php artisan db:show

# 接続成功の場合、データベース情報が表示される
```

#### 2-8-2. マイグレーション実行

```bash
# マイグレーションを実行
php artisan migrate --force

# マイグレーション状態を確認
php artisan migrate:status
```

#### 2-8-3. 初期データ投入

```bash
# シーダーを実行（初期データ投入）
php artisan db:seed --force

# 初期データの確認
php artisan tinker --execute="echo App\Models\Counselor::count() . ' counselors created';"
```


### 2-9. 公開ディレクトリの設定

#### 2-9-1. シンボリックリンクの作成

さくらのレンタルサーバーでは、公開ディレクトリ（ドキュメントルート）が `~/www/` に設定されています。Laravelの `public/` ディレクトリをシンボリックリンクで公開する設定を行います。

```bash
# 既存の公開ディレクトリを確認
ls ~/www/

# Laravelのpublicディレクトリへのシンボリックリンクを作成
# ※独自ドメインのサブディレクトリとして公開する場合
ln -s ~/app/counseling-record-system/src/public ~/www/counseling

# ※ドメイン直下で公開する場合（既存のwwwの内容をバックアップしてから）
# mv ~/www ~/www_backup
# ln -s ~/app/counseling-record-system/src/public ~/www
```

#### 2-9-2. .htaccess の確認

Laravelの `public/.htaccess` が正しく設定されていることを確認する:

```bash
cat ~/app/counseling-record-system/src/public/.htaccess
```

Laravel標準の `public/.htaccess` の内容（HTTPSリダイレクト追加済み）:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

上記の内容と一致しているか確認してください。差異がある場合は、上記の内容で上書きしてください。

#### 2-9-3. ストレージのシンボリックリンク

```bash
cd ~/app/counseling-record-system/src

# ストレージのシンボリックリンクを作成（音声ファイル等のアクセス用）
php artisan storage:link
```


#### 2-10. CRON設定

#### 2-10-1. さくらのCRON設定方法

1. コントロールパネルにログインする
2. 「スケジュール設定」→「CRON」を選択する
3. 以下のCRONジョブを追加する

##### 音声ファイル自動削除（毎日午前1時）

| 項目 | 設定値 |
|------|-------|
| 実行コマンド | `cd /home/inmylife1965/app/counseling-record-system/src && /usr/local/bin/php artisan audio-records:delete-expired --days=7 1> /dev/null` |
| 実行日時 | `* * 1 0` |

##### 長期間未ログインアカウントの自動ロック（毎日午前2時）

| 項目 | 設定値 |
|------|-------|
| 実行コマンド | `cd /home/inmylife1965/app/counseling-record-system/src && /usr/local/bin/php artisan counselors:lock-inactive --days=30 1> /dev/null` |
| 実行日時 | `* * 2 0` |

##### 未使用アカウントの自動ロック（毎日午前2時）

| 項目 | 設定値 |
|------|-------|
| 実行コマンド | `cd /home/inmylife1965/app/counseling-record-system/src && /usr/local/bin/php artisan counselors:lock-unused --days=7 1> /dev/null` |
| 実行日時 | `* * 2 0` |

##### バックアップ（毎日午前3時）

| 項目 | 設定値 |
|------|-------|
| 実行コマンド | `cd /home/inmylife1965/app/counseling-record-system/src && /usr/local/bin/php artisan db:backup 1> /dev/null` |
| 実行日時 | `* * 3 0` |


---

## 3. 再デプロイ手順

コード修正後に本番環境を更新する手順です。変更内容に応じて必要な作業が異なります。

### 3-1. 基本手順（コード変更のみ）

最も頻度の高いパターンです。PHP/Bladeファイルの修正、ロジック変更など。

#### ローカル側（Windows PowerShell）

```powershell
cd ~/counseling-record-system

git add .
git commit -m "fix: 修正内容の説明"
git push origin main
```

#### サーバー側

```bash
ssh sakura
cd ~/app/counseling-record-system
git pull origin main

cd src

# キャッシュをクリアして再作成
php artisan config:cache
php artisan route:cache
php artisan view:cache
```


### 3-3. マイグレーションを含む場合

テーブルの追加・変更など、データベース構造に変更がある場合。

#### ローカル側（Windows PowerShell）

```powershell
cd ~/counseling-record-system

git add .
git commit -m "feat: マイグレーション追加の説明"
git push origin main
```

#### サーバー側

```bash
ssh sakura
cd ~/app/counseling-record-system
git pull origin main

cd src

# データベースをクリア
% php artisan migrate:fresh

# マイグレーションを実行
php artisan migrate --force

# マイグレーション状態を確認
php artisan migrate:status

# シーダーを実行


# キャッシュをクリアして再作成
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**注意**: 本番データに影響するマイグレーション（カラム削除、テーブル削除など）は、事前にバックアップを取得してから実行してください。

```bash
# バックアップの手動実行
php artisan db:backup
```

### 3-4. Composer依存パッケージの変更を含む場合

`composer.json` に新しいパッケージを追加・更新した場合。

#### ローカル側（Windows PowerShell）

```powershell
cd ~/counseling-record-system/src

# パッケージを追加・更新
composer require 新しいパッケージ名

# composer.lock の変更をコミット
cd ..
git add src/composer.json src/composer.lock
git add .
git commit -m "chore: パッケージ追加の説明"
git push origin main
```

#### サーバー側

```bash
ssh sakura
cd ~/app/counseling-record-system
git pull origin main

cd src

# 依存パッケージを再インストール
composer install --no-dev --optimize-autoloader

# キャッシュをクリアして再作成
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3-5. 環境変数（.env）の変更のみ

APIキーの変更、設定値の調整など。Gitを使わずサーバー上で直接編集します。

#### 方法A: viエディタで編集

```bash
ssh sakura
cd ~/app/counseling-record-system/src
vi .env
```

#### 方法B: sedコマンドで編集

```bash
ssh sakura
cd ~/app/counseling-record-system/src

# 例: APIキーの変更
sed -i 's/^OPENAI_API_KEY=.*/OPENAI_API_KEY=sk-新しいキー/' .env

# 変更内容を確認
grep OPENAI_API_KEY .env

# 設定キャッシュを再作成
php artisan config:cache
```

### 3-6. 再デプロイ時の注意事項

**キャッシュについて**: 本番環境では `config:cache`、`route:cache`、`view:cache` でキャッシュを作成しています。コード変更後にキャッシュをクリア・再作成しないと、古い設定やルーティングが使われ続けます。

**切り戻し（ロールバック）**: デプロイ後に問題が発生した場合、以下の手順で前のバージョンに戻せます。

```bash
ssh sakura
cd ~/app/counseling-record-system

# 直前のコミットに戻す
git log --oneline -5    # コミット履歴を確認
git checkout 戻したいコミットハッシュ

cd src
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**マイグレーションのロールバック**: DBマイグレーションを含むデプロイで問題が発生した場合は、マイグレーションも戻す必要があります。

```bash
cd ~/app/counseling-record-system/src

# 直前のマイグレーションを1つ戻す
php artisan migrate:rollback --step=1
```

### 4. バックアップのリストア

```bash
cd ~/app/counseling-record-system/src

php artisan db:restore バックアップファイル[例:inmylife1965_counseling_00_20260603_180000.sql.enc]
```