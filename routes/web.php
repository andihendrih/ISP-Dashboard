<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GenieacsController;
use App\Http\Controllers\HotspotController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MikrotikController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PppoeController;
use App\Http\Controllers\ServicePlanController;
use App\Http\Controllers\SnmpController;
use App\Http\Controllers\UserManagementController;
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

        Route::get('/users-radius',          [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users-radius/{username}', [UserManagementController::class, 'show'])
            ->where('username', '.*')
            ->name('users.show');

        /* Billing — Invoices & Payments (admin + finance) */
        Route::get('/invoices',                 [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/create',          [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('/invoices',                [InvoiceController::class, 'store'])->name('invoices.store');
        Route::post('/invoices/generate-batch', [InvoiceController::class, 'generateBatch'])->name('invoices.generate-batch');
        Route::get('/invoices/{invoice}',           [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{invoice}/pdf',       [InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::get('/invoices/{invoice}/receipt',   [InvoiceController::class, 'receipt'])->name('invoices.receipt');
        Route::post('/invoices/{invoice}/cancel',   [InvoiceController::class, 'cancel'])->name('invoices.cancel');

        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::delete('/payments/{payment}',        [PaymentController::class, 'destroy'])->name('payments.destroy');

        /* Service Plans (paket harga) */
        Route::get('/plans',              [ServicePlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/create',       [ServicePlanController::class, 'create'])->name('plans.create');
        Route::post('/plans',             [ServicePlanController::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}/edit',  [ServicePlanController::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}',       [ServicePlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}',    [ServicePlanController::class, 'destroy'])->name('plans.destroy');
    });

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

        /* GenieACS */
        Route::get('/genieacs',                       [GenieacsController::class, 'index'])->name('genieacs.index');
        Route::post('/genieacs/sync',                 [GenieacsController::class, 'sync'])->name('genieacs.sync');
        Route::post('/genieacs/{deviceId}/reboot',    [GenieacsController::class, 'reboot'])
            ->where('deviceId', '.*')->name('genieacs.reboot');
        Route::post('/genieacs/{deviceId}/refresh',   [GenieacsController::class, 'refresh'])
            ->where('deviceId', '.*')->name('genieacs.refresh');
        Route::post('/genieacs/{deviceId}/ssid',      [GenieacsController::class, 'setSsid'])
            ->where('deviceId', '.*')->name('genieacs.ssid');
        Route::post('/genieacs/{deviceId}/password',  [GenieacsController::class, 'setPassword'])
            ->where('deviceId', '.*')->name('genieacs.password');
    });

});
