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
    Route::get('/projects', [PageController::class, 'show'])->defaults('page', 'Projects.index')->name('projects');
    Route::get('/projects/create', function () {
        return view('Projects.form');
    })->name('projects.create');

    // @TODO: once Project model + migrations exist, swap the closure for a real
    // controller method and pass the actual project in:
    // Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::get('/projects/{id}/edit', function ($id) {
        return view('Projects.form'); // form.blade.php currently ignores $id — hardcoded data only
    })->name('projects.edit');

    Route::get('/preplist', [PageController::class, 'show'])->defaults('page', 'Preplist.index')->name('preplist');
    Route::get('/quotation', [PageController::class, 'show'])->defaults('page', 'Quotation.index')->name('quotation');
    Route::get('/entities', [PageController::class, 'show'])->defaults('page', 'Entities.index')->name('entities');
    Route::get('/priceindex', [PageController::class, 'show'])->defaults('page', 'Priceindex.index')->name('priceindex');
    Route::get('/settings', [PageController::class, 'show'])->defaults('page', 'Settings.index')->name('settings');
});
