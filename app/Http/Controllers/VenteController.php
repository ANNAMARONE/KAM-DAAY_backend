<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVenteRequest;
use App\Http\Requests\UpdateVenteRequest;
use App\Models\Client;
use App\Models\Produit;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
class VenteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ventes = Vente::with(['produits'])->get();
    
        return response()->json([
            'status' => 'success',
            'data' => $ventes
        ]);
    }
    
    //afficher les ventes par client
    public function ventesParClient($id)
    {
        $ventes = Vente::where('client_id', $id)->with('produits')->get();
    
        if ($ventes->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucune vente trouvée pour ce client.'
            ], 404);
        }
    
        return response()->json([
            'status' => 'success',
            'data' => $ventes
        ]);
    }
    //filtrer les ventes par date
    public function filterVenteByDate($date){
        $ventes=vente::whereDate('created_at',$date)->with('produits')->get();
        return response()->json([
            'status'=>'success',
            'data'=> $ventes
        ]);
    }
//noter une vente comme satisfaisante ou non sur la table feedback
public function noterVente($id, $satisfaite)
{
    $vente = Vente::find($id);

    if (!$vente) {
        return response()->json([
            'status' => 'error',
            'message' => 'Vente non trouvée'
        ], 404);
    }

    // Vérifie si un feedback existe déjà pour cette vente
    if ($vente->feedback) {
        return response()->json([
            'status' => 'error',
            'message' => 'Cette vente a déjà été notée.'
        ], 400);
    }

    // Créer le feedback
    $feedback = $vente->feedback()->create([
        'satisfait' => $satisfaite,
        'vente_id' => $vente->id
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Vente notée avec succès',
        'data' => $feedback
    ]);
}

//afficher les ventes notees non satisfaisantes d'un vedenteur spécifique
public function mesVentes(){
    $userId = Auth::id();

    $ventes = Vente::where('user_id', $userId)
        ->with(['produits', 'client', 'feedback']) // ⬅️ important ici
        ->get();

    if ($ventes->isEmpty()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Aucune vente trouvée pour cet utilisateur.'
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'data' => $ventes
    ]);
}


