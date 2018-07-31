<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. A "local" driver, as well as a variety of cloud
    | based drivers are available for your choosing. Just store away!
    |
    | Supported: "local", "ftp", "s3", "rackspace"
    |
    */

    'default' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => 's3',

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    | -- Uses environment variables from .env to ensure consistency across app
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'visibility' => 'public',
        ],
//Figures that were used in previous edition are only stored in prod
        's3prod' => [
            'driver' => 's3',
            'key'    => env('AWS_s3_KEY', ''),
            'secret' => env('AWS_s3_SECRET', ''),
            'region' => env('AWS_s3_REGION', ''),
            'bucket' =>  env('AWS_s3_PROD', ''),
        ],
//Figures that were newly suggested on Dev in testing will still only be in the Dev suggested bucket.
        's3devsuggested' => [
            'driver' => 's3',
            'key'    => env('AWS_s3_KEY', ''),
            'secret' => env('AWS_s3_SECRET', ''),
            'region' => env('AWS_s3_REGION', ''),
            'bucket' => env('AWS_s3_DEV', '').'/suggested/',
        ],

    ],

];
