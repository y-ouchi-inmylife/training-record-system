<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ImageMagick 実行パス
    |--------------------------------------------------------------------------
    |
    | 表示用変換（heic→jpeg 等）で呼び出す magick コマンドのパス。
    |
    | Linux 本番 : Ubuntu 24.04 標準の ImageMagick は 6 系で `magick` コマンドは
    |               なく、`/usr/bin/convert` のみ。`.env` で
    |               `MAGICK_PATH=/usr/bin/convert` を指定する。未設定だと既定値の
    |               `magick` が使われて変換に失敗する。
    | Windows 開発: 開発サーバー（Herd / php -S）の子プロセスでは PATH 解決に
    |               失敗するため、`.env` で magick.exe のフルパスを指定する。
    |
    | 既定値を 'magick' のままにしているのは、開発環境の ImageMagick 7 系
    | （`magick` コマンドが正式名称）に合わせているため。
    |
    */

    'magick_path' => env('MAGICK_PATH', 'magick'),

    /*
    |--------------------------------------------------------------------------
    | FFmpeg 実行パス
    |--------------------------------------------------------------------------
    |
    | 表示用変換（mov→mp4 等）で呼び出す ffmpeg コマンドのパス。
    |
    | Linux 本番 : `ffmpeg` で PATH 解決可能なため未設定でよい（デフォルト 'ffmpeg'）。
    | Windows 開発: 開発サーバー（Herd / php -S）の子プロセスでは PATH 解決に
    |               失敗するため、`.env` で ffmpeg.exe のフルパスを指定する。
    |
    */

    'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),

];
