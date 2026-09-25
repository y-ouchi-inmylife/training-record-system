# さくらのクラウド Ubuntu サーバー構築手順書（Laravel 用・サーバー単位）

## この手順書の範囲

この手順書は **サーバー1台につき1回だけ行う作業**（OS・ファイアウォール・SSH・ミドルウェアの導入）をまとめたものです。

アプリごとに行う作業（DB 作成、デプロイ、nginx のサイト設定、SSL 証明書の取得、ワーカー、cron など）は、各アプリの **アプリ単位手順書** に記載します。入口は「付録A アプリ追加チェックリスト」を参照。

- 対象 OS：Ubuntu 24.04 LTS
- 実績環境：sakura-cloud-prod-01（旧名 trs01-prod。現在はトレーニング記録システムが稼働）
- 表記：【sakura-cloud-prod-01 未適用】…手順としては推奨だが、sakura-cloud-prod-01 ではまだ実施していない

### 実績環境のバージョン（2026-09-25 確認、同日の更新適用後）

| 項目 | 説明 | 導入元 | バージョン |
|---|---|---|---|
| OS | サーバーの土台となる基本ソフト | さくらのクラウド パブリックアーカイブ | Ubuntu 24.04.5 LTS（カーネル 6.8.0-142） |
| nginx | Web サーバー。ブラウザからのアクセスを受け付ける | Ubuntu 標準パッケージ | 1.24.0 |
| PHP / PHP-FPM | Laravel を動かすプログラム言語と、nginx から PHP を呼び出す仕組み | ondrej PPA（`ppa:ondrej/php`） | 8.4.26 |
| MySQL | データベース。アプリのデータを保存する | Ubuntu 標準パッケージ | 8.0.46 |
| Composer | PHP のライブラリを取り込む道具 | 公式インストーラ | 2.10.2 |
| Node.js / npm | 画面の CSS・JavaScript をビルドする道具 | NodeSource | v22.23.3 / 10.9.9 |
| certbot（＋nginx プラグイン） | 無料の SSL 証明書を取得・自動更新する道具 | Ubuntu 標準パッケージ | 2.9.0 |
| supervisor | キューワーカーなどを常駐させ、止まったら自動で再起動する仕組み | 【sakura-cloud-prod-01 未適用】 | — |

---

## 0. 前提条件

**さくらのクラウド（IaaS）** で借りるのは「空の Linux サーバー1台」です。OS とログインユーザーは用意されますが、Web サーバーもデータベースも入っていません。それらを自分でインストール・設定します。
（**さくらのレンタルサーバー** は、Web サーバー・PHP・MySQL・メール送信・SSL 証明書などがあらかじめ用意された状態で借りるものです。）

### 0-1. 料金・課金の考え方

レンタルサーバーは「月額固定で借りる」ものだったが、さくらのクラウド（IaaS）は **リソースを作った時点から課金が始まる従量課金制** である。ここを理解しておかないと、想定外の請求につながる。

#### 料金体系のしくみ

- **サーバー**：仮想サーバーの作成完了・**起動した時点から課金開始**、**停止すると課金停止**。利用時間に応じて **自動的に一番安い料金が適用** される。1か月の稼働時間が10時間以内なら時間料金、20日までは日額料金、それ以降は月額料金に自動で切り替わる。本番サーバーは24時間稼働させるため、実質「月額固定」になる。
- **ディスク**：**作成した時点から、削除するまで課金** が続く。サーバーを停止しても、**ディスクを削除しない限りディスク分の料金は発生し続ける** 点に注意（レンタルサーバーには無かった感覚）。
- **データ転送量・リクエスト数による課金がない**。アクセス増で通信料が跳ねる心配がない（AWS 等との大きな違い）。円建てで為替変動リスクもない。
- リソースを何も追加していない状態（会員登録だけ）では課金されない。

#### おおよその金額感

