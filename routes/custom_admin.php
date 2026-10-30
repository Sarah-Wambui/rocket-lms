<?php

/**
 * Custom Admin Routes File
 * 
 * This file allows adding custom admin routes without modifying the main admin.php
 * Routes defined here will be loaded by the main admin routes file.
 */

use Illuminate\Support\Facades\Route;

// Get the admin panel prefix from the main application
$prefix = getAdminPanelUrlPrefix();

/**
 * Define your custom admin panel routes here
 * They will be automatically loaded alongside the main admin routes
 * 
 * All routes will be prefixed with your admin panel prefix 
 * and will have 'web' and 'admin' middleware applied automatically
 */

// Example of custom routes:
// 
// Route::group(['prefix' => 'custom-section'], function () {
//     Route::get('/', 'YourCustomController@index')->name('admin.custom.index');
//     Route::get('/create', 'YourCustomController@create')->name('admin.custom.create');
//     Route::post('/store', 'YourCustomController@store')->name('admin.custom.store');
//     Route::get('/{id}/edit', 'YourCustomController@edit')->name('admin.custom.edit');
//     Route::post('/{id}/update', 'YourCustomController@update')->name('admin.custom.update');
//     Route::get('/{id}/delete', 'YourCustomController@delete')->name('admin.custom.delete');
// });

// You can add as many route groups as needed

// CVH Africa: course Q&A overview (Education > Course Forum)
Route::get('/cvh-course-qa', 'CvhCourseQaAdminController@index');
Route::post('/cvh-course-qa/{id}/delete', 'CvhCourseQaAdminController@destroy')->where('id', '[0-9]+');

// CVH Africa: admissions (Users > Admissions)
Route::group(['prefix' => 'cvh-admissions'], function () {
    Route::get('/', 'CvhAdmissionsAdminController@index');
    Route::get('/export', 'CvhAdmissionsAdminController@export');
    Route::post('/settings', 'CvhAdmissionsAdminController@saveSettings');
    Route::post('/{id}/mark-paid', 'CvhAdmissionsAdminController@markPaid')->where('id', '[0-9]+');
    Route::post('/{id}/{decision}', 'CvhAdmissionsAdminController@decide')->where(['id' => '[0-9]+', 'decision' => 'approve|reject']);
});

// CVH Africa: Google Meet for live sessions (settings in Settings > General > Features)
Route::group(['prefix' => 'cvh-google-meet'], function () {
    Route::get('/', 'CvhGoogleMeetController@index');
    Route::get('/connect', 'CvhGoogleMeetController@connect');
    Route::get('/callback', 'CvhGoogleMeetController@callback');
    Route::post('/test', 'CvhGoogleMeetController@test');
    Route::post('/disconnect', 'CvhGoogleMeetController@disconnect');
});

// CVH Africa: Google Meet engagement metrics (Education > Live Class Metrics)
Route::group(['prefix' => 'cvh-meet-metrics'], function () {
    Route::get('/', 'CvhMeetMetricsAdminController@index');
    Route::get('/{id}', 'CvhMeetMetricsAdminController@show')->where('id', '[0-9]+');
    Route::post('/{id}/sync', 'CvhMeetMetricsAdminController@sync')->where('id', '[0-9]+');
});

// CVH Africa: in-house Homepage Editor (Appearance > Homepage Editor)
Route::group(['prefix' => 'cvh-homepage'], function () {
    Route::get('/', 'CvhHomepageController@index');
    Route::post('/', 'CvhHomepageController@store');
    Route::get('/{id}/edit', 'CvhHomepageController@edit')->where('id', '[0-9]+');
    Route::post('/{id}/update', 'CvhHomepageController@update')->where('id', '[0-9]+');
    Route::post('/{id}/toggle', 'CvhHomepageController@toggle')->where('id', '[0-9]+');
    Route::post('/{id}/move/{direction}', 'CvhHomepageController@move')->where(['id' => '[0-9]+', 'direction' => 'up|down']);
    Route::post('/{id}/delete', 'CvhHomepageController@destroy')->where('id', '[0-9]+');
});

/**
 * To use these routes, you must have your controller in App\Http\Controllers\Admin namespace
 * or specify the complete namespace like:
 * 
 * Route::get('/custom-page', '\App\Http\Controllers\YourNamespace\YourController@method');
 */ 