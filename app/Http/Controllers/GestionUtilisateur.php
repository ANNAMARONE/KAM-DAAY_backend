<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class GestionUtilisateur extends Controller
{
    //afficher la liste des utilisateurs
    public function index()
    {
       $utilisateurs =User::all();
       return response()->json([
           'status' => 'success',
           'data' => $utilisateurs
       ]);
    }
    //activer un utilisateur
    public function activerUtilisateur($id)
    {
        $utilisateur = User::find($id);
        if (!$utilisateur) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }
    
        $utilisateur->statut= 'actif';
        $utilisateur->save();
    
        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur activé avec succès'
        ]);
    }
    
    //désactiver un utilisateur
    public function desactiverUtilisateur($id)
    {
        $utilisateur = User::find($id);
        if (!$utilisateur) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }
    
        $utilisateur->statut = 'inactif';
        $utilisateur->save();
    
        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur désactivé avec succès'
        ]);
    }
    
    //supprimer un utilisateur
    public function supprimerUtilisateur($id)
    {
        $utilisateur = User::find($id);
        if (!$utilisateur) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        $utilisateur->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }
    //afficher un utilisateur
    public function show($id)
    {
        $utilisateur = User::find($id);
        if (!$utilisateur) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $utilisateur
        ]);
    }
   //modifier un utilisateur
    public function update(Request $request, $id)
    {
        $utilisateur = User::find($id);
        if (!$utilisateur) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        $utilisateur->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur mis à jour avec succès',
            'data' => $utilisateur
        ]);
    }
    //verifier si un utilisateur est actif
    public function isActif($id){
        $utilisateur=User::find($id);
        if(!$utilisateur){
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }
        if($utilisateur->statut === 'actif'){
            return response()->json([
                'status' => 'success',
                'message' => 'Utilisateur actif'
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur inactif'
            ]);
        }
    }
    //rechercher un utilisateur par son nom
    public function rechercheUtilisateur(Request $request)
    {
        $nom = $request->input('nom');
    
        if (!$nom) {
            return response()->json([
                'status' => 'error',
                'message' => 'Veuillez fournir un nom à rechercher.'
            ], 400);
        }
    
        $utilisateurs = User::where('username', 'like', '%' . $nom . '%')->get();
    
        if ($utilisateurs->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucun utilisateur trouvé.'
            ], 404);
        }
    
        return response()->json([
            'status' => 'success',
            'data' => $utilisateurs
        ]);
    }
    
// exporter la lists des utilisateurs 
public function exportUtilisateurs()
{
    $utilisateurs = User::all();
    if ($utilisateurs->isEmpty()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Aucun utilisateur à exporter'
        ], 404);
    }

    $csvFileName = 'utilisateurs_' . date('Y-m-d_H-i-s') . '.csv';
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $csvFileName . '"',
    ];

    return response()->stream(function () use ($utilisateurs) {
        $handle = fopen('php://output', 'w');

        // En-têtes CSV
        fputcsv($handle, ['ID', 'Nom', 'Adresse', 'Téléphone', 'Statut']);

        // Lignes
        foreach ($utilisateurs as $utilisateur) {
            fputcsv($handle, [
                $utilisateur->id,
                $utilisateur->username,
                $utilisateur->localite,
                $utilisateur->telephone,
                $utilisateur->statut,
            ]);
        }

        fclose($handle);
    }, 200, $headers);
}

   //notifier l'admin lors de la creation d'un nouvel utilisateur
    
}