<?php

use Illuminate\Support\Facades\Route;
use TeknikHub\FileManager\Http\Controllers\FileManagerController;

Route::get('/', [FileManagerController::class, 'index'])->name('index');
Route::get('/browse', [FileManagerController::class, 'browse'])->name('browse');
Route::post('/folder', [FileManagerController::class, 'createFolder'])->name('folder');
Route::post('/upload', [FileManagerController::class, 'upload'])->name('upload');
Route::post('/rename', [FileManagerController::class, 'rename'])->name('rename');
Route::post('/delete', [FileManagerController::class, 'delete'])->name('delete');
Route::post('/paste', [FileManagerController::class, 'paste'])->name('paste');
Route::get('/preview', [FileManagerController::class, 'preview'])->name('preview');
Route::get('/download', [FileManagerController::class, 'download'])->name('download');
