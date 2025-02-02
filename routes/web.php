<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/authenticate', [AuthController::class, 'authenticate'])->name('authenticate');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/register', [AuthController::class, 'register'])->name('register');
Route::post('/storeUser', [AuthController::class, 'storeUser'])->name('storeUser');

Route::middleware(['auth:web'])->group(function () {
    Route::get('/',[AdminController::class,'index'])->name('dashboard');
    Route::get('/addWebsite',[AdminController::class,'addWebsite'])->name('addWebsite');
    Route::get('/deleteWebsite',[AdminController::class,'deleteWebsite'])->name('deleteWebsite');
    Route::post('/storeWebsite', [AdminController::class, 'storeWebsite'])->name('storeWebsite');
});