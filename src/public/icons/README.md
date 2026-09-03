# public/icons

トレーナー側／クライアント側 PWA・favicon 用アイコン一式。

## 位置づけ

- 正式ロゴが確定するまでの**暫定アイコン**である
- 正式ロゴ確定後は、`favicon-trainer.svg` / `favicon-client.svg` を差し替え、下記コマンドを再実行して PNG / ICO を同名で置き換える
- 元となる `src/public/favicon.svg` および `favicon.ico` / `apple-touch-icon.png` は `recording-v2/session.blade.php` が参照しているため削除しないこと

## 色

- クライアント用: `#D85A30`（現行 `favicon.svg` の色をそのまま流用）
- トレーナー用: `#0a4fa8`（`resources/sass/app.scss` L17/L22 で既に使われている濃い青）

`favicon-trainer.svg` は `favicon-client.svg` の `#D85A30` を `#0a4fa8` に 2 箇所（背景矩形と時計の針）置換したもの。

## 生成コマンド

Windows PowerShell + ImageMagick 7 系（`magick`）で以下を実行する。作業ディレクトリはリポジトリルート。

```powershell
$icons = ".\src\public\icons"

# トレーナー用
magick -background none -density 1024 "$icons\favicon-trainer.svg" -resize 192x192 "$icons\icon-trainer-192.png"
magick -background none -density 1024 "$icons\favicon-trainer.svg" -resize 512x512 "$icons\icon-trainer-512.png"
magick -background none -density 1024 "$icons\favicon-trainer.svg" -resize 180x180 "$icons\apple-touch-icon-trainer.png"
magick -background none -density 1024 "$icons\favicon-trainer.svg" -define icon:auto-resize=48,32,16 "$icons\favicon-trainer.ico"

# クライアント用
magick -background none -density 1024 "$icons\favicon-client.svg" -resize 192x192 "$icons\icon-client-192.png"
magick -background none -density 1024 "$icons\favicon-client.svg" -resize 512x512 "$icons\icon-client-512.png"
magick -background none -density 1024 "$icons\favicon-client.svg" -resize 180x180 "$icons\apple-touch-icon-client.png"
magick -background none -density 1024 "$icons\favicon-client.svg" -define icon:auto-resize=48,32,16 "$icons\favicon-client.ico"
```

- `-density 1024` で高解像度ラスタライズしてから `-resize` で縮小することで、32x32 viewBox の SVG を拡大した際のぼやけを防ぐ
- `-background none` は透過起点。SVG 自体が角丸の地色矩形を持つため、出力 PNG／ICO の地色は SVG のまま反映される
- ICO は `-define icon:auto-resize=48,32,16` でマルチサイズ（48/32/16）を 1 ファイルに格納

## 生成物

| ファイル | サイズ | 用途 |
|---|---|---|
| `favicon-trainer.svg` / `favicon-client.svg` | 32x32 viewBox | ブラウザタブ（SVG 対応） |
| `favicon-trainer.ico` / `favicon-client.ico` | 16/32/48 マルチ | ブラウザタブ（レガシー） |
| `apple-touch-icon-trainer.png` / `apple-touch-icon-client.png` | 180x180 | iOS ホーム画面 |
| `icon-trainer-192.png` / `icon-client-192.png` | 192x192 | PWA インストール（Android 等） |
| `icon-trainer-512.png` / `icon-client-512.png` | 512x512 | PWA インストール（スプラッシュ等） |

## manifest（`public/manifest-trainer.json` / `public/manifest-client.json`）に関する注記

- manifest は JSON 静的ファイルであり、コメントを埋め込めないためここに注記する
- `name` / `short_name` は **manifest に直書き**しており、`.env` の `CLIENT_PORTAL_NAME` などを変更しても manifest には反映されない
  - トレーナー側 `name`: `トレーニング記録管理システム` / `short_name`: `トレーニング記録`
  - クライアント側 `name`: `トレーニング記録` / `short_name`: `トレーニング記録`
- 表示名を変える場合は manifest ファイル自体を編集する必要がある
