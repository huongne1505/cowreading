<?php

use Illuminate\Support\Facades\Route;

// Redirect root to homepage
Route::get('/', function () {
    return redirect('/index.html');
});

// Serve static HTML files
Route::get('/index.html', function () {
    return response()->file(public_path('index.html'));
});

Route::get('/category.html', function () {
    return response()->file(public_path('category.html'));
});

Route::get('/categories.html', function () {
    return response()->file(public_path('categories.html'));
});

Route::get('/reader.html', function () {
    return response()->file(public_path('reader.html'));
});

Route::get('/auth.html', function () {
    return response()->file(public_path('auth.html'));
});

// Fallback route for any other requests
Route::fallback(function () {
    return redirect('/index.html');
});
