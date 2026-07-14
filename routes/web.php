<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('fake.auth')->group(function () {
    Route::get('/projects', [PageController::class, 'show'])->defaults('page', 'projects')->name('projects');
    Route::get('/pricing', [PageController::class, 'show'])->defaults('page', 'pricing')->name('pricing');
    Route::get('/quotation', [PageController::class, 'show'])->defaults('page', 'quotation')->name('quotation');
    Route::get('/entities', [PageController::class, 'show'])->defaults('page', 'entities')->name('entities');
    Route::get('/users', [PageController::class, 'show'])->defaults('page', 'users')->name('users');
    Route::get('/settings', [PageController::class, 'show'])->defaults('page', 'settings')->name('settings');
});