- 最小構成の目安：サーバー（メモリ1GB）月額 約1,540円＋ディスク（40GB）月額 約880円＝**約2,420円から**。
- Web・PHP・MySQL・キューワーカーを1台に同居させる場合、メモリ1GB では不足する。
- 実際に作る前に、公式の **料金シミュレーション**（計算ツール）で見積もる。
- 運用開始後は、コントロールパネルの利用料金管理から **料金アラート機能** を設定しておく。
- 開発・検証用に一時的に立てたサーバーは、用が済んだら停止・削除して無駄な課金を避ける（停止してもディスク料金は残る）。

※金額は時期・条件により変動する。

### 0-2. スペック選びの前提（スペックは後から変えられる）

さくらのクラウドは後からスペックを変更できるが、**CPU・メモリとディスクで変えやすさが大きく異なる**。

#### CPU・メモリ：気軽に変えられる（両方向）

- 「プラン変更」機能で CPU コア数・メモリを変更できる。**スケールアップもスケールダウンも可能**。
- ※参考：さくらの「VPS」はスケールダウン不可だが、「クラウド」は両方向いける。

#### ディスク：増やせるが手間がかかる。縮小は不可

- 今のディスクを **その場で直接大きくすることはできない**。大きいディスクを新規作成し、作成時に既存ディスクを指定してデータをコピー → 「パーティションサイズの拡張」で新容量まで広げる → サーバーの接続先を新ディスクに付け替える。**データは保たれる** が工程が多く、コピーに時間がかかる。作業中はサーバーを停止する。
- **ハマりどころ**：コピーしただけでは容量が増えない。「パーティションサイズの拡張」の一手間が必須。
- **縮小はできない**。作りすぎると下げにくい。

#### スペック選びの方針

- **CPU・メモリは控えめに始めてよい**。負荷を見てから増減する。
- **ディスクはやや余裕を持たせる**。ただし写真・動画などの本体をオブジェクトストレージに置く設計なら、本体ディスクには OS・アプリ・DB しか乗らないので、極端に大きくする必要はない。
- **1台に複数アプリを同居させる場合**は、アプリを追加するタイミングでメモリを見直す。

#### 実績

- sakura-cloud-prod-01：CPU 2コア / メモリ 2GB / ディスク 40GB（根拠はトレーニング記録システムのアプリ単位手順書に記載）

---

## 第1段階：サーバー作成と OS の初期整備

### 1-1. サーバーを作成する（コントロールパネル）

1. **ゾーンを確認する**（例：東京第1／第2）。
2. **「＋追加」からサーバーを新規作成する**。
3. **OS アーカイブ**：Ubuntu 24.04 LTS。
4. **スペックを選ぶ**（0-2 の方針に従う）。
5. **SSH 公開鍵を登録する**。事前にローカル端末で鍵ペアを作成しておく。
6. **管理ユーザーのパスワード等、初期設定を行う**。ログインユーザーは `ubuntu`。
7. **パケットフィルタ**：割り当てない（未割当）。ファイアウォールはサーバー内の ufw（1-5）だけで制御する。
   - 補足：パケットフィルタはサーバーに届く手前で通信を遮断する仕組みで、ufw と併用すれば二重の防御になる。必要になったら後から NIC に割り当てられる。
8. **サーバーを起動し、グローバル IP アドレスを控える**。

実績（sakura-cloud-prod-01）：東京第2ゾーン（tk1b）、プラン 2Core-2GB、NIC は共有セグメント（帯域 100Mbps）、パケットフィルタ未割当。2026-07-04 作成。

### 1-2. ローカル端末から SSH でログインする

ローカル端末の SSH 設定ファイルに接続先を登録しておくと、`ssh <ホスト名>` だけで入れる。設定ファイルの場所は次のとおり（書き方は共通）。

- Windows：`C:\Users\<ユーザー名>\.ssh\config`
- Mac / Linux：`~/.ssh/config`

```
Host <ホスト名（例：sakura-cloud-prod-01）>
    HostName <サーバーのIPアドレス>
    User ubuntu
    IdentityFile ~/.ssh/<秘密鍵ファイル名>
```

```
ssh <ホスト名>
```

### 1-3. SSH のパスワードログインを無効にする

