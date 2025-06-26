<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\NouvelUtilisateurCree;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            // ✅ Validation ajustée (role et statut sont maintenant optionnels)
            $validatedData = $request->validate([
                'profile' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'username' => 'required|string|max:255',
                'password' => 'required|string|min:8|confirmed',
                'telephone' => 'required|unique:users,telephone|regex:/^([0-9\s\-\+\(\)]*)$/|min:9',
                'role' => 'nullable|in:admin,vendeuse', 
                'localite' => 'required|string|max:255',
                'statut' => 'nullable|in:actif,inactif', 
                'domaine_activite' => 'required|string|in:halieutique,Agroalimentaire,Artisanat local,Savons / Cosmétiques,Jus locaux',
                'GIE' => 'nullable|string|max:255', // GIE est optionnel
            ]);
    
            // 📁 Gestion de l'image
            $filename = null;
            if ($request->hasFile('profile')) {
                $file = $request->file('profile');
                $filename = time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images/profiles'), $filename);
            }
            if (!$filename) {
                return response()->json(['error' => 'Profile image is required'], 422);
            }
    
            // 🧠 Valeurs par défaut
            $role = $validatedData['role'] ?? 'vendeuse';
            $statut = $validatedData['statut'] ?? 'inactif';
    
            // 🧑 Création de l'utilisateur
            $user = User::create([
                'profile' => $filename,
                'username' => $validatedData['username'],
                'password' => bcrypt($validatedData['password']),
                'telephone' => $validatedData['telephone'],
                'role' => $role,
                'localite' => $validatedData['localite'],
                'statut' => $statut,
                'domaine_activite' => $validatedData['domaine_activite'],
                'GIE' => $validatedData['GIE'] ?? null,
            ]);
    
           
            $admin = User::where('role', 'admin')->first();
            if ($admin) {
                $admin->notify(new NouvelUtilisateurCree($user));
            }
    
            
            $user->assignRole($role);
    
            return response()->json(['message' => 'User registered successfully'], 201);
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }
    
    public function login(Request $request)
    {
       try{
         // Validate the request data
         $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials=request(['username', 'password']);
        if(!$token = auth('api')->attempt($credentials)){
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $user = auth('api')->user();
        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
        // Authentication failed, return error response
        
       }catch(\Exception $e){
        return response()->json(['error' => 'Login failed: ' . $e->getMessage()], 500); 
       }
    }
    public function logout(Request $request)
    {
        try {
            auth('api')->logout();
            return response()->json(['message' => 'Logout successful'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Logout failed: ' . $e->getMessage()], 500);
        }
    }
    public function user(Request $request)
    {
        try {
            $user = auth('api')->user();
            return response()->json(['user' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve user: ' . $e->getMessage()], 500);
        }
    }
    public function refresh(Request $request)
    {
        try {
            $token = JWTAuth::refresh();
            return response()->json(['token' => $token], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token refresh failed: ' . $e->getMessage()], 500);
        }
    }
    public function checkUniqueTelephone(Request $request)
    {
        try {
            $request->validate([
                'telephone' => 'required|unique:users,telephone|regex:/^([0-9\s\-\+\(\)]*)$/|min:9',
            ]);
            return response()->json(['message' => 'Telephone is unique'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Validation failed: ' . $e->getMessage()], 422);
        }
    }
    public function checkUniqueUsername(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|unique:users,username|max:255',
            ]);
            return response()->json(['message' => 'Username is unique'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Validation failed: ' . $e->getMessage()], 422);
        }
    }
    public function updateProfile(Request $request)
    {
        try {
            $user = auth('api')->user();
            $validatedData = $request->validate([
                'profile' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'username' => 'required|string|max:255',
                'telephone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9',
                'localite' => 'required|string|max:255',
                'statut' => 'required|in:actif,inactif',
                'domaine_activite' => 'required|string|in:halieutique,Agroalimentaire,Artisanat local,Savons / Cosmétiques,Jus locaux|max:255',
            ]);
            if($request->hasFile('profile')) {
                $file = $request->file('profile');
                $filname = time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images/profiles'), $filname);
                $user->profile = $filname;
            }
            $user->username = $validatedData['username'];
            $user->telephone = $validatedData['telephone'];
            $user->localite = $validatedData['localite'];
            $user->statut = $validatedData['statut'] ? 'actif' : 'inactif';
            $user->domaine_activite = $validatedData['domaine_activite'];
            if($request->role=='admin'){
                $user->assignRole('admin');
            } elseif ($request->role=='vendeuse') {
                $user->assignRole('vendeuse');
            }
            $user->save();
            return response()->json(['message' => 'Profile updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Profile update failed: ' . $e->getMessage()], 500);
        }
    }
       //demander un mot de passe oublié
       public function demandeMotDePasseOublie(Request $request)
       {
           $telephone = $request->input('telephone');
           Log::info("Demande mot de passe oublié reçue pour: $telephone");
       
           $utilisateur = User::where('telephone', $telephone)->first();
       
           if (!$utilisateur) {
               Log::warning("Utilisateur non trouvé pour téléphone: $telephone");
               return response()->json([
                   'status' => 'error',
                   'message' => 'Utilisateur non trouvé'
               ], 404);
           }
       
           // Générer un token sécurisé
           $token = Str::random(60);
           $hashedToken = hash('sha256', $token);
       
           // Enregistrer ou mettre à jour le token
           DB::table('password_resets')->updateOrInsert(
               ['telephone' => $telephone],
               [
                   'token' => $hashedToken,
                   'created_at' => Carbon::now()
               ]
           );
       
           // Lien de réinitialisation
           $resetLink = "http://localhost:5173/reset-password?token=$token&telephone=$telephone";
       
           // Message WhatsApp
           $nom = $utilisateur->name ?? 'cher utilisateur';
           $message = "Bonjour {$utilisateur->name}, cliquez ici pour réinitialiser votre mot de passe : $resetLink";
         
           $wa_link = "https://wa.me/221$telephone?text=" . urlencode($message);
       
           Log::info("Lien WhatsApp généré : " . $wa_link);
       
           return response()->json([
               'status' => 'success',
               'whatsapp_link' => $wa_link
           ]);
       }
       
       public function resetPassword(Request $request)
       {
           try {
               $request->validate([
                   'token' => 'required',
                   'telephone' => 'required',
                   'password' => 'required|string|min:8|confirmed',
               ]);
       
               // Récupération de l'entrée password_resets
               $reset = DB::table('password_resets')
                   ->where('telephone', $request->telephone)
                   ->first();
       
               // Vérification de l'existence et de la validité du token SHA256
               if (
                   !$reset ||
                   !hash_equals($reset->token, hash('sha256', $request->token))
               ) {
                   return response()->json(['error' => 'Token ou téléphone invalide'], 400);
               }
       
               // Récupération de l'utilisateur
               $user = User::where('telephone', $request->telephone)->first();
       
               if (!$user) {
                   return response()->json(['error' => 'Utilisateur non trouvé'], 404);
               }
       
               // Mise à jour du mot de passe (avec bcrypt)
               $user->password = bcrypt($request->password);
               $user->save();
       
               // Suppression de la demande de réinitialisation
               DB::table('password_resets')
                   ->where('telephone', $request->telephone)
                   ->delete();
       
               return response()->json(['message' => 'Mot de passe réinitialisé avec succès'], 200);
           } catch (\Exception $e) {
               return response()->json(['error' => 'Réinitialisation du mot de passe échouée: ' . $e->getMessage()], 500);
           }
       }
}