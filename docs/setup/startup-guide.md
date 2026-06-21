# 開発環境の起動手順および停止手順

トレーニング記録管理システムの開発環境起動手順です。

## 前提条件

- Windows 11
- Docker Desktop がインストール済み
- PHP 8.2 以上がインストール済み
- Composer がインストール済み

---

## 起動手順

### 1. Docker Desktop を起動

1. スタートメニューから「Docker Desktop」を起動
2. Docker Desktop が完全に起動するまで待つ（タスクトレイのアイコンが安定するまで）
3. Docker Desktop のダッシュボードが開いたら、左下の「Engine running」が表示されていることを確認

### 2. MySQLコンテナを起動

PowerShell を開いて以下のコマンドを実行：
```powershell
# MySQLコンテナを起動
docker start training-mysql

# 起動確認
docker ps
```

**確認ポイント**:
- `training-mysql` が `Up` 状態になっているか
- `0.0.0.0:3308->3306/tcp` が表示されているか

**エラーが出た場合**:
```powershell
# コンテナが存在しない場合は、再作成
docker run --name training-mysql `
  -e MYSQL_ROOT_PASSWORD=root `
  -e MYSQL_DATABASE=training_record `
  -e MYSQL_USER=laravel `
  -e MYSQL_PASSWORD=laravel `
  -p 3308:3306 `
  -v training-mysql-data:/var/lib/mysql `
  -d mysql:8.0 `
  --character-set-server=utf8mb4 `
  --collation-server=utf8mb4_unicode_ci
```

### 3. JavaScript・CSSのビルド（初回および resources/ 変更時のみ）
```powershell
cd ~\workspace\dev\training-record-system\src
npm install
npm run build
```

**補足**: ビルド成果物 `public/build/` は `.gitignore` に含まれているため、ローカル環境では必ずビルドが必要。`@vite()` ディレクティブを含むBladeテンプレートを表示するには `public/build/manifest.json` が必要で、未生成の場合500エラーになる。

### 4. 開発サーバーを起動
```powershell
cd ~\workspace\dev\training-record-system\src
php -S 127.0.0.1:8080 -t public
```

**表示されるメッセージ**:
```
[Sat Mar 15 12:00:00 2026] PHP 8.4.16 Development Server (http://127.0.0.1:8080) started
```

### 5. ブラウザでアクセス

ブラウザで以下のURLを開く：
```
http://localhost:8080
```

ログイン画面が表示されればOK！

#### ログインアカウント

##### システム管理者アカウント

- **ユーザーID**: `system_admin`
- **パスワード**: `InMyLife1965!`
- **権限**: システム管理者（設定のみアクセス可能）

##### 管理者アカウント

- **ユーザーID**: `admin`
- **パスワード**: `InMyLife1965!`
- **権限**: 管理者（全機能アクセス可能）

##### 一般アカウント

- **ユーザーID**: `staff`
- **パスワード**: `InMyLife1965!`
- **権限**: 一般（基本機能のみ）

---

## 停止手順

### 1. 開発サーバーを停止

開発サーバーの PowerShell で `Ctrl + C` を押す

### 2. MySQLコンテナを停止（オプション）
```powershell
docker stop training-mysql
```

**注意**: コンテナを停止しなくても問題ありませんが、PCのリソースを節約したい場合は停止してください。

### 3. Docker Desktop を停止（オプション）

タスクトレイの Docker Desktop アイコンを右クリック → 「Quit Docker Desktop」

## トラブルシューティング

### エラー: "SQLSTATE[HY000] [2002] 対象のコンピューターによって拒否されたため、接続できませんでした"

**原因**: MySQLコンテナが起動していない

**解決方法**:
1. Docker Desktop が起動しているか確認
2. `docker start training-mysql` を実行
3. `docker ps` で起動確認

### エラー: "Address already in use"

**原因**: ポート 8080 が既に使用されている

**解決方法**:
1. 別のポートを使用: `php -S 127.0.0.1:8081 -t public`
2. ブラウザで `http://localhost:8081` を開く

### エラー: "No such container: training-mysql"

**原因**: MySQLコンテナが作成されていない

**解決方法**:
上記の「MySQLコンテナを起動」の「エラーが出た場合」の手順でコンテナを再作成

### エラー: "failed to connect to the docker API"

**原因**: Docker Desktop が起動していない

**解決方法**:
1. Docker Desktop を起動
2. 完全に起動するまで待つ
3. 再度コマンドを実行

---

## バッチ実行手順





---

## 参考情報

- **プロジェクトディレクトリ**: `C:\Users\y-ouchi\workspace\dev\training-record-system\src`
- **データベース名**: `training_record`
- **データベースユーザー**: `laravel`
- **データベースパスワード**: `laravel`
- **MySQLポート**: `3308`
- **開発サーバーURL**: `http://localhost:8080`

---

## まとめ：起動に必要なターミナル

| ターミナル | コマンド | 必須 |
|-----------|---------|------|
| 1（Docker） | `docker start training-mysql` | 必須 |
| 2（開発サーバー） | `php -S 127.0.0.1:8080 -t public` | 必須 |

---

作成日: 2026-03-15
更新日: 2026-05-22
