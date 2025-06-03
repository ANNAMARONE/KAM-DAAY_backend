<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get ('/',function(){
return response()->json(['message'=>'bonjour']);
});
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:api')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user', [AuthController::class, 'user'])->name('user');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    //route pour la gestion clients
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('detail_client/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::post('/ajouter_clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/mes_clients', [ClientController::class, 'mesClients'])->name('clients.mesClients');
    Route::get('/mes_clients_supprimer', [ClientController::class, 'mesClientsSupprimer'])->name('clients.mesClientsSupprimer');
    Route::get('/clients_inactifs', [ClientController::class, 'clientsInactifs'])->name('clients.clientsInactifs');
    Route::put('/modifier_client/{client}', [ClientController::class, 'update'])->name('clients.update');
    //Route::delete('/supprimer_client/{id}', [ClientController::class, 'destroy'])->name('clients.destroy');
    Route::post('/restorer_client/{client}', [ClientController::class, 'restorer'])->name('clients.restorer');
    Route::post('/force_delete_client/{client}', [ClientController::class, 'forceDelete'])->name('clients.forceDelete');
    Route::post('/soft_delete_client/{cliend}', [ClientController::class, 'softDelete'])->name('clients.softDelete');
});