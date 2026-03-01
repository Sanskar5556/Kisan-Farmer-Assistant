<?php
// Debug script — runs a fake HTTP request through Laravel and shows the response
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

$body = json_encode([
    'name'                  => 'Debug Farmer',
    'email'                 => 'debug' . rand(100,999) . '@test.com',
    'password'              => '123456',
    'password_confirmation' => '123456',
]);

$req = Illuminate\Http\Request::create(
    '/api/auth/register',
    'POST',
    [],
    [],
    [],
    ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
    $body
);

try {
    $resp = $kernel->handle($req);
    echo "STATUS: " . $resp->getStatusCode() . PHP_EOL;
    echo "BODY: " . $resp->getContent() . PHP_EOL;
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "FILE: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    echo "TRACE: " . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
}
