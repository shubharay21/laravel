<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\UserTable;

Route::get('/', UserTable::class);
