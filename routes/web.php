<?php

use App\Http\Controllers\PdfUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/pdf-upload', [PdfUploadController::class, 'store'])->name('pdf.upload');
});
