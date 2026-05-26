<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataCarController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\ReservationsController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TrackerController;
use App\Http\Controllers\OtpController;

use App\Http\Controllers\ProfileUser;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']); 
Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


//utility 
Route::post('/verify-otp-forget-password', [OtpController::class, 'verify_otp_forget_password']);
Route::post('/verify-otp-account', [OtpController::class, 'verify_otp_account']);


//show car 
Route::get('/show/{id}', [DataCarController::class, 'show']);
Route::middleware('throttle:60,1')->get('/show', [DataCarController::class, 'index']);


//admin
Route::middleware(['role:admin'])->group(function(){

    //menagemen data mobil
    Route::post('/add-car', [DataCarController::class, 'store']);
    Route::delete('/deleteCar/{id}', [DataCarController::class, 'destroy']);
    Route::post('/updateCar/{id}', [DataCarController::class, 'update']);

    //manajemen reservasi 
    Route::patch('/approve-reservasi/{id}', [AdminController::class, 'ApproveReserv']);
    Route::patch('/approve-refund/{id}', [AdminController::class, 'refund']);
    Route::patch('/rejected-reservation/{id}',[AdminController::class, '']); 
    Route::get('/reservations', [AdminController::class, 'listReservasi']);
    Route::get('/reservation/{id}', [AdminController::class, 'detailReserv']); 

    //manajemen pelanggan 
    Route::get('/customer-profile', [AdminController::class, 'customerProfile']);

});


Route::middleware(['role:customer'])->group(function(){

    //profile user 
    Route::get('/showProfile', [ProfileUser::class, 'show']);
    Route::post('/updateProfile', [ProfileUser::class, 'update']);
    Route::delete('/deleteAccount', [ProfileUser::class, 'destroy']);

    //reservasi 
    Route::post('/add-reservation', [ReservationsController::class, 'store']);
    Route::get('/history-reservation', [ReservationsController::class, 'index']);
    Route::patch('/cancel-reserv/{id}', [ReservationsController::class, 'cancel']);

    //pembayaran 
    Route::post('/payment', [PaymentsController::class, 'store']); 
    Route::get('/midtrans/callback', [PaymentsController::class, 'callback']);

});

//trakcer 
Route::post('/tracker/ping', [TrackerController::class, 'ping']);
Route::post('/tracker/stop', [TrackerController::class, 'stop']);
Route::get('/tracker/locations', [TrackerController::class, 'locations']);
Route::get('/tracker/locations/{carId}', [TrackerController::class, 'locationByCar']);
Route::post('/tracker/generate-token', [TrackerController::class, 'generateToken']);
Route::delete('/tracker/{id}', [TrackerController::class, 'destroy']);
Route::get('/tracker/history/{carId}', [TrackerController::class, 'history']);
