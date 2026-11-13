<?php

/**
 * Web routes.
 *
 * Routes outside the `auth` middleware group are public; routes inside
 * the group require the user to be logged in. Module routes additionally
 * enforce the role-based access matrix (see App\Support\ModuleAccess):
 * read requests need the View level; writes need Manage. Settings is
 * administrator-only via the access-settings gate.
 */

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PreplistController;
use App\Http\Controllers\PriceIndexController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\QuotationController;
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
Route::middleware(['auth', 'session.active'])->group(function () {
    Route::middleware('module:projects')->group(function () {
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects');

        // /projects/create must stay before /projects/{project} so "create" is
        // matched as a literal path instead of being captured as a project id.
        Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');

        Route::get('/projects/{project}', [ProjectController::class, 'show'])
            ->whereNumber('project')
            ->name('projects.show');
        Route::get('/projects/{project}/download', [ProjectController::class, 'download'])
            ->whereNumber('project')
            ->name('projects.download');
        Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])
            ->whereNumber('project')
            ->name('projects.edit');
        Route::put('/projects/{project}', [ProjectController::class, 'update'])
            ->whereNumber('project')
            ->name('projects.update');
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])
            ->whereNumber('project')
            ->name('projects.destroy');
    });

    Route::middleware('module:preplist')->group(function () {
        Route::get('/preplist', [PreplistController::class, 'index'])->name('preplist');

        // /preplist/create must be declared BEFORE /preplist/{preplist} so "create"
        // is matched as a literal path, not captured by the {preplist} wildcard.
        Route::get('/preplist/create', [PreplistController::class, 'create'])->name('preplist.create');
        Route::post('/preplist', [PreplistController::class, 'store'])->name('preplist.store');

        Route::get('/preplist/{preplist}', [PreplistController::class, 'show'])
            ->whereNumber('preplist')
            ->name('preplist.show');
        Route::get('/preplist/{preplist}/edit', [PreplistController::class, 'edit'])
            ->whereNumber('preplist')
            ->name('preplist.edit');
        Route::put('/preplist/{preplist}', [PreplistController::class, 'update'])
            ->whereNumber('preplist')
            ->name('preplist.update');
        Route::patch('/preplist/{preplist}/notes', [PreplistController::class, 'updateNotes'])
            ->whereNumber('preplist')
            ->name('preplist.notes');
        Route::delete('/preplist/{preplist}', [PreplistController::class, 'destroy'])
            ->whereNumber('preplist')
            ->name('preplist.destroy');
        Route::get('/preplist/{preplist}/generate-rfq', [PreplistController::class, 'generateRfq'])
            ->whereNumber('preplist')
            ->name('preplist.generate-rfq');
        Route::post('/preplist/{preplist}/generate-rfq', [PreplistController::class, 'generateRfqExcel'])
            ->whereNumber('preplist')
            ->name('preplist.generate-rfq.excel');
    });

    Route::middleware('module:quotation')->group(function () {
        Route::get('/quotation', [QuotationController::class, 'index'])->name('quotation');

        Route::get('/quotation/{quotation}', [QuotationController::class, 'show'])
            ->whereNumber('quotation')
            ->name('quotation.show');
        Route::post('/quotation/{quotation}/document', [QuotationController::class, 'storeDocument'])
            ->whereNumber('quotation')
            ->name('quotation.store-document');
        Route::get('/quotation/{quotation}/download', [QuotationController::class, 'download'])
            ->whereNumber('quotation')
            ->name('quotation.download');
        Route::delete('/quotation/{quotation}/document', [QuotationController::class, 'destroyDocument'])
            ->whereNumber('quotation')
            ->name('quotation.destroy-document');
        Route::delete('/quotation/{quotation}', [QuotationController::class, 'destroy'])
            ->whereNumber('quotation')
            ->name('quotation.destroy');
    });

    Route::middleware('module:priceindex')->group(function () {
        Route::get('/priceindex', [PriceIndexController::class, 'index'])->name('priceindex');
    });

    Route::middleware('module:entities')->group(function () {
        // Entity profile page — one view rendered two ways based on {type}.
        Route::get('/entities/{type}/{id}', [EntityController::class, 'show'])->whereIn('type', ['procuring_entity', 'supplier'])->whereNumber('id')->name('entities.show');
        Route::get('/entities', [EntityController::class, 'index'])->name('entities');

        // Add / Edit entity form — one view rendered two ways based on {type}.
        // NOTE: /entities/{type}/create must be declared BEFORE /entities/{type}/{id}
        // so "create" is matched as a literal path, not captured by the {id} wildcard.
        Route::get('/entities/{type}/create', [EntityController::class, 'create'])->whereIn('type', ['procuring_entity', 'supplier'])->name('entities.create');

        Route::get('/entities/{type}/{id}/edit', [EntityController::class, 'edit'])->whereIn('type', ['procuring_entity', 'supplier'])->whereNumber('id')->name('entities.edit');

        Route::post('/entities/{type}/store', [EntityController::class, 'store'])->whereIn('type', ['procuring_entity', 'supplier'])->name('entities.store');

        Route::put('/entities/{type}/{id}', [EntityController::class, 'update'])->whereIn('type', ['procuring_entity', 'supplier'])->whereNumber('id')->name('entities.update');

        Route::delete('/entities/{type}/{id}', [EntityController::class, 'destroy'])->whereIn('type', ['procuring_entity', 'supplier'])->whereNumber('id')->name('entities.destroy');
    });

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
        Route::put('/settings/privileges/{id}', [SettingsController::class, 'privilegeUpdate'])->name('settings.privileges.update');
        Route::post('/settings/privileges/{id}/toggle-status', [SettingsController::class, 'privilegeToggleStatus'])->name('settings.privileges.toggle-status');
        Route::delete('/settings/privileges/{id}', [SettingsController::class, 'privilegeDestroy'])->name('settings.privileges.destroy');
        Route::get('/settings/privileges/{id}/edit', [SettingsController::class, 'privilegeEdit'])->name('settings.privileges.edit');
        Route::post('/settings/privileges/access', [SettingsController::class, 'updateModuleAccess'])->name('settings.privileges.access.update');

        Route::get('/settings/backup', [SettingsController::class, 'backup'])->name('settings.backup');
        Route::post('/settings/backup', [SettingsController::class, 'storeBackup'])->name('settings.backup.store');
        Route::get('/settings/backup/download/{file}', [SettingsController::class, 'downloadBackup'])->name('settings.backup.download');
        Route::delete('/settings/backup/{file}', [SettingsController::class, 'destroyBackup'])->name('settings.backup.destroy');

        // Dummy configuration page (front end only; back-end TODOs live in the view).
        Route::get('/settings/configuration', [PageController::class, 'show'])->defaults('page', 'Settings.configuration')->name('settings.configuration');
    });

    Route::middleware('module:purchase-order')->group(function () {
        // Purchase orders generated from awarded quotations.
        Route::get('/purchase-order', [PurchaseOrderController::class, 'index'])->name('purchase-order');

        // Purchase order preparation screen, opened by the quotation page's
        // "Proceed to PO" button: pick the quoted items to order and set the
        // purchase quantity before the PO is created.
        // Declared before /purchase-order/{purchaseOrder} so "create" is never
        // captured as an id.
        Route::get('/purchase-order/create/{rfq}', [PurchaseOrderController::class, 'create'])
            ->whereNumber('rfq')
            ->name('purchase-order.create');
        Route::post('/purchase-order', [PurchaseOrderController::class, 'store'])
            ->name('purchase-order.store');

        // Purchase order detail.
        Route::get('/purchase-order/{purchaseOrder}', [PurchaseOrderController::class, 'show'])
            ->whereNumber('purchaseOrder')
            ->name('purchase-order.show');
        Route::post('/purchase-order/{purchaseOrder}/send', [PurchaseOrderController::class, 'send'])
            ->whereNumber('purchaseOrder')
            ->name('purchase-order.send');
        Route::post('/purchase-order/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])
            ->whereNumber('purchaseOrder')
            ->name('purchase-order.cancel');
        Route::delete('/purchase-order/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])
            ->whereNumber('purchaseOrder')
            ->name('purchase-order.destroy');
        Route::post('/purchase-order/{purchaseOrder}/items/{purchaseOrderItem}/toggle-receipt', [PurchaseOrderController::class, 'toggleReceipt'])
            ->whereNumber('purchaseOrder')
            ->whereNumber('purchaseOrderItem')
            ->name('purchase-order.receive');
    });
});
