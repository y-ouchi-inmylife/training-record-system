<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'Asia/Tokyo',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Trainer Portal Branding（画面設計書 §2-3「ブラウザタイトル」）
    |--------------------------------------------------------------------------
    |
    | トレーナー側画面のブランド関連文字列。
    |
    | - trainer_portal_name: layouts/app・layouts/guest・layouts/error の
    |   <title> 末尾サフィックスに使用する（画面設計書 §2-3）。
    |
    | APP_NAME を流用しない理由: APP_NAME は MAIL_FROM_NAME / VITE_APP_NAME /
    | cache・session の Str::slug プレフィックスに波及しており、
    | ブラウザタイトルの変更が上記に影響しないよう独立キーで扱う。
    |
    */

    'trainer_portal_name' => env('TRAINER_PORTAL_NAME', 'トレーニング記録管理システム'),

    /*
    |--------------------------------------------------------------------------
    | Client Portal Branding（設計書 §9-1 / §9-2）
    |--------------------------------------------------------------------------
    |
    | クライアントポータルのブランド関連文字列。Blade にベタ書きせず、
    | env 経由で切り替えできるようにする。
    |
    | - client_portal_name: ログイン画面のワードマーク・タイトルに表示する
    |   プロダクト名。設計書 §9-1 の暫定値は「トレーニング記録」（8 文字）。
    |   想定文字数 3〜7 文字だが 8 文字でも組めるようレイアウト側で対応。
    | - client_portal_company: フッターに表示するトレーニング提供会社名
    |   （開発会社ではない）。設計書 §9-2 に従い、null / 空の場合は
    |   Blade 側で <footer> ブロックごと出力しない。
    |
    */

    'client_portal_name' => env('CLIENT_PORTAL_NAME', 'トレーニング記録'),

    'client_portal_company' => env('CLIENT_PORTAL_COMPANY'),

];
