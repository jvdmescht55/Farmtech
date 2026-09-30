<?php

use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\SourceController as AdminSourceController;
use App\Http\Controllers\Admin\SupplierOutreachController as AdminSupplierOutreachController;
use App\Http\Controllers\Admin\SupplierOutreachDashboardController as AdminSupplierOutreachDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\CompareController;
use App\Http\Controllers\Storefront\EquipmentController;
use App\Http\Controllers\Storefront\FinderController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\PaymentWebhookController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\AboutController;
use App\Http\Controllers\Storefront\HowItWorksController;
use App\Http\Controllers\Storefront\PolicyController;
use App\Http\Controllers\Storefront\SearchController;
use App\Http\Controllers\Storefront\SupportController;
use App\Http\Controllers\Storefront\TrackOrderController;
use App\Http\Controllers\Admin\InsightsController as AdminInsightsController;
use App\Http\Controllers\Admin\LicenseController as AdminLicenseController;
use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\HerdHubController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\Rfid\AnimalController as RfidAnimalController;
use App\Http\Controllers\Rfid\CatalogueController as RfidCatalogueController;
use App\Http\Controllers\Rfid\DashboardController as RfidDashboardController;
use App\Http\Controllers\Rfid\ImportController as RfidImportController;
use App\Http\Controllers\Rfid\ReaderController as RfidReaderController;
use App\Http\Controllers\Rfid\SettingsController as RfidSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public: portal (Store or Herd Management) + herd customer accounts
|--------------------------------------------------------------------------
*/

Route::get('/', [PortalController::class, 'index'])->name('portal');
Route::get('/herd', [CustomerAuthController::class, 'landing'])->name('landing');
Route::middleware('guest')->group(function () {
    Route::get('/login', [CustomerAuthController::class, 'landing'])->name('login');
    Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:10,1');
});
Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/activate', [CustomerAuthController::class, 'showActivate'])->name('account.activate');
    Route::post('/activate', [CustomerAuthController::class, 'activate'])->middleware('throttle:10,1');
});

/*
|--------------------------------------------------------------------------
| Herd Management — a hub of software modules, each unlocked by the
| licence that ships with its device. Only RFID Scanner V1 exists today.
|--------------------------------------------------------------------------
*/

Route::get('/app', [HerdHubController::class, 'index'])->middleware('auth')->name('herd.hub');

