<?php

use App\Http\Controllers;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::redirect('/', 'dashboard'); test ploi notify

/**
 * 外部服務整合路由
 */
Route::group([
    //
], function () {
    // OAuth2 服務
    Route::get('/oauth2/redirect/line', [Controllers\Auth\AuthLineController::class, 'redirect']);
    Route::get('/oauth2/callback/line', [Controllers\Auth\AuthLineController::class, 'callback']);
});

/**
 * 付款服務整合路由
 */
Route::withoutMiddleware([
    Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
])->group(function () {
    Route::get('/t/{shortCode}', Controllers\AdminTransactionController::class);

    Route::post('/webhook/gomypay', Controllers\Gateway\GatewayGomypayController::class)->name('webhook.gomypay');

    Route::post('/webhook/ccat', Controllers\Gateway\GatewayCatPayController::class)->name('webhook.ccat');

    Route::get('/success/ccat', [Controllers\Gateway\GatewayCatPayController::class, 'success'])->name('success.ccat');
});


// Inertia 路由
Route::middleware([
    App\Http\Middleware\HandleInertiaRequests::class,
])->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');

        Route::post('login', [Controllers\Auth\AuthenticatedSessionController::class, 'store']);
    });

    Route::middleware('auth')->group(function () {
        Route::get('dashboard', Controllers\AdminDashboardController::class)->name('dashboard');

        Route::resource('users', Controllers\UserController::class)->except('show');

        Route::post('logout', [Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
    });
});

// Inertia 路由
Route::middleware([
    App\Http\Middleware\HandleInertiaRequests::class,
    'auth',
])->group(function () {
    // 報價管理
    Route::resource('/orders', Controllers\AdminOrderController::class)->except('show');
    Route::put('/orders/{id}/status', [Controllers\AdminOrderController::class, 'status']);
    Route::post('/orders/{id}/transactions', [Controllers\AdminOrderController::class, 'transactions']);
    Route::post('/orders/{id}/fulfillments', [Controllers\AdminOrderController::class, 'fulfillments']);
    Route::get('/orders/{id}/slip', [Controllers\AdminOrderController::class, 'slip']);
    Route::post('/orders/{id}/purchase', [Controllers\AdminOrderController::class, 'purchase']);
    Route::get('/orders/{id}/preview', [Controllers\AdminOrderController::class, 'preview']);
    Route::get('/orders/export', [Controllers\AdminOrderController::class, 'export']);

    // 商品管理
    Route::resource('/products', Controllers\AdminProductController::class)->except('show');
    Route::post('/products/positions', [Controllers\AdminProductController::class, 'positions']);
    Route::get('/products/export', [Controllers\AdminProductController::class, 'export']);
    Route::post('/products/import', [Controllers\AdminProductController::class, 'import']);

    // 庫存管理
    Route::resource('/inventories', Controllers\AdminInventoryController::class)->only('index', 'update');
    Route::get('/inventories/logs', [Controllers\AdminInventoryController::class, 'logs']);
    Route::get('/inventories/export', [Controllers\AdminInventoryController::class, 'export']);
    Route::post('/inventories/import', [Controllers\AdminInventoryController::class, 'import']);

    // 進/退貨管理
    Route::resource('/purchase-orders', Controllers\AdminPurchaseOrderController::class)->except('show');
    Route::put('/purchase-orders/{id}/status', [Controllers\AdminPurchaseOrderController::class, 'status']);
    Route::get('/purchase-orders/{id}/print', [Controllers\AdminPurchaseOrderController::class, 'print']);
    Route::get('/purchase-orders/{id}/print-without-amounts', [Controllers\AdminPurchaseOrderController::class, 'printWithoutAmounts']);
    Route::get('/purchase-orders/export', [Controllers\AdminPurchaseOrderController::class, 'export']);

    // 調撥管理
    Route::resource('/transfer-orders', Controllers\AdminTransferOrderController::class)->except('show');
    Route::put('/transfer-orders/{id}/status', [Controllers\AdminTransferOrderController::class, 'status']);

    // 盤點管理
    Route::resource('/adjustment-orders', Controllers\AdminAdjustmentOrderController::class)->except('show');
    Route::put('/adjustment-orders/{id}/status', [Controllers\AdminAdjustmentOrderController::class, 'status']);
    Route::put('/adjustment-orders/{id}/corrected', [Controllers\AdminAdjustmentOrderController::class, 'corrected']);

    // 客戶管理
    Route::resource('/customers', Controllers\AdminCustomerController::class)->except('show');

    // 地址管理
    Route::resource('/locations', Controllers\AdminLocationController::class)->except('show');

    // 通知設定
    Route::resource('/channels', Controllers\AdminChannelController::class)->only('index', 'store', 'destroy');

    // 分析報表
    Route::get('/analytics', Controllers\AdminAnalyticsController::class)->name('analytics.index');

    // 分析報表明細：模組化結構
    Route::prefix('/analytics')->group(function () {
        // 訂單分析
        Route::get('/orders', [Controllers\AdminOrderAnalyticsController::class, 'index']);

        // 商品分析
        Route::get('/products', [Controllers\AdminProductAnalyticsController::class, 'index']);

        // 庫存分析
        Route::get('/inventory', [Controllers\AdminInventoryAnalyticsController::class, 'index']);

        // 銷售員分析
        Route::get('/sales', [Controllers\AdminSalesAnalyticsController::class, 'index']);
        Route::get('/sales/export', [Controllers\AdminSalesAnalyticsController::class, 'export']);
    });

    // 設定
    Route::inertia('/settings', 'Setting/Index');

    /**
     * 試算頁面
     */
    Route::inertia('/installments', 'Installment/Index');

    // Majipay 分期 (手機)
    Route::get('/installments/majipay-m', [Controllers\AdminTrialCalculationController::class, 'majipayM'])->name('installments.m-majipay');

    // Majipay 分期
    Route::get('/installments/majipay', [Controllers\AdminTrialCalculationController::class, 'majipay'])->name('installments.majipay');

    // Zingala 分期
    Route::get('/installments/zingala', [Controllers\AdminTrialCalculationController::class, 'zingala'])->name('installments.zingala');

    // Gomypay 分期
    Route::get('/installments/gomypay', [Controllers\AdminTrialCalculationController::class, 'gomypay'])->name('installments.gomypay');

    // BOBOPAY 分期
    Route::get('/installments/bobopay', [Controllers\AdminTrialCalculationController::class, 'bobopay'])->name('installments.bobopay');
});
