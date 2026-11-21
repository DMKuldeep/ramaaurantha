<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Auth\EmailOtpController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StoreController::class, 'index'])->name('shop');
Route::get('/products/{product}', [StoreController::class, 'show'])->name('products.show');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [EmailOtpController::class, 'show'])->name('login');
    $otpRequestRoute = Route::post('/login/otp', [EmailOtpController::class, 'send'])->name('login.otp.send');
    if (! app()->environment('local')) {
        $otpRequestRoute->middleware('throttle:3,15');
    }
    Route::post('/login/verify', [EmailOtpController::class, 'verify'])->middleware('throttle:8,1')->name('login.otp.verify');
});
Route::post('/logout', [EmailOtpController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::patch('/account', [AccountController::class, 'update'])->name('account.update');
    Route::post('/account/addresses', [AccountController::class, 'storeAddress'])->name('addresses.store');
    Route::delete('/account/addresses/{address}', [AccountController::class, 'deleteAddress'])->name('addresses.destroy');
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'place'])->middleware('throttle:10,1')->name('checkout.place');
    Route::get('/orders/{order}', [CheckoutController::class, 'confirmation'])->name('orders.show');
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'show'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->middleware('auth')->name('logout');
    Route::middleware(['auth', 'admin'])->group(function (): void {
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');
        Route::patch('/orders/{order}', [AdminController::class, 'updateOrder'])->name('orders.update');
        Route::resource('products', AdminProductController::class)->except(['show', 'create']);
        Route::post('/team', [AdminController::class, 'storeAdmin'])->name('team.store');
    });
});
