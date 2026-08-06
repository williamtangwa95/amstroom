<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DefectController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MainStockController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ShopStockController;
use App\Http\Controllers\StockLogController;
use App\Http\Controllers\StockRequestController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

// ─── GUEST ROUTES ────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('home');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    // Password Recovery Routes
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetCode'])->name('password.email');
    Route::get('/reset-password/verify', [ForgotPasswordController::class, 'showVerifyForm'])->name('password.verify-form');
    Route::post('/reset-password/verify', [ForgotPasswordController::class, 'verifyCode'])->name('password.verify');
    Route::get('/reset-password/new', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset-form');
    Route::post('/reset-password/new', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');
});

// ─── AUTHENTICATED ROUTES ─────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // ─── OWNER ONLY ──────────────────────────────────────────────────────────
    Route::middleware('role:owner')->group(function () {

        // Shops
        Route::resource('shops', ShopController::class);
        Route::post('shops/{shop}/assign-employee', [ShopController::class, 'assignEmployee'])->name('shops.assign-employee');

        // Categories
        Route::resource('categories', CategoryController::class);

        // Items
        Route::resource('items', ItemController::class);

        // Main Store
        Route::get('main-stock', [MainStockController::class, 'index'])->name('main-stock.index');
        Route::get('main-stock/create', [MainStockController::class, 'create'])->name('main-stock.create');
        Route::post('main-stock', [MainStockController::class, 'store'])->name('main-stock.store');
        Route::get('main-stock/history', [MainStockController::class, 'history'])->name('main-stock.history');
        Route::get('main-stock/{mainStock}', [MainStockController::class, 'show'])->name('main-stock.show');
        Route::get('main-stock/{mainStock}/edit', [MainStockController::class, 'edit'])->name('main-stock.edit');
        Route::put('main-stock/{mainStock}', [MainStockController::class, 'update'])->name('main-stock.update');

        // Stock Request Approval (owner only)
        Route::post('stock-requests/{stockRequest}/approve', [StockRequestController::class, 'approve'])->name('stock-requests.approve');
        Route::post('stock-requests/{stockRequest}/reject', [StockRequestController::class, 'reject'])->name('stock-requests.reject');
        Route::put('stock-requests/item/{stockRequestItem}', [StockRequestController::class, 'updateItem'])->name('stock-requests.update-item');

        // Direct Stock Assignment to Shop (owner only)
        Route::get('stock-transfers/create', [StockTransferController::class, 'create'])->name('stock-transfers.create');
        Route::post('stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store');

        // Users
        Route::resource('users', UserController::class);



        // Audit Logs
        Route::get('stock-logs', [StockLogController::class, 'index'])->name('stock-logs.index');

        // Owner: update defect status
        Route::patch('defects/{defect}/status', [DefectController::class, 'updateStatus'])->name('defects.update-status');

    });

    // ─── OWNER + SHOP ADMIN ──────────────────────────────────────────────────
    Route::middleware('role:owner,shop_admin')->group(function () {

        // Stock Requests
        Route::get('stock-requests', [StockRequestController::class, 'index'])->name('stock-requests.index');
        Route::get('stock-requests/create', [StockRequestController::class, 'create'])->name('stock-requests.create');
        Route::post('stock-requests', [StockRequestController::class, 'store'])->name('stock-requests.store');
        Route::get('stock-requests/{stockRequest}', [StockRequestController::class, 'show'])->name('stock-requests.show');

        // Stock Transfers (view for owner + admin)
        Route::get('stock-transfers', [StockTransferController::class, 'index'])->name('stock-transfers.index');
        Route::get('stock-transfers/{stockTransfer}', [StockTransferController::class, 'show'])->name('stock-transfers.show');

        // Transfer item actions
        Route::post('stock-transfers/item/{transferItem}/approve', [StockTransferController::class, 'approveItem'])->name('stock-transfers.approve-item');
        Route::post('stock-transfers/item/{transferItem}/reject', [StockTransferController::class, 'rejectItem'])->name('stock-transfers.reject-item');
        Route::post('stock-transfers/{stockTransfer}/approve-bulk', [StockTransferController::class, 'approveBulk'])->name('stock-transfers.approve-bulk');

        // Owner: modify transfer items
        Route::put('stock-transfers/item/{transferItem}', [StockTransferController::class, 'updateItem'])->name('stock-transfers.update-item');
        Route::delete('stock-transfers/item/{transferItem}', [StockTransferController::class, 'deleteItem'])->name('stock-transfers.delete-item');
        Route::post('stock-transfers/{stockTransfer}/add-item', [StockTransferController::class, 'addItem'])->name('stock-transfers.add-item');

        // Shop Admin: Approve/Reject/Revert sale returns
        Route::post('sales-returns/{saleReturn}/approve', [SaleReturnController::class, 'approve'])->name('sales-returns.approve');
        Route::post('sales-returns/{saleReturn}/reject', [SaleReturnController::class, 'reject'])->name('sales-returns.reject');
        Route::delete('sales-returns/bulk', [SaleReturnController::class, 'bulkDestroy'])->name('sales-returns.bulk-destroy');
        Route::delete('sales-returns/{saleReturn}', [SaleReturnController::class, 'destroy'])->name('sales-returns.destroy');

        // Shop stock price update
        Route::patch('shop-stock/{shopStock}/price', [ShopStockController::class, 'updatePrice'])->name('shop-stock.update-price');
        Route::post('shop-stock/admin-stock', [ShopStockController::class, 'storeAdminStock'])->name('shop-stock.store-admin-stock');
    });

    // ─── ALL AUTHENTICATED (OWNER + ADMIN + SELLER) ──────────────────────────
    Route::middleware('role:owner,shop_admin,seller')->group(function () {

        // Sales
        Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('sales/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('sales', [SaleController::class, 'store'])->name('sales.store');
        Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show')->withTrashed();
        Route::get('sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt')->withTrashed();
        Route::get('sales/{sale}/invoice', [SaleController::class, 'invoice'])->name('sales.invoice');
        Route::get('sales/{sale}/proforma', [SaleController::class, 'proforma'])->name('sales.proforma');
        Route::get('sales/{sale}/delivery-note', [SaleController::class, 'deliveryNote'])->name('sales.delivery-note');
        Route::post('sales/{sale}/convert', [SaleController::class, 'convertToSale'])->name('sales.convert');

        // Sale Returns (all roles can view and request returns)
        Route::get('sales-returns', [SaleReturnController::class, 'index'])->name('sales-returns.index');
        Route::get('sales/{sale}/return/create', [SaleReturnController::class, 'create'])->name('sales-returns.create')->withTrashed();
        Route::post('sales/{sale}/return', [SaleReturnController::class, 'store'])->name('sales-returns.store')->withTrashed();

        // Defects (all roles can view/report)
        Route::get('defects', [DefectController::class, 'index'])->name('defects.index');
        Route::get('defects/create', [DefectController::class, 'create'])->name('defects.create');
        Route::post('defects', [DefectController::class, 'store'])->name('defects.store');

        // Item Image Upload
        Route::post('items/{item}/upload-image', [ItemController::class, 'uploadImage'])->name('items.upload-image');

        // Shop Stock (view + alert threshold)
        Route::get('shop-stock', [ShopStockController::class, 'index'])->name('shop-stock.index');
        Route::get('shop-stock/{shopStock}', [ShopStockController::class, 'show'])->name('shop-stock.show');
        Route::patch('shop-stock/{shopStock}/alert', [ShopStockController::class, 'updateAlert'])->name('shop-stock.update-alert');

        // Branding & Settings (all roles can access with customized permissions)
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('settings/send-summary', [SettingController::class, 'sendSummaryEmail'])->name('settings.send-summary');
        Route::delete('settings/logo', [SettingController::class, 'removeLogo'])->name('settings.remove-logo');
        Route::delete('settings/ringtone', [SettingController::class, 'removeRingtone'])->name('settings.remove-ringtone');

        // Notifications
        Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/poll', [\App\Http\Controllers\NotificationController::class, 'poll'])->name('notifications.poll');
        Route::post('notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::get('notifications/{notification}/go', [\App\Http\Controllers\NotificationController::class, 'readAndRedirect'])->name('notifications.go');
        Route::post('notifications/clear', [\App\Http\Controllers\NotificationController::class, 'clearAll'])->name('notifications.clear');

        // Live Chat Routes
        Route::prefix('chats')->name('chats.')->group(function () {
            Route::get('/', [ChatController::class, 'index'])->name('index');
            Route::get('/messages', [ChatController::class, 'fetchMessages'])->name('messages');
            Route::post('/messages', [ChatController::class, 'sendMessage'])->name('send');
            Route::post('/messages/bulk', [ChatController::class, 'sendBulkMessage'])->name('send-bulk');
            Route::get('/items/search', [ChatController::class, 'searchItems'])->name('items.search');
            Route::post('/inquire', [ChatController::class, 'inquireProduct'])->name('inquire');
            Route::post('/send-sms', [ChatController::class, 'sendSMS'])->name('send-sms');
            Route::get('/unread-badge', [ChatController::class, 'getUnreadBadge'])->name('unread-badge');
        });

        // Expenses
        Route::post('expenses/bulk-approve', [ExpenseController::class, 'bulkApprove'])->name('expenses.bulk-approve');
        Route::resource('expenses', ExpenseController::class);
        Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');
        Route::post('expenses/{expense}/request-review', [ExpenseController::class, 'requestReview'])->name('expenses.request-review');
        Route::post('expenses/{expense}/grant-edit', [ExpenseController::class, 'grantEdit'])->name('expenses.grant-edit');
        Route::post('expenses/{expense}/revert-approval', [ExpenseController::class, 'revertApproval'])->name('expenses.revert-approval');
    });

    // ─── OWNER + ADMIN ONLY ──────────────────────────────────────────────────
    Route::middleware('role:owner,shop_admin')->group(function () {
        Route::resource('expense-categories', ExpenseCategoryController::class)->except(['show', 'edit']);
        Route::post('shop-stock/{shopStock}/approve-price', [ShopStockController::class, 'approvePrice'])->name('shop-stock.approve-price');

        // Reports
        Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('reports/stock', [ReportController::class, 'stock'])->name('reports.stock');
        Route::get('reports/transfer', [ReportController::class, 'transfer'])->name('reports.transfer');
        Route::get('reports/defect', [ReportController::class, 'defect'])->name('reports.defect');
        Route::get('reports/expenses', [ReportController::class, 'expenses'])->name('reports.expenses');
        Route::get('reports/sales-vs-expenses', [ReportController::class, 'salesVsExpenses'])->name('reports.sales-vs-expenses');
    });
});
