<?php

use App\Providers\NativeAppServiceProvider;

it('returns opcache php.ini directives', function () {
    $provider = new NativeAppServiceProvider;
    $ini = $provider->phpIni();

    expect($ini)
        ->toHaveKey('opcache.enable', '1')
        ->toHaveKey('opcache.enable_cli', '1')
        ->toHaveKey('opcache.memory_consumption', '128')
        ->toHaveKey('opcache.interned_strings_buffer', '8')
        ->toHaveKey('opcache.max_accelerated_files', '10000')
        ->toHaveKey('opcache.validate_timestamps', '0')
        ->toHaveKey('memory_limit', '512M');
});
