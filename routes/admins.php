<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware(['verified', EnsureAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    // USERS MANAGEMENT
    Route::prefix('users')->controller(UserController::class)->group(function () {
        Route::get('/', 'index')->name('users.index');
        Route::post('/', 'store')->name('users.store');
        Route::get('{user}', 'show')->name('users.show');
        Route::match(['put', 'patch'], '{user}', 'update')->name('users.update');
        Route::delete('{user}', 'destroy')->name('users.destroy');
        Route::patch('{user}/toggle-active', 'toggleActive')->name('users.toggle-active');
        Route::post('{user}/avatar', 'updateAvatar')->name('users.avatar');
    });
});
