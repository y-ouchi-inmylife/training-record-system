<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 役割別セッション設定
    |--------------------------------------------------------------------------
    | トレーナー（内部）とクライアント（外部）で lifetime / Cookie 名 / domain を
    | 分離する。ConfigureSessionByRole ミドルウェアが判定結果に応じて動的に
    | config('session.*') を上書きする。
    |
    | domain は env 未設定時に null を返す。ローカルでは両方 null で現ホスト限定 Cookie
    | として動作。本番はサブドメインごとに env で設定する。
    */

    'trainer' => [
        'lifetime' => (int) env('TRAINER_SESSION_LIFETIME', 120),
        'cookie'   => env('TRAINER_SESSION_COOKIE', 'trs01-staff-session'),
        'domain'   => env('TRAINER_SESSION_DOMAIN'),
    ],

    'client' => [
        'lifetime' => (int) env('CLIENT_SESSION_LIFETIME', 60),
        'cookie'   => env('CLIENT_SESSION_COOKIE', 'trs01-client-session'),
        'domain'   => env('CLIENT_SESSION_DOMAIN'),
    ],
];
