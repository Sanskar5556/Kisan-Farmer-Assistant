<?php

use Illuminate\Support\Facades\Facade;

return [
    'name'            => env('APP_NAME', 'Kisan Smart Assistant'),
    'env'             => env('APP_ENV', 'local'),
    'debug'           => (bool) env('APP_DEBUG', true),
    'url'             => env('APP_URL', 'http://localhost:8000'),
    'asset_url'       => env('ASSET_URL'),
    'timezone'        => 'Asia/Kolkata',
    'locale'          => 'en',
    'fallback_locale' => 'en',
    'faker_locale'    => 'en_US',
    'key'             => env('APP_KEY'),
    'cipher'          => 'AES-256-CBC',
    'maintenance'     => ['driver' => 'file'],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    | Only what a JSON API needs — no Blade views, no sessions, no broadcasting
    |--------------------------------------------------------------------------
    */
    'providers' => [
        // Framework essentials
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Log\LogServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Routing\RoutingServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        // JWT Auth
        Tymon\JWTAuth\Providers\LaravelServiceProvider::class,

        // Our app providers
        App\Providers\AppServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ],

    'aliases' => Facade::defaultAliases()->merge([
        'JWTAuth'    => Tymon\JWTAuth\Facades\JWTAuth::class,
        'JWTFactory' => Tymon\JWTAuth\Facades\JWTFactory::class,
    ])->toArray(),
];
