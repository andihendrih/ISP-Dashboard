<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\GenieacsController;
use App\Http\Controllers\HotspotController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MikrotikController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PppoeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServicePlanController;
use App\Http\Controllers\SnmpController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\Settings\UserController as SettingsUserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* Pelanggan */
    Route::middleware('role:admin,noc,finance')->group(function () {
        Route::get('/customers',           [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create',    [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers',          [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{id}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{id}',      [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{id}',   [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::post('/customers/{id}/regenerate-radius', [CustomerController::class, 'regenerateRadiusPassword'])->name('customers.regenerate-radius');

        /* Map view pelanggan (Leaflet + OpenStreetMap) */
        Route::get('/map',           [MapController::class, 'index'])->name('map.index');
        Route::get('/map/customers', [MapController::class, 'customers'])->name('map.customers');

        Route::get('/users-radius',          [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users-radius/{username}', [UserManagementController::class, 'show'])
            ->where('username', '.*')
            ->name('users.show');

        /* Billing — Invoices & Payments (admin + finance) */
        Route::get('/invoices',                 [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/create',          [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('/invoices',                [InvoiceController::class, 'store'])->name('invoices.store');
        Route::post('/invoices/generate-batch', [InvoiceController::class, 'generateBatch'])->name('invoices.generate-batch');
        Route::get('/invoices/{invoice}',       [InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');

        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::delete('/payments/{payment}',        [PaymentController::class, 'destroy'])->name('payments.destroy');

        /* Laporan */
        Route::get('/reports/financial',         [ReportController::class, 'financial'])->name('reports.financial');
        Route::get('/reports/financial/export',  [ReportController::class, 'exportCsv'])->name('reports.financial.export');
        Route::get('/reports/churn',             [ReportController::class, 'churn'])->name('reports.churn');

        /* Tiket Support */
        Route::get('/tickets',                   [SupportTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/create',            [SupportTicketController::class, 'create'])->name('tickets.create');
        Route::post('/tickets',                  [SupportTicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}',          [SupportTicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/comment', [SupportTicketController::class, 'comment'])->name('tickets.comment');
        Route::post('/tickets/{ticket}/status',  [SupportTicketController::class, 'updateStatus'])->name('tickets.status');
        Route::post('/tickets/{ticket}/assign',  [SupportTicketController::class, 'assign'])->name('tickets.assign');

        /* Notifikasi */
        Route::get('/notifications/settings',     [NotificationController::class, 'settings'])->name('notifications.settings');
        Route::post('/notifications/settings',    [NotificationController::class, 'saveSettings'])->name('notifications.settings.save');
        Route::post('/notifications/test',        [NotificationController::class, 'testSend'])->name('notifications.test');
        Route::get('/notifications/logs',         [NotificationController::class, 'logs'])->name('notifications.logs');
        Route::post('/invoices/{invoice}/notify', [NotificationController::class, 'sendInvoiceNotification'])->name('notifications.invoice');

        /* Service Plans (paket harga) */
        Route::get('/plans',              [ServicePlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/create',       [ServicePlanController::class, 'create'])->name('plans.create');
        Route::post('/plans',             [ServicePlanController::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}/edit',  [ServicePlanController::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}',       [ServicePlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}',    [ServicePlanController::class, 'destroy'])->name('plans.destroy');
    });

    /* Inventory perangkat (ONU / Router / dst) */
    Route::middleware('role:admin,noc')->group(function () {
        Route::get('/devices',                    [DeviceController::class, 'index'])->name('devices.index');
        Route::get('/devices/create',             [DeviceController::class, 'create'])->name('devices.create');
        Route::post('/devices',                   [DeviceController::class, 'store'])->name('devices.store');
        Route::get('/devices/scan',               [DeviceController::class, 'scan'])->name('devices.scan');
        Route::get('/devices/qr-sheet',           [DeviceController::class, 'qrSheet'])->name('devices.qr-sheet');
        Route::get('/devices/{device}',           [DeviceController::class, 'show'])->name('devices.show');
        Route::get('/devices/{device}/edit',      [DeviceController::class, 'edit'])->name('devices.edit');
        Route::put('/devices/{device}',           [DeviceController::class, 'update'])->name('devices.update');
        Route::delete('/devices/{device}',        [DeviceController::class, 'destroy'])->name('devices.destroy');
        Route::post('/devices/{device}/assign',     [DeviceController::class, 'assign'])->name('devices.assign');
        Route::post('/devices/{device}/transition', [DeviceController::class, 'transition'])->name('devices.transition');
        Route::get('/devices/{device}/qr',         [DeviceController::class, 'qr'])->name('devices.qr');
    });

    /* Shortlink QR: scan -> redirect detail perangkat by serial */
    Route::get('/d/{serial}', [DeviceController::class, 'lookup'])->where('serial', '.+')->name('devices.lookup');

    /* PPPoE */
    Route::middleware('role:admin,noc')->group(function () {
        Route::get('/pppoe',                 [PppoeController::class, 'index'])->name('pppoe.index');
        Route::get('/pppoe/create',          [PppoeController::class, 'create'])->name('pppoe.create');
        Route::post('/pppoe',                [PppoeController::class, 'store'])->name('pppoe.store');
        Route::post('/pppoe/bulk',           [PppoeController::class, 'bulkCreate'])->name('pppoe.bulk');
        Route::delete('/pppoe/{username}',   [PppoeController::class, 'destroy'])
            ->where('username', '.*')->name('pppoe.destroy');
        Route::post('/pppoe/{username}/reset-password', [PppoeController::class, 'resetPassword'])
            ->where('username', '.*')->name('pppoe.reset-password');

        /* Hotspot */
        Route::get('/hotspot',         [HotspotController::class, 'index'])->name('hotspot.index');
        Route::get('/hotspot/create',  [HotspotController::class, 'create'])->name('hotspot.create');
        Route::post('/hotspot',        [HotspotController::class, 'store'])->name('hotspot.store');
        Route::delete('/hotspot/{id}', [HotspotController::class, 'destroy'])->name('hotspot.destroy');

        /* Voucher Hotspot (sourced from FreeRADIUS Hotspot* / HS_* groups) */
        Route::get('/vouchers',                       [VoucherController::class, 'index'])->name('vouchers.index');
        Route::post('/vouchers/bulk-expired',         [VoucherController::class, 'bulkDeleteExpired'])->name('vouchers.bulk-expired');

        /* Voucher generator + cetak A4 (5x6 = 30/lembar) */
        Route::get('/vouchers/generate',              [VoucherController::class, 'generateForm'])->name('vouchers.generate-form');
        Route::post('/vouchers/generate',             [VoucherController::class, 'generate'])->name('vouchers.generate');
        Route::get('/vouchers/batch/{batch}/print',   [VoucherController::class, 'batchPrint'])->name('vouchers.batch.print');
        Route::get('/vouchers/batch/{batch}',         [VoucherController::class, 'batchShow'])->name('vouchers.batch.show');
        Route::delete('/vouchers/batch/{batch}',      [VoucherController::class, 'batchDestroy'])->name('vouchers.batch.destroy');

        Route::delete('/vouchers/{username}',         [VoucherController::class, 'destroy'])
            ->where('username', '.*')->name('vouchers.destroy');

        /* Mikrotik */
        Route::get('/mikrotik',                       [MikrotikController::class, 'index'])->name('mikrotik.index');
        Route::post('/mikrotik',                      [MikrotikController::class, 'store'])->name('mikrotik.store');
        Route::get('/mikrotik/{id}',                  [MikrotikController::class, 'show'])->name('mikrotik.show');
        Route::put('/mikrotik/{id}',                  [MikrotikController::class, 'update'])->name('mikrotik.update');
        Route::delete('/mikrotik/{id}',               [MikrotikController::class, 'destroy'])->name('mikrotik.destroy');
        Route::post('/mikrotik/{id}/disconnect',      [MikrotikController::class, 'disconnect'])->name('mikrotik.disconnect');

        /* SNMP */
        Route::get('/snmp',                  [SnmpController::class, 'index'])->name('snmp.index');
        Route::post('/snmp/{id}/poll',       [SnmpController::class, 'poll'])->name('snmp.poll');
        Route::get('/snmp/{id}/history.json', [SnmpController::class, 'history'])->name('snmp.history');

        /* GenieACS / TR-069 Management */
        Route::get('/genieacs',                          [GenieacsController::class, 'index'])->name('genieacs.index');
        Route::get('/genieacs/export.csv',               [GenieacsController::class, 'exportCsv'])->name('genieacs.export-csv');
        Route::post('/genieacs/sync',                    [GenieacsController::class, 'sync'])->name('genieacs.sync');
        Route::get('/genieacs/{device}',                 [GenieacsController::class, 'show'])->name('genieacs.show');
        Route::post('/genieacs/{device}/reboot',         [GenieacsController::class, 'reboot'])->name('genieacs.reboot');
        Route::post('/genieacs/{device}/refresh',        [GenieacsController::class, 'refresh'])->name('genieacs.refresh');
        Route::post('/genieacs/{device}/factory-reset',  [GenieacsController::class, 'factoryReset'])->name('genieacs.factory-reset');
        Route::post('/genieacs/{device}/ssid',           [GenieacsController::class, 'setSsid'])->name('genieacs.ssid');
        Route::post('/genieacs/{device}/password',       [GenieacsController::class, 'setPassword'])->name('genieacs.password');
        Route::post('/genieacs/{device}/pppoe',          [GenieacsController::class, 'setPppoe'])->name('genieacs.pppoe');
        Route::post('/genieacs/{device}/wan-ip',         [GenieacsController::class, 'setWanIp'])->name('genieacs.wan-ip');
        Route::post('/genieacs/{device}/suspend-wan',    [GenieacsController::class, 'suspendWan'])->name('genieacs.suspend-wan');
        Route::post('/genieacs/{device}/wifi-security',  [GenieacsController::class, 'setWifiSecurity'])->name('genieacs.wifi-security');
    });

    /* Pengaturan — User Management (superadmin/admin only) */
    Route::middleware('role:superadmin,admin')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/users',                  [SettingsUserController::class, 'index'])->name('users.index');
        Route::get('/users/create',           [SettingsUserController::class, 'create'])->name('users.create');
        Route::post('/users',                 [SettingsUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit',      [SettingsUserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}',           [SettingsUserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/reset',    [SettingsUserController::class, 'resetPassword'])->name('users.reset');
        Route::post('/users/{user}/toggle',   [SettingsUserController::class, 'toggleActive'])->name('users.toggle');
        Route::delete('/users/{user}',        [SettingsUserController::class, 'destroy'])->name('users.destroy');
    });
});
