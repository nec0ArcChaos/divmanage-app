<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('team', [TeamController::class, 'index'])->name('team.index');
    Route::post('team', [TeamController::class, 'store'])->name('team.store');
    Route::put('team/{user}', [TeamController::class, 'update'])->name('team.update');
    Route::delete('team/{user}', [TeamController::class, 'destroy'])->name('team.destroy');
    Route::patch('team/{user}/status', [TeamController::class, 'updateStatus'])->name('team.updateStatus');
    Route::post('job-titles', [TeamController::class, 'storeJobTitle'])->name('job-titles.store');
    Route::delete('job-titles/{jobTitle}', [TeamController::class, 'destroyJobTitle'])->name('job-titles.destroy');

    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');

    Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])->name('project-members.store');
    Route::put('projects/{project}/members/{user}', [ProjectMemberController::class, 'update'])->name('project-members.update');
    Route::delete('projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])->name('project-members.destroy');
    Route::post('projects/{project}/tasks', [ProjectController::class, 'storeTask'])->name('project-tasks.store');

    Route::get('my-tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    // Task comments
    Route::get('tasks/{task}/comments', [TaskCommentController::class, 'index'])->name('task-comments.index');
    Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('task-comments.store');
    Route::delete('task-comments/{comment}', [TaskCommentController::class, 'destroy'])->name('task-comments.destroy');

    // Task attachments
    Route::get('task-attachments/{attachment}/download', [TaskAttachmentController::class, 'download'])->name('task-attachments.download');
    Route::delete('task-attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])->name('task-attachments.destroy');

    // Notifications
    Route::get('notifications/counts', [NotificationController::class, 'counts'])->name('notifications.counts');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('notifications/read-by-task/{taskId}', [NotificationController::class, 'readByTask'])->name('notifications.readByTask');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
});

require __DIR__.'/settings.php';
