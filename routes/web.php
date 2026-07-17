<?php

use App\Http\Livewire\Chat;
use App\Http\Livewire\LogViewer;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/logs', LogViewer::class)->name('logs');
Route::get('/chat', Chat::class)->name('chat');
