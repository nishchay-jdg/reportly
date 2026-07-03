<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectFileController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\ShareViewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [ProjectController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::post('/projects/{project}/files', [ProjectFileController::class, 'store'])->name('projects.files.store');
    Route::patch('/projects/{project}/files/{file}', [ProjectFileController::class, 'update'])->name('projects.files.update');
    Route::delete('/projects/{project}/files/{file}', [ProjectFileController::class, 'destroy'])->name('projects.files.destroy');

    Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
    Route::patch('/folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');

    Route::post('/projects/{project}/shares', [ShareController::class, 'store'])->name('shares.store');
    Route::patch('/projects/{project}/shares/{share}', [ShareController::class, 'update'])->name('shares.update');
    Route::delete('/projects/{project}/shares/{share}', [ShareController::class, 'destroy'])->name('shares.destroy');

    Route::post('/comments/{comment}/resolve', [CommentController::class, 'resolve'])->name('comments.resolve');
});

// Public share viewer (no auth) — guests may view and comment.
Route::get('/r/{slug}', [ShareViewController::class, 'show'])->name('share.view');
Route::post('/r/{slug}/unlock', [ShareViewController::class, 'unlock'])->name('share.unlock');
Route::post('/r/{slug}/comments', [CommentController::class, 'store'])->name('share.comments.store');

require __DIR__.'/auth.php';
