<?php

use Illuminate\Support\Facades\Route;

/*
| CVH Africa custom pages (loaded from the end of routes/web.php).
| Same middleware as the template's main "Web" route group.
*/
Route::group(['namespace' => 'Web', 'middleware' => ['check_mobile_app', 'impersonate', 'share', 'check_maintenance', 'check_restriction']], function () {
    // Innovations showcase
    Route::get('/innovations', 'CvhInnovationsController@index');
    Route::get('/innovations/{slug}', 'CvhInnovationsController@show');

    // Community forum (in-house; the template's paid forum at /forums is untouched)
    Route::group(['prefix' => 'community'], function () {
        Route::get('/', 'CvhCommunityController@index');
        Route::get('/topic/{id}', 'CvhCommunityController@topic')->where('id', '[0-9]+');

        Route::group(['middleware' => 'web.auth'], function () {
            Route::get('/new', 'CvhCommunityController@create');
            Route::post('/topics', 'CvhCommunityController@store')->middleware('throttle:5,1');
            Route::post('/topic/{id}/reply', 'CvhCommunityController@reply')->where('id', '[0-9]+')->middleware('throttle:10,1');
            Route::post('/moderate', 'CvhCommunityController@moderate');
        });

        Route::get('/{slug}', 'CvhCommunityController@category');
    });

    // Course Q&A (in-house; replaces the paid Course Forum plugin feature)
    Route::group(['prefix' => '/course/{slug}', 'middleware' => 'web.auth'], function () {
        Route::get('/questions', 'CvhCourseQaController@index');
        Route::post('/questions', 'CvhCourseQaController@store')->middleware('throttle:5,1');
        Route::get('/questions/{id}', 'CvhCourseQaController@show')->where('id', '[0-9]+');
        Route::post('/questions/{id}/answers', 'CvhCourseQaController@answer')->where('id', '[0-9]+')->middleware('throttle:10,1');
        Route::get('/qa/{type}/{id}/edit', 'CvhCourseQaController@edit')->where(['type' => 'question|answer', 'id' => '[0-9]+']);
        Route::post('/qa/{type}/{id}/update', 'CvhCourseQaController@update')->where(['type' => 'question|answer', 'id' => '[0-9]+']);
        Route::post('/qa/action', 'CvhCourseQaController@action');
    });

    // Certificates of completion (in-house; the template's certificate plugin is not used)
    Route::get('/certificates/verify', 'CvhCertificatesController@verify');
    Route::group(['middleware' => 'web.auth'], function () {
        Route::get('/my-certificates', 'CvhCertificatesController@index');
        Route::get('/my-certificates/{webinarId}/download', 'CvhCertificatesController@download')->where('webinarId', '[0-9]+');

        // Admission (application form -> admission fee -> approval)
        Route::get('/admission', 'CvhAdmissionController@index');
        Route::post('/admission/pay', 'CvhAdmissionController@pay')->middleware('throttle:10,1');
        Route::get('/admission/payment/verify', 'CvhAdmissionController@verify');
    });
});
