<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Events
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
    Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::put('/events/{event}/assignments', [EventController::class, 'assign'])->name('events.assign');
    Route::get('/events/{event}/registrations', [RegistrationController::class, 'forEvent'])->name('events.registrations');

    // Registrations and passes
    Route::post('/events/{event}/register', [RegistrationController::class, 'store'])->name('events.register');
    Route::get('/my-registrations', [RegistrationController::class, 'mine'])->name('registrations.mine');
    Route::get('/registrations/{registration}/pass', [RegistrationController::class, 'pass'])->name('registrations.pass');
    Route::post('/registrations/{registration}/paid', [RegistrationController::class, 'markPaid'])->name('registrations.paid');
    Route::post('/registrations/{registration}/reject', [RegistrationController::class, 'reject'])->name('registrations.reject');

    // Chapters and users (administrators)
    Route::get('/chapters', [ChapterController::class, 'index'])->name('chapters.index');
    Route::post('/chapters', [ChapterController::class, 'store'])->name('chapters.store');
    Route::put('/chapters/{chapter}', [ChapterController::class, 'update'])->name('chapters.update');
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users');
    Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::put('/admin/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('admin.users.role');

    // Check-in
    Route::get('/check-in', [CheckInController::class, 'index'])->name('checkin.index');
    Route::get('/check-in/{event}', [CheckInController::class, 'show'])->name('checkin.show');
    Route::post('/check-in/{event}/scan', [CheckInController::class, 'scan'])->middleware('throttle:120,1')->name('checkin.scan');
    Route::get('/check-in/{event}/search', [CheckInController::class, 'search'])->middleware('throttle:60,1')->name('checkin.search');
    Route::get('/check-in/{event}/count', [CheckInController::class, 'count'])->name('checkin.count');
});
