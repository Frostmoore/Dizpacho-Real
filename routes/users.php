<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Users\ChatController;

Route::middleware(['auth'])->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('users.chat.index');
    Route::post('/chat/send', [ChatController::class, 'send'])->name('users.chat.send');
});
