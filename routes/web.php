<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MemberController;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected (Admin & Petugas)
Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Laman Anggota: CRUD penuh dapat diakses baik oleh admin maupun petugas
    Route::resource('members', MemberController::class);

    // Fitur CRUD hanya untuk Admin (Buku, Peminjaman, Kategori)
    Route::middleware(['admin'])->group(function () {
        Route::resource('books', BookController::class)->except(['index', 'show']);

        Route::get('/loans/create', [LoanController::class, 'create'])->name('loans.create');
        Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');
        Route::get('/loans/{loan}/edit', [LoanController::class, 'edit'])->name('loans.edit');
        Route::put('/loans/{loan}', [LoanController::class, 'update'])->name('loans.update');
        Route::delete('/loans/{loan}', [LoanController::class, 'destroy'])->name('loans.destroy');
        Route::put('/loans/{id}/kembalikan', [LoanController::class, 'kembalikan'])->name('loans.kembalikan');

        Route::resource('categories', CategoryController::class)->except(['show']);
    });

    // Read-only (Index, Show, Report) dapat diakses Admin maupun Petugas
    Route::resource('books', BookController::class)->only(['index', 'show']);

    // Route khusus harus didaftarkan SEBELUM Route::resource('loans'),
    // supaya "/loans/report" tidak dianggap sebagai "/loans/{loan}".
    Route::get('/loans/report', [LoanController::class, 'report'])->name('loans.report');
    Route::resource('loans', LoanController::class)->only(['index', 'show']);
});
