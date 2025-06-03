<?php

namespace App\Http\Controllers;


use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\Produit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clients = Client::all();
        return response()->json($clients);
    }
//afficher les clients ajouter par l'utilisateur connecté
    public function mesClients()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $clients = Client::where('user_id', $user->id)->get();
        return response()->json($clients);
    }
   // afficher les clients supprimer par l'utilisateur connecté
   public function mesClientsSupprimer(){
    $user = Auth::user();
    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $clients =Client::onlyTrashed()->where('user_id', $user->id)->get();
    return response()->json($clients);
   }
//changement automatique de l'état du client à inactif si il n'a pas de vente depuis 30 jours
    public function clientsInactifs()
    {
        $clients = Client::whereDoesntHave('ventes', function ($query) {
            $query->where('created_at', '>=', now()->subDays(30));
        })->get();

        return response()->json($clients);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
    
        try {
            // Récupérer l'utilisateur connecté
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
    
            // Validation
            $validatedData = $request->validate([
                'prenom' => 'required|string|max:255',
                'nom' => 'required|string|max:255',
                'telephone' => 'required|string|max:15',
                'adresse' => 'nullable|string|max:255',
                'statut' => 'required|string|max:50',
                'type' => 'required|string|max:50',
                'date_vente' => 'required|date',
                'produits' => 'required|array|min:1',
                'produits.*.nom' => 'required|string|max:255',
                'produits.*.quantite' => 'required|integer|min:1',
                'produits.*.prix_unitaire' => 'required|numeric|min:0',
            ]);
    
            // Création du client
            $client = Client::create([
                'nom' => $validatedData['prenom'],
                'prenom' => $validatedData['nom'],
                'telephone' => $validatedData['telephone'],
                'adresse' => $validatedData['adresse'] ?? null,
                'statut' => $validatedData['statut'],
                'type' => $validatedData['type'],
                'user_id' => $user->id,
            ]);
    
           
          $vente = $client->ventes()->create(); // client_id automatiquement assigné

          // Boucle sur les produits
          foreach ($validatedData['produits'] as $produitData) {
              $produit = Produit::firstOrCreate(
                  ['nom' => $produitData['nom']],
                  ['image' => null]
              );
          
              $vente->produits()->attach($produit->id, [
                  'quantite' => $produitData['quantite'],
                  'prix_unitaire' => $produitData['prix_unitaire'],
                  'montant_total' => $produitData['quantite'] * $produitData['prix_unitaire'],
                  'date_vente' => $validatedData['date_vente'],
              ]);
          }

            DB::commit();
    
            return response()->json([
                'message' => 'Client et vente enregistrés avec succès',
                'client' => $client,
                'vente' => $vente,
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage()
            ], 500);
        }
    }
    

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        // Vérifier si le client existe
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }
       //afficher les detailles du client et ses ventes
        $clientDetails = $client->load(['ventes.produits']);
        return response()->json($clientDetails);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        // Vérifier si le client existe
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        // Validation des données
        $validatedData = $request->validate([
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'telephone' => 'required|string|max:15',
            'adresse' => 'nullable|string|max:255',
            'statut' => 'required|string|max:50',
            'type' => 'required|string|max:50',
        ]);

        // Mettre à jour le client
        $client->update($validatedData);

        return response()->json(['message' => 'Client updated successfully', 'client' => $client], 200);
        
    }

  
    //Supprimer un client sans le suprrimer de la base de données
    public function softDelete(Client $client)
    {
        // Vérifier si le client existe
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        // Marquer le client comme supprimé
        $client->delete();

        return response()->json(['message' => 'Client deleted successfully'], 200);
    }



    //supprimer un client et ses ventes de la base de données
    public function forceDelete(Client $client)
    {
        // Vérifier si le client existe
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        // Supprimer le client et ses ventes
        $client->ventes()->delete(); // Supprimer les ventes associées
        $client->forceDelete(); // Supprimer le client

        return response()->json(['message' => 'Client and associated sales deleted successfully'], 200);
    }
  //restaurer un client supprimé
    public function restore($id)
    {
        $client = Client::withTrashed()->find($id);
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        $client->restore();

        return response()->json(['message' => 'Client restored successfully', 'client' => $client], 200);
    }
}