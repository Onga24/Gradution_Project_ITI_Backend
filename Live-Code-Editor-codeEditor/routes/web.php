<?php

use Illuminate\Support\Facades\Route;



Route::view('/{path?}', 'app')->where('path', '^(?!api).*');

// Route::get('/{any}', function () {
//     return view('app');
// })->where('any', '^(?!api).*$');

