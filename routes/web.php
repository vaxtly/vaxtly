<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'api-tester')->name('home');
Route::livewire('/docs', 'docs-page')->name('docs');
