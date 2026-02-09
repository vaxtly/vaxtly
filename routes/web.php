<?php

use App\Http\Controllers\ProxyRequestController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'api-tester')->name('home');
Route::livewire('/docs', 'docs-page')->name('docs');

Route::post('/internal/proxy-request', ProxyRequestController::class)->name('internal.proxy-request');
