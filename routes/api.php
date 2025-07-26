<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GestionUtilisateur;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\StatistiqueController;
use App\Http\Controllers\VenteController;
use App\Http\Controllers\WebhookController;
use App\Models\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
Route::post('/ai/ask', [AIController::class, 'ask']);
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/mot-de-passe/oublie-par-telephone', [AuthController::class, 'demandeMotDePasseOublie']);
Route::post('/reset-password', action: [AuthController::class, 'resetPassword']);
Route::middleware('auth:api')->get('/notifications', function () {
    return auth::user()->notifications()->latest()->take(10)->get();
});
Route::middleware('jwt')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user', [AuthController::class, 'user'])->name('user');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::post('/user/update-profile', [AuthController::class, 'updateProfile']);

    Route::post('/webhook/whatsapp', [WebhookController::class, 'handle']);
    Route::get('/webhook/whatsapp', [WebhookController::class, 'verify']);


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
    Route::delete('/force_delete_client/{client}', [ClientController::class, 'forceDelete'])->name('clients.forceDelete');
    Route::delete('/soft_delete_client/{client}', [ClientController::class, 'softDelete'])->name('clients.softDelete');
    Route::get('/clients/search', [ClientController::class, 'search'])->name('clients.search');
    Route::get('/filtre_clients', [ClientController::class, 'filterByDate'])->name('clients.filterByDate');
    Route::get('/export_clients', [ClientController::class, 'exportClients'])->name('clients.exportClients');
    Route::get('/exportmes_clients', [ClientController::class, 'exportMesClients'])->name('clients.exportMesClients');
    //route pour le tableau de bord
    Route::get('/nombre-clients', [ClientController::class, 'nombreClients']);
    Route::get('/nombre-ventes-aujourdhui', [VenteController::class, 'nombreVentesAujourdHui']);
    Route::get('/revenus-du-mois', [VenteController::class, 'revenusDuMois']);
    Route::get('/taux-satisfaction-positif', [FeedbackController::class, 'tauxSatisfactionPositif']);
    Route::get('/statistiques', [StatistiqueController::class, 'statistiques']);
    Route::get('/feedbacks-recents', [FeedbackController::class, 'getFeedbacksRecents']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    //route pour la gestion des ventes
    Route::get('/mes_ventes', [VenteController::class, 'mesVentes'])->name('ventes.mesVentes');
    Route::post('/ajouter_vente', [VenteController::class, 'store'])->name('ventes.store');
    Route::get('/contact_client/{id}', [ClientController::class, 'contactLinks']);
   Route::get('/contactMesClient', [ClientController::class, 'contactClients']);
    Route::get('/clients/recents', [ClientController::class, 'clientsRecents']);
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

    Route::get('/produits', [ProduitController::class, 'index'])->name('produits.index');
    Route::post('/ajouter/produits', [ProduitController::class, 'store'])->name('produits.store');
    Route::get('/vente/{id}/whatsapp', [VenteController::class, 'lancerConversationWhatsAppVente']);
    Route::get('/clients/{id}/whatsapp', [ClientController::class, 'lancerConversationWhatsApp']);
    // gestion des produits
    Route::post('/produits/{produit}/stock', [ProduitController::class, 'updateStock']);
    Route::put('/produits/{produit}', [ProduitController::class, 'update']);
    Route::delete('/produits/{produit}', [ProduitController::class, 'destroy']);
    Route::get('/produit/{id}',[ProduitController::class,'show']);
    //route pour admin 
    Route::get('/admin/nombre-utilisateurs', [AdminController::class, 'nombreUtilisateurs']);
    Route::get('/admin/nombre-ventes', [AdminController::class, 'nombreVentes']);
    Route::get('/admin/nombre-vendeuses', [AdminController::class, 'nombreVendeuses']);
    Route::get('/admin/nombre-produits', [AdminController::class, 'nombreProduits']);
    Route::get('/admin/nombre-clients', [AdminController::class, 'nombreClient']);
    Route::get('/admin/vendeuses', [AdminController::class, 'vendeuses'])->name('admin.vendeuses');
    Route::post('/admin/modifier_vendeuse/{id}', [GestionUtilisateur::class, 'update'])->name('admin.update');
    Route::get('/admin/produits',[ProduitController::class,'afficherProduit']);
    Route::get('/stats', [VenteController::class, 'stats']);
    Route::get('/ventes-par-mois', [VenteController::class, 'ventesParMois']);
});
Route::get('/ventes', [VenteController::class, 'index'])->name('ventes.index');
Route::get('/ventes_par_client/{id}', [VenteController::class, 'ventesParClient'])->name('ventes.ventesParClient');
Route::get('/ventes_par_date/{date}', [VenteController::class, 'filterVenteByDate'])->name('ventes.filterVenteByDate');
Route::post('/noter_vente/{id}/{satisfaite}', [VenteController::class, 'noterVente'])->name('ventes.noterVente');
Route::get('/ventesNonSatisfaites', [VenteController::class, 'ventesNonSatisfaites'])->name('ventes.ventesNonSatisfaites');
Route::delete('/ventes/{id}', [VenteController::class, 'destroy'])->name('ventes.destroy');
Route::get('/detail_vente/{id}', [VenteController::class, 'show'])->name('ventes.show');
Route::get('/reponse_client/{id}/{satisfaite}', [VenteController::class, 'noterParLien']);
Route::post('/ventes/{id}/noter/{satisfaite}', [VenteController::class, 'noterVente']);
Route::get('/alertes/clients_insatisfaits', [ClientController::class, 'clientsInsatisfaitsRecurrents']);