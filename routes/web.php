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
    Route::get('/projects/{id}', function ($id) {
        return view('Projects.show'); // show.blade.php currently ignores $id — hardcoded data only
    })->name('projects.show');

    // @TODO: once Project model + migrations exist, swap the closure for a real
    // controller method and pass the actual project in:
    // Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::get('/projects/{id}/edit', function ($id) {
        return view('Projects.form', compact('id')); // pass $id so form can switch to edit mode
    })->name('projects.edit');

    Route::get('/preplist', [PageController::class, 'show'])->defaults('page', 'Preplist.index')->name('preplist');

    // @TODO: once the Preplist model + migrations exist, swap the closure for a real
    // controller method and pass the actual preplist in:
    // Route::get('/preplist/{id}', [PreplistController::class, 'show']);
    //
    // NOTE: /preplist/create must be declared BEFORE /preplist/{id} so "create"
    // is matched as a literal path, not captured by the {id} wildcard.
    Route::get('/preplist/create', function () {
        return view('Preplist.form'); // no $prepList → renders Add mode
    })->name('preplist.create');

    // @TODO: once models + migrations exist, pass the actual $prepList in:
    // Route::get('/preplist/{id}', [PreplistController::class, 'show']);
    Route::get('/preplist/{id}', function ($id) {
        return view('Preplist.show'); // show.blade.php currently ignores $id — hardcoded data only
    })->name('preplist.show');

    // Edit (reuses Preplist/form.blade.php, which also serves Add).
    // @TODO: once models + migrations exist, pass the real preplist in:
    // Route::get('/preplist/{id}/edit', [PreplistController::class, 'edit'])->name('preplist.edit');
    Route::get('/preplist/{id}/edit', function ($id) {
        return view('Preplist.form', ['prepList' => ['id' => $id, 'name' => 'PURCHASE OF PHARMA SUPPLIES']]);
    })->name('preplist.edit');
    Route::get('/quotation', [PageController::class, 'show'])->defaults('page', 'Quotation.index')->name('quotation');
    Route::get('/entities', [PageController::class, 'show'])->defaults('page', 'Entities.index')->name('entities');
    Route::get('/priceindex', [PageController::class, 'show'])->defaults('page', 'Priceindex.index')->name('priceindex');
    Route::get('/settings', [PageController::class, 'show'])->defaults('page', 'Settings.index')->name('settings');
});
