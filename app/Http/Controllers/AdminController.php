<?php
namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Produit;
use App\Models\User;
use App\Models\Vente;

class AdminController extends Controller{
    //afficher le nombre d'utilisateurs 
    public function nombreUtilisateurs()
    {
        $nombreUtilisateurs =User::count();
        return response()->json(['nombre_utilisateurs' => $nombreUtilisateurs]);
    }
    //afficher le nombre de ventes 
    public function nombreVentes()
    {
        $nombreVentes = Vente::count();
        return response()->json(['nombre_ventes' => $nombreVentes]);
    }
    //afficher le nombre de vendeuses
    public function nombreVendeuses()
    {
        $nombreVendeuses = User::where('role', 'vendeuse')->count();
        return response()->json(['nombre_vendeuses' => $nombreVendeuses]);
    }
    //afficher le nombres de produits
    public function nombreProduits()
    {
        $nombreProduits = Produit::count();
        return response()->json(['nombre_produits' => $nombreProduits]);
    }
    //afficher le nombre de clients
    public function nombreClient()
    {
        $nombreClient = Client::count();
        return response()->json(['nombre_client' => $nombreClient]);
    }
    //afficher tout les vendeuses
    public function vendeuses()
    {
        $vendeuses = User::where('role', 'vendeuse')->get();
        return response()->json(['vendeuses' => $vendeuses]);
    }
}