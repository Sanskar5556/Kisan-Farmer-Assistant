<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['service' => 'Kisan Smart Assistant API', 'status' => 'running']);
});
