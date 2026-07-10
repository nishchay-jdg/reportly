<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AgreementController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectFileController;
use App\Http\Controllers\ProjectVersionController;
use App\Http\Controllers\SettingsController;
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

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications');
    Route::patch('/settings/brand-kit', [SettingsController::class, 'updateBrandKit'])->name('settings.brand-kit');

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('/projects/{project}/duplicate', [ProjectController::class, 'duplicate'])->name('projects.duplicate');

    Route::post('/projects/{project}/files', [ProjectFileController::class, 'store'])->name('projects.files.store');
    Route::patch('/projects/{project}/files/{file}', [ProjectFileController::class, 'update'])->name('projects.files.update');
    Route::delete('/projects/{project}/files/{file}', [ProjectFileController::class, 'destroy'])->name('projects.files.destroy');

    Route::post('/projects/{project}/versions', [ProjectVersionController::class, 'store'])->name('projects.versions.store');
    Route::post('/projects/{project}/versions/{version}/restore', [ProjectVersionController::class, 'restore'])->name('projects.versions.restore');
    Route::delete('/projects/{project}/versions/{version}', [ProjectVersionController::class, 'destroy'])->name('projects.versions.destroy');

    Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
    Route::patch('/folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');

    Route::post('/projects/{project}/shares', [ShareController::class, 'store'])->name('shares.store');
    Route::patch('/projects/{project}/shares/{share}', [ShareController::class, 'update'])->name('shares.update');
    Route::delete('/projects/{project}/shares/{share}', [ShareController::class, 'destroy'])->name('shares.destroy');

    Route::post('/comments/{comment}/resolve', [CommentController::class, 'resolve'])->name('comments.resolve');

    Route::get('/library', [MediaController::class, 'page'])->name('media.page');

    // Named "/media-library", not "/media" — uploaded files are physically stored under
    // public/media/, and a route path identical to that directory name gets intercepted by
    // the web server as a static-file/directory lookup before Laravel's router ever sees it.
    Route::get('/media-library', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media-library', [MediaController::class, 'store'])->name('media.store');
    Route::delete('/media-library/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

    Route::middleware('platform_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users/{user}/toggle-platform-admin', [AdminController::class, 'togglePlatformAdmin'])->name('users.toggle-platform-admin');
        Route::post('/deploy', [DeployController::class, 'store'])->name('deploy.store');
        Route::get('/deploy/status', [DeployController::class, 'status'])->name('deploy.status');
    });
});

// Public share viewer (no auth) — guests may view, comment, and delete their own comments
// (ownership is checked against a long-lived identity cookie, see CommentController).
Route::get('/r/{slug}', [ShareViewController::class, 'show'])->name('share.view');
Route::post('/r/{slug}/unlock', [ShareViewController::class, 'unlock'])->name('share.unlock')->middleware('throttle:share-unlock');
Route::post('/r/{slug}/comments', [CommentController::class, 'store'])->name('share.comments.store')->middleware('throttle:guest-comments');
Route::get('/r/{slug}/comments', [ShareViewController::class, 'comments'])->name('share.comments.index');
Route::post('/r/{slug}/approve', [ShareViewController::class, 'approve'])->name('share.approve');
Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
Route::get('/r/{slug}/agreement', [AgreementController::class, 'show'])->name('share.agreement.show');
Route::post('/r/{slug}/agreement', [AgreementController::class, 'store'])->name('share.agreement.store')->middleware('throttle:agreement-sign');

require __DIR__.'/auth.php';
