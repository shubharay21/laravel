<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\UserTable;
use App\Http\Controllers\LBController;
Route::get('/', function () {
    return view('online');
});

Route::get('/users', UserTable::class);

Route::controller(LBController::class)->group(function () {
    Route::any('/sendtolb', 'sendtolb')->name('sendtolb');
    Route::any('/logoutfromlb', 'logoutfromlb')->name('logoutfromlb');
    Route::any('/refreshtokenforlb', 'refreshtokenforlb')->name('refreshtokenforlb');
});
