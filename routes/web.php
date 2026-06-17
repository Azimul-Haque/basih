<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/clear', ['as'=>'clear','uses'=>'IndexController@clear']);
