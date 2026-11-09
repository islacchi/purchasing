<?php

/**
 * Web routes.
 *
 * Routes outside the `auth` middleware group are public; routes inside
 * the group require the user to be logged in.
 *
 * NOTE: Several show/edit routes currently use inline closures with dummy
 * data because the underlying models/migrations don't exist yet. See the
 * inline @TODO comments for the intended controller replacements.
 */

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

// Redirect the root path to the login page.
Route::get('/', function () {
    return redirect('/login');
});

// Public authentication routes.
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated routes — these require a valid authenticated session.
Route::middleware('auth')->group(function () {
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
    // Entity profile page — one view rendered two ways based on {type}.
    Route::get('/entities/{type}/{id}', [EntityController::class, 'show'])->whereIn('type', ['procuring_entity', 'supplier'])->whereNumber('id')->name('entities.show');
    Route::get('/entities', [EntityController::class, 'index'])->name('entities');
    Route::get('/priceindex', [PageController::class, 'show'])->defaults('page', 'Priceindex.index')->name('priceindex');

    // Add / Edit entity form — one view rendered two ways based on {type}.
    // NOTE: /entities/{type}/create must be declared BEFORE /entities/{type}/{id}
    // so "create" is matched as a literal path, not captured by the {id} wildcard.
    Route::get('/entities/{type}/create', [EntityController::class, 'create'])->whereIn('type', ['procuring_entity', 'supplier'])->name('entities.create');

    Route::get('/entities/{type}/{id}/edit', [EntityController::class, 'edit'])->whereIn('type', ['procuring_entity', 'supplier'])->whereNumber('id')->name('entities.edit');

    Route::post('/entities/{type}/store', [EntityController::class, 'store'])->whereIn('type', ['procuring_entity', 'supplier'])->name('entities.store');

    Route::put('/entities/{type}/{id}', [EntityController::class, 'update'])->whereIn('type', ['procuring_entity', 'supplier'])->whereNumber('id')->name('entities.update');

    Route::delete('/entities/{type}/{id}', [EntityController::class, 'destroy'])->whereIn('type', ['procuring_entity', 'supplier'])->whereNumber('id')->name('entities.destroy');

    // Settings (administrator only)
    Route::middleware('can:access-settings')->group(function () {
        Route::get('/settings', [PageController::class, 'show'])->defaults('page', 'Settings.index')->name('settings');
        Route::get('/settings/logs', [SettingsController::class, 'logs'])->name('settings.logs');

        // User accounts + privileges (moved here from the Entities module).
        // NOTE: /settings/privileges/create must stay before the {id}/edit route.
        Route::get('/settings/privileges', [SettingsController::class, 'privileges'])->name('settings.privileges');
        Route::get('/settings/privileges/search', [SettingsController::class, 'privilegeSearch'])->name('settings.privileges.search');
        Route::get('/settings/privileges/create', [SettingsController::class, 'privilegeCreate'])->name('settings.privileges.create');
        Route::post('/settings/privileges', [SettingsController::class, 'privilegeStore'])->name('settings.privileges.store');
        Route::get('/settings/privileges/{id}/edit', [SettingsController::class, 'privilegeEdit'])->name('settings.privileges.edit');
        Route::put('/settings/privileges/{id}', [SettingsController::class, 'privilegeUpdate'])->name('settings.privileges.update');
        Route::post('/settings/privileges/{id}/toggle-status', [SettingsController::class, 'privilegeToggleStatus'])->name('settings.privileges.toggle-status');
        Route::delete('/settings/privileges/{id}', [SettingsController::class, 'privilegeDestroy'])->name('settings.privileges.destroy');

        Route::get('/settings/backup', [SettingsController::class, 'backup'])->name('settings.backup');
    });

    // Quotation detail (dummy data for now — the view uses $id to pick a hardcoded record).
    // @TODO: once the Quotation/RFQ models + migrations exist, pass the real quotation in:
    // Route::get('/quotation/{id}', [QuotationController::class, 'show'])->name('quotation.show');
    Route::get('/quotation/{id}', function ($id) {
        return view('Quotation.show', ['id' => $id]);
    })->name('quotation.show');

    // Purchase orders generated from awarded quotations.
    // @TODO: once the PurchaseOrder model + migrations exist, swap for:
    // Route::get('/purchase-order', [PurchaseOrderController::class, 'index'])->name('purchase-order');
    Route::get('/purchase-order', [PageController::class, 'show'])
        ->defaults('page', 'Purchaseorder.index')
        ->name('purchase-order');

    // Purchase order preparation screen, opened by the quotation page's
    // "Proceed to PO" button: pick the quoted items to order and set the
    // purchase quantity before the PO is created.
    // Declared before /purchase-order/{id} so "create" is never captured as an id.
    // @TODO: Route::get('/purchase-order/create/{rfq}', [PurchaseOrderController::class, 'create'])->name('purchase-order.create');
    Route::get('/purchase-order/create/{rfq}', function ($rfq) {
        return view('Purchaseorder.create', ['rfqId' => (int) $rfq]);
    })->name('purchase-order.create');

    // Purchase order detail (dummy data for now — the view uses $id to pick a hardcoded record).
    // @TODO: Route::get('/purchase-order/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-order.show');
    Route::get('/purchase-order/{id}', function ($id) {
        return view('Purchaseorder.show', ['id' => $id]);
    })->name('purchase-order.show');

});
