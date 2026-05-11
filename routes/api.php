<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataCarController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\ReservationsController;

use App\Http\Controllers\ProfileUser;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']); 

//show car 
Route::get('/show/{id}', [DataCarController::class, 'show']);
Route::get('/show', [DataCarController::class, 'index']);


//admin
Route::middleware(['role:admin'])->group(function(){

    //menagemen data mobil
    Route::post('/add-car', [DataCarController::class, 'store']);
    Route::delete('/deleteCar/{id}', [DataCarController::class, 'destroy']);
    Route::post('/updateCar/{data_car}', [DataCarController::class, 'update']);

});


Route::middleware(['role:customer'])->group(function(){

    //profile user 
    Route::get('/showProfile', [ProfileUser::class, 'show']);
    Route::post('/updateProfile', [ProfileUser::class, 'update']);
    Route::delete('/deleteAccount', [ProfileUser::class, 'destroy']);

    //reservasi 
    Route::post('/reservation', [ReservationsController::class, 'store']);
    Route::get('/reservations', [ReservationsController::class, 'index']);
});
