<?php

use Illuminate\Support\Facades\Route;

/*
| CVH Africa custom pages (loaded from the end of routes/web.php).
| Same middleware as the template's main "Web" route group.
*/
Route::group(['namespace' => 'Web', 'middleware' => ['check_mobile_app', 'impersonate', 'share', 'check_maintenance', 'check_restriction']], function () {
    Route::get('/innovations', 'CvhInnovationsController@index');
    Route::get('/innovations/{slug}', 'CvhInnovationsController@show');
});
