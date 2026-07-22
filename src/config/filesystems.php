<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        // メディア（写真・動画）保存用。プロバイダは .env で差し替え可能
        // （さくらのオブジェクトストレージ / Cloudflare R2 / AWS S3 等の S3 互換）
        'media' => [
            'driver' => 's3',
            'key' => env('MEDIA_STORAGE_ACCESS_KEY_ID'),
            'secret' => env('MEDIA_STORAGE_SECRET_ACCESS_KEY'),
            'region' => env('MEDIA_STORAGE_REGION', 'auto'),
            'bucket' => env('MEDIA_STORAGE_BUCKET'),
            'endpoint' => env('MEDIA_STORAGE_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'throw' => true,
            'report' => false,
        ],

        // DB バックアップ保存用。プロバイダは .env で差し替え可能
        'backup' => [
            'driver' => 's3',
            'key' => env('BACKUP_STORAGE_ACCESS_KEY_ID'),
            'secret' => env('BACKUP_STORAGE_SECRET_ACCESS_KEY'),
            'region' => env('BACKUP_STORAGE_REGION', 'auto'),
            'bucket' => env('BACKUP_STORAGE_BUCKET'),
            'endpoint' => env('BACKUP_STORAGE_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'throw' => true,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