Route::prefix('app/rfid-v1')->name('rfid.')->middleware(['auth', 'module:rfid'])->group(function () {
    Route::get('/', [RfidDashboardController::class, 'index'])->name('dashboard');

    Route::get('/animals', [RfidAnimalController::class, 'index'])->name('animals.index');
    Route::get('/animals/create', [RfidAnimalController::class, 'create'])->name('animals.create');
    Route::post('/animals', [RfidAnimalController::class, 'store'])->name('animals.store');
    Route::get('/animals/{animal}', [RfidAnimalController::class, 'show'])->name('animals.show');
    Route::get('/animals/{animal}/edit', [RfidAnimalController::class, 'edit'])->name('animals.edit');
    Route::put('/animals/{animal}', [RfidAnimalController::class, 'update'])->name('animals.update');
    Route::post('/animals/{animal}/weights', [RfidAnimalController::class, 'addWeight'])->name('animals.weights.store');

    Route::get('/readers', [RfidReaderController::class, 'index'])->name('readers.index');
    Route::post('/readers', [RfidReaderController::class, 'store'])->name('readers.store');
    Route::post('/readers/{reader}/token', [RfidReaderController::class, 'regenerateToken'])->name('readers.token');
    Route::delete('/readers/{reader}', [RfidReaderController::class, 'destroy'])->name('readers.destroy');
    Route::post('/sync/upload', [RfidReaderController::class, 'upload'])->name('sync.upload');
    Route::get('/sync/{sync}', [RfidReaderController::class, 'showSync'])->name('sync.show');

    Route::get('/import', [RfidImportController::class, 'create'])->name('import.create');
    Route::post('/import', [RfidImportController::class, 'store'])->name('import.store');
    Route::get('/import/template', [RfidImportController::class, 'template'])->name('import.template');

    Route::get('/catalogues', [RfidCatalogueController::class, 'index'])->name('catalogues.index');
    Route::post('/catalogues', [RfidCatalogueController::class, 'store'])->name('catalogues.store');
    Route::get('/catalogues/{catalogue}', [RfidCatalogueController::class, 'show'])->name('catalogues.show');
    Route::put('/catalogues/{catalogue}', [RfidCatalogueController::class, 'update'])->name('catalogues.update');
    Route::delete('/catalogues/{catalogue}', [RfidCatalogueController::class, 'destroy'])->name('catalogues.destroy');
    Route::post('/catalogues/{catalogue}/lots', [RfidCatalogueController::class, 'addLots'])->name('catalogues.lots.add');
    Route::put('/catalogues/{catalogue}/lots', [RfidCatalogueController::class, 'updateLots'])->name('catalogues.lots.update');
    Route::post('/catalogues/{catalogue}/number', [RfidCatalogueController::class, 'autoNumber'])->name('catalogues.number');
    Route::delete('/catalogues/{catalogue}/lots/{lot}', [RfidCatalogueController::class, 'removeLot'])->name('catalogues.lots.remove');
    Route::get('/catalogues/{catalogue}/print', [RfidCatalogueController::class, 'print'])->name('catalogues.print');
    Route::get('/catalogues/{catalogue}/export', [RfidCatalogueController::class, 'export'])->name('catalogues.export');

    Route::get('/settings', [RfidSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [RfidSettingsController::class, 'update'])->name('settings.update');
});

/*
|--------------------------------------------------------------------------
| Store (public). Home is /store because / is the portal.
|--------------------------------------------------------------------------
*/

Route::group([], function () {
    Route::get('/store', [HomeController::class, 'index'])->name('home');
    Route::get('/search', [SearchController::class, 'index'])->name('search.index')->middleware('throttle:search');
    Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest')->middleware('throttle:search');
    Route::get('/category/{category}', [ProductController::class, 'category'])->name('category.show');
    Route::get('/industry/{industry}', [ProductController::class, 'industry'])->name('industry.show');

    // Short "Domain hub" URLs for the 4 industries — same real controller/view as
    // /industry/{industry}, just a friendlier path. The industry route parameter
    // keeps its stable backing value (agriculture/construction/etc.); only the
    // display label and this URL alias changed for the new domain naming.
    foreach (\App\Enums\Industry::cases() as $domainIndustry) {
        Route::get('/'.$domainIndustry->domainSlug(), [ProductController::class, 'industry'])
            ->defaults('industry', $domainIndustry->value)
            ->name('domain.'.$domainIndustry->domainSlug());
    }
    Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/compare', [CompareController::class, 'data'])->name('compare.data')->middleware('throttle:search');
    Route::get('/equipment', [EquipmentController::class, 'index'])->name('equipment.index');
    Route::get('/finder', [FinderController::class, 'index'])->name('finder.index');

    Route::middleware('throttle:cart')->group(function () {
        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::get('/cart/summary', [CartController::class, 'summary'])->name('cart.summary');
        Route::post('/cart/{product}/add', [CartController::class, 'add'])->name('cart.add');
        Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');

        Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
        Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    });

    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');

    Route::get('/how-it-works', [HowItWorksController::class, 'index'])->name('how-it-works');
    Route::get('/about', [AboutController::class, 'index'])->name('about.index');

    Route::get('/track', [TrackOrderController::class, 'index'])->name('track.index');
    Route::post('/track', [TrackOrderController::class, 'show'])->name('track.show')->middleware('throttle:track');

    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
    Route::post('/support', [SupportController::class, 'store'])->name('support.store')->middleware('throttle:support');

    Route::get('/policies/shipping', [PolicyController::class, 'shipping'])->name('policies.shipping');
    Route::get('/policies/returns', [PolicyController::class, 'returns'])->name('policies.returns');
    Route::get('/policies/terms', [PolicyController::class, 'terms'])->name('policies.terms');
    Route::get('/policies/icasa-compliance', [PolicyController::class, 'icasaCompliance'])->name('policies.icasa');
    Route::get('/policies/privacy', [PolicyController::class, 'privacy'])->name('policies.privacy');
});

Route::post('/webhooks/payfast', [PaymentWebhookController::class, 'handle'])
    ->defaults('gateway', 'payfast')->name('checkout.webhook.payfast');
Route::post('/webhooks/ozow', [PaymentWebhookController::class, 'handle'])
    ->defaults('gateway', 'ozow')->name('checkout.webhook.ozow');
Route::post('/webhooks/yoco', [PaymentWebhookController::class, 'handle'])
    ->defaults('gateway', 'yoco')->name('checkout.webhook.yoco');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'create'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'store']);
    Route::post('/logout', [AdminLoginController::class, 'destroy'])->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        // Orders + own Profile: both Admin and Staff — the only two
        // sections Staff has, per the EnsureUserIsAdmin panel gate.
        Route::redirect('/', '/admin/orders');

        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
        Route::post('/orders/{order}/notes', [AdminOrderController::class, 'addNote'])->name('orders.notes.store');

        Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password');

        // Admin-only from here down — Staff gets a 403 (see the
        // manage-catalog/manage-settings/manage-users Gates in AppServiceProvider).
        Route::middleware('can:manage-catalog')->group(function () {
            Route::get('/source', [AdminSourceController::class, 'create'])->name('source.create');
            Route::post('/source', [AdminSourceController::class, 'store'])->name('source.store');

            Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
            Route::get('/products/live', [AdminProductController::class, 'live'])->name('products.live');
            Route::get('/products/{product}', [AdminProductController::class, 'show'])->name('products.show');
            Route::match(['post', 'patch', 'put'], '/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
            Route::post('/products/{product}/approve', [AdminProductController::class, 'approve'])->name('products.approve');
            Route::post('/products/{product}/reject', [AdminProductController::class, 'reject'])->name('products.reject');
            Route::post('/products/{product}/archive', [AdminProductController::class, 'archive'])->name('products.archive');
            Route::post('/products/{product}/relist', [AdminProductController::class, 'relist'])->name('products.relist');

            Route::get('/suppliers/outreach', [AdminSupplierOutreachController::class, 'index'])->name('suppliers.outreach');

            Route::get('/outreach', [AdminSupplierOutreachDashboardController::class, 'index'])->name('outreach.index');
            Route::patch('/outreach/{product}', [AdminSupplierOutreachDashboardController::class, 'update'])->name('outreach.update');
        });

        Route::middleware('can:manage-users')->group(function () {
            Route::get('/licenses', [AdminLicenseController::class, 'index'])->name('licenses.index');
            Route::post('/licenses', [AdminLicenseController::class, 'store'])->name('licenses.store');
            Route::post('/licenses/{license}/revoke', [AdminLicenseController::class, 'revoke'])->name('licenses.revoke');
            Route::post('/licenses/{license}/restore', [AdminLicenseController::class, 'restore'])->name('licenses.restore');

            Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
            Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
            Route::post('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('users.toggle-active');
        });

        Route::middleware('can:manage-settings')->group(function () {
            Route::get('/settings', [AdminSettingsController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
        });

        Route::middleware('can:view-financials')->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
            Route::get('/insights', [AdminInsightsController::class, 'index'])->name('insights');
        });
    });
});
