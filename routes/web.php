<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (request()->getHost() === parse_url(config('app.portal_url'), PHP_URL_HOST)) {
        return redirect()->route('portal.login');
    }

    return redirect('/admin/login');
});

require __DIR__.'/portal.php';
