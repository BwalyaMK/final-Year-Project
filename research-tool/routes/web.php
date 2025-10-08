<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Chat routes
Route::middleware(['auth'])->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
    Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');
    Route::post('/pdfs/upload', [ChatController::class, 'upload'])->name('pdfs.upload');
});

// PDF routes: /pdfs/*
Route::middleware('auth')->group(function () {
    Route::get('/pdfs', [PdfController::class, 'index'])->name('pdfs.index');
    Route::post('/pdfs/upload', [PdfController::class, 'upload'])->name('pdfs.upload');
    Route::get('/pdfs/{id}', [PdfController::class, 'show'])->name('pdfs.show');
    Route::delete('/pdfs/{id}', [PdfController::class, 'destroy'])->name('pdfs.destroy');
    Route::get('/pdfs/{id}/download', [PdfController::class, 'download'])->name('pdfs.download');
});


//temp debugging route
Route::post('/debug-upload', function(Request $request) {
    return response()->json([
        'has_file_papers' => $request->hasFile('papers'),
        'all_files' => $request->allFiles(),
        'all_inputs' => $request->all(),
        'content_type' => $request->header('Content-Type'),
    ]);
})->middleware('auth');

require __DIR__.'/auth.php';