1-2 で鍵ログインできることを確認したら、続けて行う。Ubuntu の既定では、設定ファイルに記載がないと `PasswordAuthentication yes`（パスワードログイン可）になる。鍵ログインのみにする。

```bash
sudo tee /etc/ssh/sshd_config.d/99-hardening.conf > /dev/null << 'EOF'
PasswordAuthentication no
KbdInteractiveAuthentication no
PermitRootLogin no
EOF
sudo sshd -t
sudo systemctl restart ssh
```

**締め出し防止**：今のログインは閉じずに、**別のターミナルから `ssh <ホスト名>` で入れることを確認してから** 元の画面を閉じる。

確認：

```bash
sudo sshd -T | grep -Ei '^(passwordauthentication|permitrootlogin|pubkeyauthentication) '
```

期待値：`passwordauthentication no`、`permitrootlogin no`、`pubkeyauthentication yes`。

### 1-4. OS を最新にし、タイムゾーンを設定する

```bash
sudo apt update
sudo apt upgrade -y
sudo timedatectl set-timezone Asia/Tokyo
```

ホスト名を、アプリに依存しない名前（例：`sakura-cloud-prod-01`）に設定し、`/etc/hosts` にも登録する。

```bash
sudo hostnamectl set-hostname <ホスト名>
echo '127.0.1.1 <ホスト名>' | sudo tee -a /etc/hosts
hostnamectl
cat /etc/hosts
```

プロンプトの表示は、ログインし直すと新しい名前に変わる。

`System restart required` と表示されたら再起動する。

```bash
sudo reboot
```

### 1-5. ファイアウォール（ufw）を設定する

SSH・HTTP・HTTPS だけを開け、それ以外の受信は拒否する。MySQL（3306）は同一サーバー内からのみ使うため **外部には開けない**。

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
sudo ufw status verbose
```

期待する状態：`Default: deny (incoming), allow (outgoing)`、許可は 22（OpenSSH）/ 80 / 443 のみ（IPv4・IPv6 それぞれ）。

### 1-6. スワップを作成する

さくらのクラウドの Ubuntu は初期状態でスワップがない。メモリ 2GB 程度の構成で MySQL・PHP・メディア変換などが重なると、メモリ不足でプロセス（MySQL など）が強制終了されるおそれがあるため、2GB のスワップファイルを用意する。

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
free -h
```

---

## 第2段階：ミドルウェアのインストール

### 2-1. nginx

```bash
sudo apt install -y nginx
nginx -v
```

サイトごとの設定（`/etc/nginx/sites-available/<アプリ名>`）はアプリ単位手順書で行う。

### 2-2. PHP 8.4 と PHP-FPM（ondrej PPA）

Ubuntu 24.04 標準の PHP は 8.3 のため、PPA から 8.4 を入れる。

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml \
  php8.4-curl php8.4-zip php8.4-gd php8.4-bcmath php8.4-intl
php -v
php -m
```

- sakura-cloud-prod-01 の `php -m` には bcmath / curl / gd / intl / mbstring / mysqli / pdo_mysql / xml 系 / zip / Zend OPcache などが含まれる。新サーバーでも同じ構成になっていることを確認する。
- PHP の `imagick` 拡張は使っていない（画像変換は ImageMagick のコマンドを直接呼ぶ方式）。

### 2-3. MySQL 8

```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
mysql --version
```

アプリ用の DB とユーザーの作成は、アプリ単位手順書で行う（アプリごとに分ける）。

### 2-4. Composer（公式インストーラ）

Ubuntu 標準パッケージの Composer は古いため、公式インストーラを使う。

```bash
cd /tmp
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
```

getcomposer.org のダウンロードページに記載のハッシュ検証コマンドを実行してから、インストールする。

```bash
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
composer --version
```

### 2-5. Node.js（NodeSource・22 系）

本番でフロントをビルドするアプリのために入れる（ビルド成果物をコミットしているアプリでは使わない）。

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

### 2-6. supervisor　【sakura-cloud-prod-01 未適用】

キューワーカー（`php artisan queue:work`）を常駐させ、落ちても自動再起動させるために使う。

```bash
sudo apt install -y supervisor
sudo systemctl status supervisor
```

ワーカーの設定ファイル（`/etc/supervisor/conf.d/<アプリ名>-worker.conf`）はアプリ単位手順書で作成する。

### 2-7. certbot（Let's Encrypt）

```bash
sudo apt install -y certbot python3-certbot-nginx
certbot --version
```

- 証明書の取得（ドメインごと）はアプリ単位手順書で行う。nginx プラグイン（`--nginx`）を使うと、nginx の設定ファイルへの証明書の記述と HTTP→HTTPS のリダイレクトまで certbot が書き込む（該当行に `# managed by Certbot` の印が付く）。
- apt で入れると `certbot.timer` が登録され、1日2回、期限が近い証明書を自動更新する。
- 証明書を取得したら、自動更新が成功するかを確認する（本番の証明書は更新されない。途中で nginx の設定再読み込みが一瞬入る）：

