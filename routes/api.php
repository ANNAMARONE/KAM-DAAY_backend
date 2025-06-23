<?php
use App\Http\Controllers\AIController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\GestionUtilisateur;
use App\Http\Controllers\VenteController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
Route::post('/ai/ask', [AIController::class, 'ask']);
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:api')->get('/notifications', function () {
    return auth::user()->notifications()->latest()->take(10)->get();
});
Route::middleware('jwt')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user', [AuthController::class, 'user'])->name('user');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::post('/mot-de-passe/oublie-par-telephone', [AuthController::class, 'demandeMotDePasseOublie']);
    Route::post('/reset-password', action: [AuthController::class, 'resetPassword']);

   
    //route pour la gestion clients
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('detail_client/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::post('/ajouter_clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/mes_clients', [ClientController::class, 'mesClients'])->name('clients.mesClients');
    Route::get('/mes_clients_supprimer', [ClientController::class, 'mesClientsSupprimer'])->name('clients.mesClientsSupprimer');
    Route::get('/clients_inactifs', [ClientController::class, 'clientsInactifs'])->name('clients.clientsInactifs');
    Route::put('/modifier_client/{client}', [ClientController::class, 'update'])->name('clients.update');
    //Route::delete('/supprimer_client/{id}', [ClientController::class, 'destroy'])->name('clients.destroy');
    Route::post('/restorer_client/{client}', [ClientController::class, 'restore'])->name('clients.restorer');
    Route::post('/force_delete_client/{client}', [ClientController::class, 'forceDelete'])->name('clients.forceDelete');
    Route::post('/soft_delete_client/{client}', [ClientController::class, 'softDelete'])->name('clients.softDelete');
    Route::get('/clients/search', [ClientController::class, 'search'])->name('clients.search');
    Route::get('/filtre_clients', [ClientController::class, 'filterByDate'])->name('clients.filterByDate');
    Route::get('/export_clients', [ClientController::class, 'exportClients'])->name('clients.exportClients');
    Route::get('/exportmes_clients', [ClientController::class, 'exportMesClients'])->name('clients.exportMesClients');
    //route pour la gestion des ventes
    Route::get('/mes_ventes', [VenteController::class, 'mesVentes'])->name('ventes.mesVentes');
    Route::post('/ajouter_vente', [VenteController::class, 'store'])->name('ventes.store');
    Route::get('/contact_client/{id}', [ClientController::class, 'contactLinks']);
   Route::get('/contactMesClient', [ClientController::class, 'contactClients']);
    //route pour la gestion des utilisateurs
    Route::get('/utilisateurs', [GestionUtilisateur::class, 'index'])->name('utilisateurs.index');   
    Route::post('/activer_utilisateur/{id}', [GestionUtilisateur::class, 'activerUtilisateur'])->name('utilisateurs.activer'); 
    Route::post('/desactiver_utilisateur/{id}', [GestionUtilisateur::class, 'desactiverUtilisateur'])->name('utilisateurs.desactiver');
    Route::delete('/supprimer_utilisateur/{id}', [GestionUtilisateur::class, 'supprimerUtilisateur'])->name('utilisateurs.destroy');
    Route::get('/utilisateurs/search', [GestionUtilisateur::class, 'search'])->name('utilisateurs.search');
    Route::get('/utilisateur_detail/{id}', [GestionUtilisateur::class, 'show'])->name('utilisateurs.show');
    Route::get('/recherche_utilisateur', [GestionUtilisateur::class, 'rechercheUtilisateur'])->name('utilisateurs.recherche');
    Route::get('/export_utilisateurs', [GestionUtilisateur::class, 'exportUtilisateurs'])->name('utilisateurs.exportUtilisateurs');
    Route::get('/export_mes_utilisateurs', [GestionUtilisateur::class, 'exportMesUtilisateurs'])->name('utilisateurs.exportMesUtilisateurs');
    Route::get('/export_mes_ventes', [VenteController::class, 'exportMesVentes'])->name('ventes.exportMesVentes');
    Route::get('/verifier_utilisateur_actif/{id}', [GestionUtilisateur::class, 'isActif'])->name('utilisateurs.isActif');
});
Route::get('/ventes', [VenteController::class, 'index'])->name('ventes.index');
Route::get('/ventes_par_client/{id}', [VenteController::class, 'ventesParClient'])->name('ventes.ventesParClient');
Route::get('/ventes_par_date/{date}', [VenteController::class, 'filterVenteByDate'])->name('ventes.filterVenteByDate');
Route::post('/noter_vente/{id}/{satisfaite}', [VenteController::class, 'noterVente'])->name('ventes.noterVente');
Route::get('/ventesNonSatisfaites', [VenteController::class, 'ventesNonSatisfaites'])->name('ventes.ventesNonSatisfaites');
Route::delete('/supprimer_vente/{id}', [VenteController::class, 'destroy'])->name('ventes.destroy');
Route::get('/detail_vente/{id}', [VenteController::class, 'show'])->name('ventes.show');
Route::get('/reponse_client/{id}/{satisfaite}', [VenteController::class, 'noterParLien']);
Route::get('/reponse_vente/{vente_id}/{satisfaite}', [VenteController::class, 'noterParLienVente']);
Route::get('/alertes/clients_insatisfaits', [ClientController::class, 'clientsInsatisfaitsRecurrents']);