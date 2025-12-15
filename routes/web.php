<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\PageController;

// Backend info endpoint: returns JSON by default, shows Laravel landing
// page when an HTML response is requested.
Route::get('/backend', function (Request $request) {
    if ($request->wantsJson() || $request->expectsJson()) {
        return response()->json([
            'framework' => 'Laravel',
            'version' => app()->version(),
        ]);
    }

    return view('welcome');
});

// Frontend HTML Routes (keeping .html extension)
Route::get('/', [PageController::class, 'index'])->name('home');
Route::get('/index.html', [PageController::class, 'index']);
Route::get('/about-us.html', [PageController::class, 'aboutUs'])->name('about');
Route::get('/contact.html', [PageController::class, 'contact'])->name('contact');
Route::get('/rooms.html', [PageController::class, 'rooms'])->name('rooms');
Route::get('/room-details.html', [PageController::class, 'roomDetails'])->name('room.details');
Route::get('/login.html', [PageController::class, 'login'])->name('login');
Route::get('/profile.html', [PageController::class, 'profile'])->name('profile');
Route::get('/myreservation.html', [PageController::class, 'myReservation'])->name('myreservation');

// Admin routes
Route::get('/admin/index.html', [PageController::class, 'adminIndex'])->name('admin.index');
Route::get('/admin/bookings.html', [PageController::class, 'adminBookings'])->name('admin.bookings');
Route::get('/admin/rooms.html', [PageController::class, 'adminRooms'])->name('admin.rooms');
Route::get('/admin/users.html', [PageController::class, 'adminUsers'])->name('admin.users');
Route::get('/admin/room-categories.html', [PageController::class, 'adminRoomCategories'])->name('admin.room-categories');
Route::get('/admin/profile.html', [PageController::class, 'adminProfile'])->name('admin.profile');