public function ventesNonSatisfaites(){
    
    $ventes=Vente::whereHas('feedback',function($query){
        $query->where('satisfait',0);
    })->get();
    return response()->json([
        'status'=>'success',
        'data'=>$ventes
    ]);
}
 
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    //stocker une nouvelle vente pour un client existant
    public function store(Request $request)
{
    DB::beginTransaction();

    $user = Auth::user();
    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    try {
        // Validation des données
        $validatedData = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'prenom' => 'required_without:client_id|string|max:255',
            'nom' => 'required_without:client_id|string|max:255',
            'telephone' => 'nullable|string|max:255',
            'adresse' => 'nullable|string|max:255',
            'type' => 'nullable|in:restaurateur,particulier,boutique',
            'produits' => 'required|array|min:1',
            'produits.*.produit_id' => 'required|exists:produits,id',
            'produits.*.quantite' => 'required|integer|min:1',
        ]);

        // Création ou récupération du client
        $client = !empty($validatedData['client_id'])
            ? Client::findOrFail($validatedData['client_id'])
            : Client::create([
                'prenom' => $validatedData['prenom'],
                'nom' => $validatedData['nom'],
                'telephone' => $validatedData['telephone'],
                'adresse' => $validatedData['adresse'],
                'type' => $validatedData['type'] ?? 'particulier',
                'statut' => 'actif',
                'user_id' => $user->id,
            ]);

        // Création de la vente
        $vente = Vente::create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);

        // Traitement des produits
        foreach ($validatedData['produits'] as $item) {
            $produit = Produit::findOrFail($item['produit_id']);
            $quantite = $item['quantite'];
            $prixUnitaire = $produit->prix_unitaire;
            $montant = $quantite * $prixUnitaire;

            if ($produit->stock < $quantite) {
                throw new \Exception("Stock insuffisant pour le produit {$produit->nom}");
            }

            $produit->decrement('stock', $quantite);

            $vente->produits()->attach($produit->id, [
                'quantite' => $quantite,
                'prix_unitaire' => $prixUnitaire,
                'montant_total' => $montant,
                'date_vente' => now(),
            ]);
        }

        DB::commit();

        // Envoi du message WhatsApp si le client a un téléphone
        if ($client->telephone) {
            $token = env('WHATSAPP_TOKEN');
            $phoneId = '709400618925318';

            // Format du numéro
            $clientPhone = preg_replace('/[^0-9]/', '', $client->telephone);
            if (!Str::startsWith($clientPhone, '221')) {
                $clientPhone = '221' . $clientPhone;
            }

            $messageData = [
                'messaging_product' => 'whatsapp',
                'to' => $clientPhone,
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'button',
                    'body' => [
                        'text' => "Bonjour {$client->prenom}, merci pour votre achat chez nous. Êtes-vous satisfait de la vente ?",
                    ],
                    'action' => [
                        'buttons' => [
                            [
                                'type' => 'reply',
                                'reply' => [
                                    'id' => 'satisfait_' . $vente->id,
                                    'title' => 'Satisfait',
                                ],
                            ],
                            [
                                'type' => 'reply',
                                'reply' => [
                                    'id' => 'non_satisfait_' . $vente->id,
                                    'title' => 'Non satisfait',
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            try {
                $response = Http::withToken($token)->post(
                    "https://graph.facebook.com/v19.0/{$phoneId}/messages",
                    $messageData
                );

                if ($response->successful()) {
                    Log::info('✅ Message WhatsApp envoyé avec succès', ['response' => $response->json()]);
                } else {
                    Log::error('❌ Échec de l’envoi WhatsApp', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'error' => $response->json(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('❌ Exception lors de l’envoi WhatsApp', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Vente enregistrée avec succès.',
            'vente' => $vente->load('client', 'produits'),
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('❌ Erreur dans la méthode store', ['exception' => $e]);
        return response()->json([
            'message' => 'Erreur lors de l’enregistrement de la vente.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    
    

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $vente = Vente::with('produits', 'client', 'feedback')->find($id);
    
        if (!$vente) {
            return response()->json([
                'status' => 'error',
                'message' => 'vente non trouvée'
            ], 404);
        }
    
        return response()->json([
            'status' => 'success',
            'data' => $vente
        ]);
    }
    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vente $vente)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVenteRequest $request, Vente $vente)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $vente = Vente::find($id);
    
        if (!$vente) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vente non trouvée'
            ], 404);
        }
    
        $vente->delete();
    
        return response()->json([
            'status' => 'success',
            'message' => 'Vente supprimée avec succès'
        ]);
    }
    

    public function noterParLien($id)
{
    $client = Client::find($id);
    if (!$client) {
        return response()->json(['error' => 'Client not found'], 404);
    }

    $telephone = preg_replace('/\D/', '', $client->telephone); 

   
    $vente = $client->ventes()->latest()->first();

    if (!$vente) {
        return response()->json(['error' => 'Aucune vente trouvée pour ce client'], 404);
    }

    
    $linkSatisfait = url("/reponse_vente/{$vente->id}/1");
    $linkNonSatisfait = url("/reponse_vente/{$vente->id}/0");

    // Message WhatsApp enrichi
    $whatsappMessage = "Bonjour $client->nom, êtes-vous satisfait de votre commande ?\n\n"
        . "Oui : $linkSatisfait\n"
        . "Non : $linkNonSatisfait";

    return response()->json([
        'whatsapp_link' => "https://wa.me/221$telephone?text=" . urlencode($whatsappMessage),
        'call_link' => "tel:+221$telephone",
    ]);
}

    public function noterParLienVente($vente_id, $satisfaite)
{
    $vente = Vente::find($vente_id);

    if (!$vente) {
        return response('<h2>Vente non trouvée</h2>', 404)
            ->header('Content-Type', 'text/html');
    }

    // Vérifier si un feedback existe déjà
    $feedback = $vente->feedback;

    if ($feedback) {
        $feedback->update(['satisfait' => $satisfaite]);
    } else {
        $vente->feedback()->create([
            'satisfaite' => $satisfaite,
        ]);
    }

    return response('<h2>Merci pour votre réponse !</h2>', 200)
        ->header('Content-Type', 'text/html');
}
//lancer une conversation WhatsApp avec le client

public function lancerConversationWhatsAppVente($id)
{
    $vente = Vente::with('client')->find($id);

    if (!$vente) {
        return response()->json(['error' => 'Vente introuvable.'], 404);
    }

    $client = $vente->client;

    if (!$client || empty($client->telephone)) {
        return response()->json(['error' => 'Client introuvable ou numéro manquant.'], 404);
    }

    // Nettoyer et vérifier le numéro
    $telephone = preg_replace('/\D/', '', $client->telephone);

    if (strlen($telephone) < 9) {
        return response()->json(['error' => 'Numéro de téléphone invalide.'], 400);
    }

    return response()->json([
        'whatsapp_link' => "https://wa.me/221$telephone",
        'call_link' => "tel:+221$telephone",
    ]);
}
public function nombreVentesAujourdHui()
{
    $user = Auth::user();

    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $aujourdhui = Carbon::today();

    $nombreVentes = Vente::where('user_id', $user->id)
                         ->whereDate('created_at', $aujourdhui)
                         ->count();

    return response()->json([
        'success' => true,
        'nombre_ventes_aujourdhui' => $nombreVentes
    ]);
}
public function revenusDuMois()
{
    try {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $debutMois = Carbon::now()->startOfMonth();
        $finMois = Carbon::now()->endOfMonth();

        $revenus = DB::table('produit_ventes')
            ->join('ventes', 'produit_ventes.vente_id', '=', 'ventes.id')
            ->where('ventes.user_id', $user->id)
            ->whereBetween('ventes.created_at', [$debutMois, $finMois])
            ->sum('produit_ventes.montant_total');

        return response()->json([
            'success' => true,
            'revenus_du_mois' => $revenus
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur revenusDuMois: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur interne serveur',
            'details' => $e->getMessage()
        ], 500);
    }
}
      // Statistiques globales
      public function stats()
      {
          return response()->json([
              'nombre_utilisateurs' => User::count(),
              'nombre_ventes' => Vente::count(),
              'nombre_produits' => Produit::count(),
              'nombre_vendeuses' =>User::count(),
              'nombre_client' => Client::count(),
          ]);
      }
      public function ventesParMois()
      {
          $ventes = Vente::selectRaw('MONTH(created_at) as mois_num, COUNT(*) as total')
              ->groupBy('mois_num')
              ->get()
              ->map(function ($item) {
                  $moisNom = Carbon::createFromDate(null, $item->mois_num)->locale('fr')->monthName;
                  return [
                      'mois' => ucfirst($moisNom),
                      'total' => $item->total,
                  ];
              });
      
          return response()->json($ventes);
      }
      

}