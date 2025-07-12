<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GestionUtilisateur extends Controller
{
    //afficher la liste des utilisateurs
    public function index()
    {
        $utilisateurs = User::where('role', 'vendeuse')->get();
    
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
        $utilisateur = User::with([
            'produits',
            'ventes.produits',
            'clients',
            
        ])->find($id);
    
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
     try{
        $utilisateur = User::find($id);
   
        if (!$utilisateur) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }
    
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'telephone' => 'required|string|max:20|unique:users,telephone,' . $id,
            'localite' => 'nullable|string|max:255',
             'statut' => 'required|in:actif,inactif',
            'domaine_activite' => 'nullable|string|max:255',
            'GIE' => 'nullable|string|max:255',
           
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
    
        // Gérer le profil si une nouvelle image est envoyée
        if ($request->hasFile('profile')) {
            $path = $request->file('profile')->store('profiles', 'public');
            $utilisateur->profile = $path;
        }
    
        $utilisateur->username = $request->username;
        $utilisateur->telephone = $request->telephone;
        $utilisateur->localite = $request->localite;
        $utilisateur->statut = $request->statut;
        $utilisateur->domaine_activite = $request->domaine_activite;
        $utilisateur->GIE = $request->GIE;
        
    
        $utilisateur->save();
    
        return response()->json([
            'status' => 'success',
            'message' => 'Compte vendeuse mis à jour avec succès',
            'data' => $utilisateur
        ]);
     }catch(\Exception $e){
        return response()->json([
            'status' => 'error',
            'message' => 'Erreur lors de la mise à jour de l\'utilisateur: ' . $e->getMessage()
        ], 500);
     }
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
        $query = trim($request->input('q'));
    
        if (empty($query)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Veuillez fournir un terme de recherche.'
            ], 400);
        }
    
        $utilisateurs = User::where('username', 'like', '%' . $query . '%')
            
            ->get();
    
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