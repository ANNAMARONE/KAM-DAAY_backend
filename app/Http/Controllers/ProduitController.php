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
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $produitData = $request->validate([
            'nom' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
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
    public function show(Produit $produit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Produit $produit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProduitRequest $request, Produit $produit)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Produit $produit)
    {
        //
    }
}