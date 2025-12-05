<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\UserTable;

Route::get('/', function () {
    return view('online');
});

Route::get('/users', UserTable::class);
