<?php

use App\Http\Controllers\Chat\AttachmentController;
use App\Http\Controllers\Chat\ConversationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('chat')->name('chat.')->group(function () {
    Route::get('/', fn () => view('chat.index'))->name('index');
    Route::get('/attachments/{message}', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::post('/conversations', [ConversationController::class, 'store'])->name('conversations.store');
    Route::get('/users/search', [ConversationController::class, 'searchUsers'])->name('users.search');
});
