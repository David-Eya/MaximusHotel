<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\InchargeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\PageController;

// API Routes - Laravel automatically prefixes these with 'api'
Route::get('/', function (Request $request) {
    if ($request->wantsJson() || $request->expectsJson()) {
        return response()->json([
            'framework' => 'Laravel',
            'version' => app()->version(),
        ]);
    }

    return view('welcome');
});

// Auth API
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/logout', [AuthController::class, 'logout']);
Route::get('/auth/verify', [AuthController::class, 'verify']);
Route::post('/auth/send_otp', [AuthController::class, 'sendOtp']);
Route::post('/auth/verify_otp', [AuthController::class, 'verifyOtp']);
Route::post('/auth/forgot_password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset_password', [AuthController::class, 'resetPassword']);

// Pages API
Route::get('/pages/get_room_types', [PageController::class, 'getRoomTypes']);
Route::get('/pages/get_rooms', [PageController::class, 'getRooms']);
Route::get('/pages/get_room_details', [PageController::class, 'getRoomDetails']);

// Profile API
Route::get('/profile/get', [ProfileController::class, 'get']);
Route::post('/profile/update', [ProfileController::class, 'update']);
Route::post('/profile/update-image', [ProfileController::class, 'updateImage']);
Route::post('/profile/update-password', [ProfileController::class, 'updatePassword']);

// Reservations API
Route::get('/reservations/list', [ReservationController::class, 'list']);
Route::post('/reservations/create', [ReservationController::class, 'create']);
Route::get('/reservations/check-availability', [ReservationController::class, 'checkAvailability']);
Route::post('/reservations/cancel', [ReservationController::class, 'cancel']);

// Admin API
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/bookings', [AdminController::class, 'bookings']);
    Route::post('/bookings', [AdminController::class, 'createBooking']);
    Route::put('/bookings/{book_id}/status', [AdminController::class, 'updateBookingStatus']);
    Route::get('/rooms', [AdminController::class, 'rooms']);
    Route::get('/rooms/next-id', [AdminController::class, 'getNextRoomId']);
    Route::post('/rooms', [AdminController::class, 'createRoom']);
    Route::put('/rooms/{room_id}', [AdminController::class, 'updateRoom']);
    Route::delete('/rooms/{room_id}', [AdminController::class, 'deleteRoom']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::get('/clients', [AdminController::class, 'getClients']); // For booking - accessible by Admin and Incharge
    Route::post('/users', [AdminController::class, 'createUser']);
    Route::put('/users/{userid}', [AdminController::class, 'updateUser']);
    Route::delete('/users/{userid}', [AdminController::class, 'deleteUser']);
    Route::get('/room-categories', [AdminController::class, 'roomCategories']);
    Route::post('/room-categories', [AdminController::class, 'createRoomCategory']);
    Route::match(['put', 'post'], '/room-categories/{category_id}', [AdminController::class, 'updateRoomCategory']);
    Route::delete('/room-categories/{category_id}', [AdminController::class, 'deleteRoomCategory']);
});

// Incharge API
Route::prefix('incharge')->group(function () {
    Route::get('/dashboard', [InchargeController::class, 'dashboard']);
});

