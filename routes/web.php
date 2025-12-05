<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\UserTable;

Route::get('/', fn() => 'You are Online');
Route::get('/users', UserTable::class);
