<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\MyBookingsController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\ServiceAvailabilityController;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/checkout', CheckoutController::class)->name('checkout');

    Route::get('/my-bookings', MyBookingsController::class)->name('my-bookings');

    Route::get('/services/{service}/availability', ServiceAvailabilityController::class)
        ->name('services.availability');

    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');

    Route::post('/payments/stripe/{booking}', [StripeController::class, 'initiate'])->name('payments.stripe.initiate');
    Route::get('/payments/stripe/success', [StripeController::class, 'success'])->name('payments.stripe.success');
    Route::get('/payments/stripe/cancel', [StripeController::class, 'cancel'])->name('payments.stripe.cancel');
    Route::get('/payments/stripe/status', [StripeController::class, 'status'])->name('payments.stripe.status');

    Route::prefix('admin')
        ->middleware(['role:admin'])
        ->group(function () {
            Route::get('/bookings', [AdminBookingController::class, 'index'])->name('admin.bookings.index');
            Route::get('/bookings/create', [AdminBookingController::class, 'create'])->name('admin.bookings.create');
            Route::post('/bookings', [AdminBookingController::class, 'store'])->name('admin.bookings.store');
            Route::get('/bookings/{booking}', [AdminBookingController::class, 'show'])->name('admin.bookings.show');
            Route::patch('/bookings/{booking}/status', [AdminBookingController::class, 'updateStatus'])->name('admin.bookings.status');
            Route::patch('/bookings/{booking}/assignment', [AdminBookingController::class, 'updateAssignment'])->name('admin.bookings.assignment');

            Route::get('/services', [AdminServiceController::class, 'index'])->name('admin.services.index');
            Route::post('/services', [AdminServiceController::class, 'store'])->name('admin.services.store');
            Route::get('/services/{service}/edit', [AdminServiceController::class, 'edit'])->name('admin.services.edit');
            Route::patch('/services/{service}', [AdminServiceController::class, 'update'])->name('admin.services.update');
            Route::delete('/services/{service}', [AdminServiceController::class, 'destroy'])->name('admin.services.destroy');

            Route::put('/services/{service}/staff', [AdminServiceController::class, 'syncStaff'])->name('admin.services.staff.sync');
            Route::post('/services/{service}/prices', [AdminServiceController::class, 'storePrice'])->name('admin.services.prices.store');
            Route::patch('/prices/{price}', [AdminServiceController::class, 'updatePrice'])->name('admin.prices.update');
            Route::delete('/prices/{price}', [AdminServiceController::class, 'destroyPrice'])->name('admin.prices.destroy');

            Route::get('/staff', [AdminStaffController::class, 'index'])->name('admin.staff.index');
            Route::post('/staff', [AdminStaffController::class, 'store'])->name('admin.staff.store');
            Route::get('/staff/{staff}/edit', [AdminStaffController::class, 'edit'])->name('admin.staff.edit');
            Route::patch('/staff/{staff}', [AdminStaffController::class, 'update'])->name('admin.staff.update');
            Route::delete('/staff/{staff}', [AdminStaffController::class, 'destroy'])->name('admin.staff.destroy');

            Route::post('/staff/{staff}/schedules', [AdminStaffController::class, 'storeSchedule'])->name('admin.staff.schedules.store');
            Route::put('/staff/{staff}/schedules/bulk', [AdminStaffController::class, 'syncSchedules'])->name('admin.staff.schedules.bulk');
            Route::patch('/schedules/{schedule}', [AdminStaffController::class, 'updateSchedule'])->name('admin.schedules.update');
            Route::delete('/schedules/{schedule}', [AdminStaffController::class, 'destroySchedule'])->name('admin.schedules.destroy');

            Route::post('/staff/{staff}/time-offs', [AdminStaffController::class, 'storeTimeOff'])->name('admin.staff.time_offs.store');
            Route::delete('/time-offs/{timeOff}', [AdminStaffController::class, 'destroyTimeOff'])->name('admin.time_offs.destroy');
        });
});