```bash
systemctl list-timers | grep certbot
sudo certbot renew --dry-run
```

期待値：`certbot.timer` が一覧に出ること、`Congratulations, all simulated renewals succeeded` と表示されること。

実績（sakura-cloud-prod-01、2026-09-25）：apt 版・nginx プラグインで取得。タイマー稼働、dry-run 成功。

### 2-8. 完了確認

```bash
systemctl list-units --type=service --state=running | grep -E 'nginx|php|mysql|supervisor'
```

nginx / php8.4-fpm / mysql / supervisor が `running` であること。

---

## 第3段階：サーバーの定常保守

- **OS 更新**：定期的に `sudo apt update && sudo apt upgrade -y`。`System restart required` が出たら、アクセスの少ない時間帯に再起動する。
- **SSL 証明書**：`sudo certbot certificates` で有効期限を確認する（自動更新が効いていれば期限の 30 日前頃に更新される）。
- **ディスク・メモリ**：`df -h`、`free -h` で余裕を確認する。

---

## 付録A　アプリ追加チェックリスト

アプリを1つ載せるごとに行う作業。詳細は各アプリのアプリ単位手順書に記載する。

- [ ] アプリ固有のパッケージを入れる（例：trs01 は FFmpeg、ImageMagick、libheif）
- [ ] MySQL にアプリ専用の DB とユーザーを作成する
- [ ] `/var/www/<アプリ名>` に GitHub から clone する
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] （ビルドするアプリのみ）`npm install && npm run build`
- [ ] 本番用 `.env` を作成する（コミットしない）
- [ ] artisan は `sudo -u www-data php artisan …` で実行する（key:generate、migrate、キャッシュ生成）
- [ ] `storage/`、`bootstrap/cache/` の権限を設定する
- [ ] nginx のサイト設定を作成し、`sites-enabled` にリンクする
- [ ] DNS の A レコードをサーバーの IP に向ける
- [ ] certbot でドメインの SSL 証明書を取得する
- [ ] （キューを使うアプリのみ）supervisor にワーカー設定を追加する
- [ ] www-data の crontab にバックアップ等を登録する
- [ ] 同居するアプリが増えたら、メモリ（0-2）を見直す

---

## 付録B　sakura-cloud-prod-01 の対応状況

2026-09-25 に対応済み：

- [x] 1-3 SSH のパスワードログイン無効化（`/etc/ssh/sshd_config.d/99-hardening.conf`）
- [x] 1-6 スワップ作成（2GB、`/etc/fstab` に登録）
- [x] 保留中の更新の適用と再起動（Ubuntu 24.04.5、カーネル 6.8.0-142）
- [x] サーバー名の変更（旧名 trs01-prod → sakura-cloud-prod-01）：ローカル端末の `~/.ssh/config`、サーバー内のホスト名と `/etc/hosts`、コントロールパネルのサーバー名

再起動後の確認：ホスト名・スワップ・SSH 設定が保たれ、nginx / php8.4-fpm / mysql が起動、certbot タイマーとバックアップの cron も残っていることを確認済み。

未対応：

- [ ] 2-6 supervisor の導入（トレーニング記録システムのキュー切り替えとあわせて行う）
