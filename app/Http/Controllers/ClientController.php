<?php

namespace App\Http\Controllers;


use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\Produit;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

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
                return response()->json([
                    'error' => 'Aucun utilisateur authentifié',
                    'headers' => $request->headers->all()
                ], 401);
            }
          
            // Validation
            $validatedData = $request->validate([
                'prenom' => 'required|string|max:255',
                'nom' => 'required|string|max:255',
                'telephone' => 'required|string|max:15',
                'adresse' => 'nullable|string|max:255',

                'type' => 'required|string|max:50',
                'date_vente' => 'required|date',
                'produits' => 'required|array|min:1',
                'produits.*.nom' => 'required|string|max:255',
                'produits.*.quantite' => 'required|integer|min:1',
                'produits.*.prix_unitaire' => 'required|numeric|min:0',
                

                
            ]);
    
            // Création du client
            $client = Client::create([
                'prenom' => $validatedData['prenom'],
                'nom' => $validatedData['nom'],
                'telephone' => $validatedData['telephone'],
                'adresse' => $validatedData['adresse'] ?? null,
                'statut' => 'actif',
                'type' => $validatedData['type'],
                'user_id' => $user->id,
            ]);
    
           
          $vente = $client->ventes()->create(); // client_id automatiquement assigné

          // Boucle sur les produits
          foreach ($request->input('produits') as $index => $produitData) {
            $imagePath = null;
            if ($request->hasFile("produits.$index.image")) {
                $image = $request->file("produits.$index.image");
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('produits', $imageName, 'public');
            }
        
            $produit = Produit::firstOrCreate(
                ['nom' => $produitData['nom']],
                ['image' => $imagePath]
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
  //rechercher un client par son nom
  public function search(Request $request)
  {
      $searchTerm = $request->input('q');
  
      if (!$searchTerm) {
          return response()->json(['error' => 'Search query is required'], 400);
      }
  
      $clients = Client::where('nom', 'LIKE', '%' . $searchTerm . '%')
          ->orWhere('prenom', 'LIKE', '%' . $searchTerm . '%')
          ->orWhere('telephone', 'LIKE', '%' . $searchTerm . '%')
          ->get();
  
      return response()->json($clients);
  }
  
  //filtrer les client par date de vente
    public function filterByDate(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
    
        if (!$startDate || !$endDate) {
            return response()->json(['error' => 'Start date and end date are required'], 400);
        }
    
        $clients = Client::whereHas('ventes', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        })->get();
    
        return response()->json($clients);
    }
    
//exporter les clients au format CSV ou pdf

public function exportClients(Request $request)
{
    $format = $request->input('format', 'csv'); // Par défaut, CSV

    $clients = Client::all();

    if ($format === 'csv') {
        $filename = 'clients.csv';

        return response()->stream(function () use ($clients) {
            $handle = fopen('php://output', 'w');

            // En-têtes CSV
            fputcsv($handle, ['ID', 'Nom', 'Prénom', 'Téléphone', 'Adresse', 'Statut', 'Type', 'Date de création']);

            foreach ($clients as $client) {
                fputcsv($handle, [
                    $client->id,
                    $client->nom,
                    $client->prenom,
                    $client->telephone,
                    $client->adresse,
                    $client->statut,
                    $client->type,
                    $client->created_at,
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    elseif ($format === 'pdf') {
        $pdf = Pdf::loadView('clients.export', ['clients' => $clients]);
        return $pdf->download('clients.pdf');
    }

    else {
        return response()->json(['error' => 'Format non supporté'], 400);
    }
}


// exporter les client d'un utilisateur connecté au format CSV ou PDF
public function exportMesClients(Request $request)
{
    $format = $request->input('format', 'csv'); 

    $user = Auth::user();
    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $clients = Client::where('user_id', $user->id)->get();

    if ($format === 'csv') {
        $filename = 'mes_clients.csv';

        return response()->stream(function () use ($clients) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['ID', 'Nom', 'Prénom', 'Téléphone', 'Adresse', 'Statut', 'Type', 'Date de création']);

            foreach ($clients as $client) {
                fputcsv($handle, [
                    $client->id,
                    $client->nom,
                    $client->prenom,
                    $client->telephone,
                    $client->adresse,
                    $client->statut,
                    $client->type,
                    $client->created_at
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    elseif ($format === 'pdf') {
        $pdf = Pdf::loadView('clients.export', ['clients' => $clients]);
        return $pdf->download('mes_clients.pdf');
    }

    else {
        return response()->json(['error' => 'Format non supporté'], 400);
    }
}
// Générer des liens de contact pour WhatsApp 
public function contactLinks($id)
{
    $client = Client::find($id);
    if (!$client) {
        return response()->json(['error' => 'Client not found'], 404);
    }

    $telephone = preg_replace('/\D/', '', $client->telephone); 
    $whatsappMessage = "Bonjour $client->nom, je vous contacte concernant votre commande.";

    return response()->json([
        'whatsapp_link' => "https://wa.me/221$telephone?text=" . urlencode($whatsappMessage),
        'call_link' => "tel:+221$telephone",
    ]);
}


// Afficher les liens de contact pour tous les clients de l'utilisateur connecté
public function contactClients()
{
    $user = Auth::user();
    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    $clients = Client::where('user_id', $user->id)->get();
    $contactLinks = [];

    foreach ($clients as $client) {
        $vente = $client->ventes()->latest()->first();
        $telephone = preg_replace('/\D/', '', $client->telephone);

        if (!$vente) {
            // Sauter les clients sans vente
            continue;
        }

        $linkSatisfait = url("/reponse_vente/{$vente->id}/1");
        $linkNonSatisfait = url("/reponse_vente/{$vente->id}/0");
    
        $whatsappMessage = "Bonjour $client->nom, êtes-vous satisfait de votre commande ?\n\n"
            . "✅ Oui : $linkSatisfait\n"
            . "❌ Non : $linkNonSatisfait";

        $contactLinks[] = [
            'client_id' => $client->id,
            'client_nom' => $client->nom,
            'whatsapp_link' => "https://wa.me/221$telephone?text=" . urlencode($whatsappMessage),
            'call_link' => "tel:+221$telephone",
        ];
    }

    return response()->json([
        'status' => 'success',
        'contacts' => $contactLinks
    ]);
}
//Clients les plus fidèles
public function clientsFideles()
{
    $clients = Client::withCount('ventes')
        ->orderBy('ventes_count', 'desc')
        ->take(10)
        ->get();

    return response()->json($clients);
}
//Taux de satisfaction: Pourcentage de feedbacks positifs par rapport au total.
public function tauxSatisfaction()
{
    $ventes = DB::table('ventes')
        ->select(DB::raw('SUM(CASE WHEN reponse = 1 THEN 1 ELSE 0 END) as positif_count, COUNT(*) as total_count'))
        ->first();

    if ($ventes->total_count == 0) {
        return response()->json(['taux_satisfaction' => 0]);
    }

    $tauxSatisfaction = ($ventes->positif_count / $ventes->total_count) * 100;

    return response()->json(['taux_satisfaction' => $tauxSatisfaction]);
}
//Alertes et indicateurs critiques: Liste des clients inactifs, clients insatisfaits récurrents
public function clientsInsatisfaitsRecurrents()
{
    $clients = Client::whereHas('ventes.feedback', function ($query) {
        $query->where('satisfaite', 0); // feedback négatif
    })
    ->withCount(['ventes as feedback_negatif_count' => function ($query) {
        $query->whereHas('feedback', function ($subQuery) {
            $subQuery->where('satisfaite', 0);
        });
    }])
    ->having('feedback_negatif_count', '>=', 2)
    ->get();

    return response()->json([
        'status' => 'success',
        'clients_insatisfaits_recurrents' => $clients
    ]);
}
public function lancerConversationWhatsApp($id)
{
    $client = Client::find($id);
    
    if (!$client) {
        return response()->json(['error' => 'Client not found'], 404);
    }
    
    $telephone = preg_replace('/\D/', '', $client->telephone); // Nettoyer le numéro de téléphone
    
    return response()->json([
        'whatsapp_link' => "https://wa.me/221$telephone",
        'call_link' => "tel:+221$telephone",
    ]);
}

//le nombre de cliens ajouter par l'utilisateur connecté

public function nombreClients()
{
    $user = Auth::user();

    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $nombreClients = Client::where('user_id', $user->id)->count();

    return response()->json([
        'success' => true,
        'nombre_clients' => $nombreClients
    ]);
}
//afficher les trois clients les plus récents de l'utilisateur connecté
public function clientsRecents()
{
    $user = Auth::user();

    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $clients = Client::where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->take(3)
        ->get();

    return response()->json($clients);
}
}