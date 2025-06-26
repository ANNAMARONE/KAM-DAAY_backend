<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVenteRequest;
use App\Http\Requests\UpdateVenteRequest;
use App\Models\Client;
use App\Models\Produit;
use App\Models\Vente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
class VenteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    $ventes=Vente::all();
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
    $ventes = Vente::where('user_id', $userId)->with('produits')->get();
    
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
            // Validation
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
    
            // Création ou récupération client
            if (!empty($validatedData['client_id'])) {
                $client = Client::findOrFail($validatedData['client_id']);
            } else {
                $client = Client::create([
                    'prenom' => $validatedData['prenom'],
                    'nom' => $validatedData['nom'],
                    'telephone' => $validatedData['telephone'],
                    'adresse' => $validatedData['adresse'],
                    'type' => $validatedData['type'] ?? 'particulier',
                    'statut' => 'actif',
                    'user_id' => $user->id,
                ]);
            }
    
            // Création de la vente
            $vente = Vente::create([
                'client_id' => $client->id,
                'user_id' => $user->id,
            ]);
    
            // Attacher les produits et gérer le stock
            foreach ($validatedData['produits'] as $item) {
                $produit = Produit::findOrFail($item['produit_id']);
                $quantite = $item['quantite'];
                $prixUnitaire = $produit->prix_unitaire;
                $montant = $quantite * $prixUnitaire;
                // Vérification du stock
                if ($produit->stock < $quantite) {
                    throw new \Exception("Stock insuffisant pour le produit {$produit->nom}");
                }
                $produit->stock -= $quantite;
                $produit->save();
    
                $vente->produits()->attach($produit->id, [
                    'quantite' => $quantite,
                    'prix_unitaire' => $prixUnitaire,
                    'montant_total' => $montant,
                    'date_vente' => now(),
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Vente enregistrée avec succès.',
                'vente' => $vente->load('client', 'produits'),
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
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
    public function destroy(Vente $vente)
    {
        $vente->delete();
        return response()->json([
            'status'=>'success',
            'message'=>'vente supprimée avec succès'
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

}