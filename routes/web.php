<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');

    Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])->name('project-members.store');
    Route::put('projects/{project}/members/{user}', [ProjectMemberController::class, 'update'])->name('project-members.update');
    Route::delete('projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])->name('project-members.destroy');
});

require __DIR__.'/settings.php';
