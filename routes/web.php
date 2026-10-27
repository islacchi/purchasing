<?php

/**
 * Web routes.
 *
 * Routes outside the `fake.auth` middleware group are public; routes inside
 * the group require the user to be logged in.
 *
 * NOTE: Several show/edit routes currently use inline closures with dummy
 * data because the underlying models/migrations don't exist yet. See the
 * inline @TODO comments for the intended controller replacements.
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;

// Redirect the root path to the login page.
Route::get('/', function () {
    return redirect('/login');
});

// Public fake-auth routes (any credentials work for now).
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated routes — these require the session `logged_in` flag.
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
    // Generate quotation page (dummy data for now).
    // @TODO: once models + migrations exist, pass the real prep list in:
    // Route::get('/quotation/generate/{prepList}', [QuotationController::class, 'generate']);
    Route::get('/quotation/generate', function () {
        return view('Preplist.generate_rfq'); // view uses hardcoded dummy data
    })->name('quotation.generate');
    Route::get('/quotation', [PageController::class, 'show'])->defaults('page', 'Quotation.index')->name('quotation');

    // Quotation detail (dummy data for now — the view uses $id to pick a hardcoded record).
    // @TODO: once the Quotation/RFQ models + migrations exist, pass the real quotation in:
    // Route::get('/quotation/{id}', [QuotationController::class, 'show'])->name('quotation.show');
    Route::get('/quotation/{id}', function ($id) {
        return view('Quotation.show', ['id' => $id]);
    })->name('quotation.show');

    // Add / Edit entity form — one view rendered three ways based on {type}.
    // NOTE: /entities/{type}/create must be declared BEFORE /entities/{type}/{id}
    // so "create" is matched as a literal path, not captured by the {id} wildcard.
    // @TODO: once models + migrations exist, swap the closures for controller methods:
    // Route::get('/entities/{type}/create', [EntityController::class, 'create'])->name('entities.create');
    // Route::get('/entities/{type}/{id}/edit', [EntityController::class, 'edit'])->name('entities.edit');
    Route::get('/entities/{type}/create', function ($type) {
        return view('Entities.form', ['type' => $type]); // no $id → renders Add mode
    })->whereIn('type', ['user', 'procuring_entity', 'supplier'])->name('entities.create');

    Route::get('/entities/{type}/{id}/edit', function ($type, $id) {
        return view('Entities.form', ['type' => $type, 'id' => $id]); // $id present → renders Edit mode
    })->whereIn('type', ['user', 'procuring_entity', 'supplier'])->name('entities.edit');

    // Entity profile page — one view rendered three ways based on {type}.
    // @TODO: once models + migrations exist, pass the real $user/$entity/$supplier in:
    // Route::get('/entities/procuring-entities/{id}', [EntityController::class, 'show'])->name('entities.show');
    Route::get('/entities/{type}/{id}', function ($type, $id) {
        return view('Entities.show', ['type' => $type, 'id' => $id]); // show.blade.php uses hardcoded dummy data
    })->whereIn('type', ['user', 'procuring_entity', 'supplier'])->name('entities.show');
    Route::get('/entities', [PageController::class, 'show'])->defaults('page', 'Entities.index')->name('entities');
    Route::get('/priceindex', [PageController::class, 'show'])->defaults('page', 'Priceindex.index')->name('priceindex');
    Route::get('/settings', [PageController::class, 'show'])->defaults('page', 'Settings.index')->name('settings');
});