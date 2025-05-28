<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/' ,function(){
    return response()->json(['message'=>'hello word']);
    });

        Route::middleware('jwt')->group(function(){
            
        });