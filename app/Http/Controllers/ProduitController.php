<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProduitRequest;
use App\Http\Requests\UpdateProduitRequest;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class ProduitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //verifier si l'utilisateur est connecter
        $user = Auth::user();
        if(!$user) {
            return response()->json([
                'error' => 'Aucun utilisateur authentifié',
                'headers' => request()->headers->all()
            ], 401);
        }
        //Afficher les produit de l'utilisateur connecter
        $produits = Produit::where('user_id', $user->id)->get();
        return response()->json([
            'status' => 'success',
            'produits' => $produits,
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $produitData = $request->validate([
            'nom' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'prix_unitaire' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'unite'=> 'required|string|in:kg,litre,unite', // Assurez-vous que l'unité est valide
            
            
        ]);
        //recuperer l'utilis
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'error' => 'Aucun utilisateur authentifié',
                'headers' => $request->headers->all()
            ], 401);
        }
      
        // Si une image est présente, on la stocke d'abord
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
        }
    
        // Création du produit
        $produit = Produit::create([
            'nom' => $produitData['nom'],
            'image' => $imagePath,
            'prix_unitaire' => $produitData['prix_unitaire'],
            'stock' => $produitData['stock'],
            'unite' => $produitData['unite'],
            'user_id' => $user->id,
        ]);
    
        return response()->json([
            'status' => 'success',
            'produit' => $produit,
        ], 201);
    }
    

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $produit = Produit::find($id);
    
        if (!$produit) {
            return response()->json([
                'status' => false,
                'message' => 'Produit non trouvé'
            ], 404);
        }
    
        return response()->json([
            'status' => true,
            'data' => $produit
        ]);
    }
    

    /**
     * Show the form for editing the specified resource.
     */

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Produit $produit)
    {
        $request->validate([
            'nom' => 'string|max:255',
            'prix_unitaire' => 'numeric|min:0',
            'quantite_stock' => 'integer|min:0',
            'unite' => 'string|max:50',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);
        $user=Auth::id();
        // Vérifier si l'utilisateur connecté est le propriétaire
        if ($produit->user_id !==$user) {
            return response()->json([
                'status' => false,
                'message' => 'Non autorisé'
            ], 403);
        }
    
        // Mise à jour des données sauf image
        $produit->update($request->except('image'));
    
        // Gérer le fichier image s’il existe
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('produits', 'public');
            $produit->image = $imagePath;
            $produit->save();
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Produit mis à jour avec succès',
            'data' => $produit,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Produit $produit)
    {
        $user=Auth::id();
        if ($produit->user_id !== $user) {
            return response()->json([
                'status' => false,
                'message' => 'Non autorisé'
            ], 403);
        }
    
        $produit->delete();
    
        return response()->json([
            'status' => true,
            'message' => 'Produit supprimé avec succès'
        ]);
    }

    //mettre a jour l'stock
    public function updateStock(Request $request, Produit $produit)
{
    $request->validate([
        'stock' => 'required|integer|min:0',
    ]);
       $user=auth::id();
    if ($produit->user_id !==$user) {
        return response()->json(['message' => 'Non autorisé'], 403);
    }

    $produit->stock = $request->stock;
    $produit->save();

    return response()->json([
        'status' => true,
        'message' => 'Stock mis à jour avec succès',
        'data' => $produit
    ]);
}
public function afficherProduit()
{
    $produits = Produit::with('user')->get();

    return response()->json([
        'status' => 'success',
        'data' => $produits
    ]);
}

}