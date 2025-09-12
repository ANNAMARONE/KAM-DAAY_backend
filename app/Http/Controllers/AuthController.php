<?php

namespace App\Http\Controllers;
use App\Services\ZokoService;
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
use App\Services\TwilioService;
class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            // ✅ Validation
            $validatedData = $request->validate([
                'username' => 'required|string|max:255',
                'password' => 'required|string|min:8|confirmed',
                'telephone' => 'required|unique:users,telephone|regex:/^[0-9]{9,15}$/',
                'role' => 'nullable|in:admin,vendeuse',
                'statut' => 'nullable|in:actif,inactif',
                'domaine_activite' => 'required|string|in:halieutique,Agroalimentaire,Artisanat local,Savons / Cosmétiques,Jus locaux',
                'GIE' => 'nullable|string|max:255',
            ]);
    
            // ✅ Gestion image
            $filename = null;
            if ($request->hasFile('profile')) {
                $file = $request->file('profile');
                $filename = time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images/profiles'), $filename);
            }
    
            $role = $validatedData['role'] ?? 'vendeuse';
            $statut = $validatedData['statut'] ?? 'inactif';
    
            // ✅ Normalisation téléphone (ajout indicatif 221 si manquant)
            $phone = preg_replace('/[^0-9]/', '', $validatedData['telephone']);
            if (!\Str::startsWith($phone, '221')) {
                $phone = '221' . $phone;
            }
    
            // ✅ Création utilisateur
            $user = User::create([
                'profile' => $filename,
                'username' => $validatedData['username'],
                'password' => bcrypt($validatedData['password']),
                'telephone' => $phone,
                'role' => $role,
                'localite' => $request->input('localite', 'mbour'),
                'statut' => $statut,
                'domaine_activite' => $validatedData['domaine_activite'],
                'GIE' => $validatedData['GIE'] ?? null,
            ]);
    
            // ✅ Notifier admin
            $admin = User::where('role', 'admin')->first();
            if ($admin) {
                $admin->notify(new NouvelUtilisateurCree($user));
            }
    
            $user->assignRole($role);
    
            // ✅ Envoi WhatsApp via Zoko
            $zoko = new \App\Services\ZokoService();
            $clientPhone = "221" . preg_replace('/[^0-9]/', '', $validatedData['telephone']); // format international
            $message = "Bonjour {$user->username}, bienvenue sur notre plateforme !";
            
            $sendResult = $zoko->sendMessage($clientPhone, $message);
            
            if ($sendResult['status'] === 'error') {
                return response()->json([
                    'message' => 'Utilisateur enregistré, mais WhatsApp non envoyé',
                    'whatsapp_error' => $sendResult['details']
                ], 201);
            }
    
            try {
                $zoko->sendMessage($clientPhone, $message);
    
                return response()->json([
                    'message' => 'Utilisateur enregistré avec succès, WhatsApp envoyé'
                ], 201);
    
            } catch (\Exception $e) {
                // Cas où le user est créé mais pas de WhatsApp envoyé
                return response()->json([
                    'message' => 'Utilisateur enregistré, mais WhatsApp non envoyé',
                    'whatsapp_error' => $e->getMessage()
                ], 201);
            }
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Échec de l\'inscription : ' . $e->getMessage()
            ], 500);
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
            $user = auth('api')->user(); // Récupère l'utilisateur connecté
    
            auth('api')->logout();
    
            // ✅ Envoyer un message WhatsApp après déconnexion
            if ($user) {
                $message = "Bonjour {$user->username}, vous vous êtes déconnecté avec succès.";
                TwilioService::sendWhatsAppMessage($user->telephone, $message);
            }
    
            return response()->json(['message' => 'Logout successful, WhatsApp sent'], 200);
    
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
    //    public function demandeMotDePasseOublie(Request $request)
    //    {
    //        $telephone = $request->input('telephone');
    //        Log::info("Demande mot de passe oublié reçue pour: $telephone");
       
    //        $utilisateur = User::where('telephone', $telephone)->first();
       
    //        if (!$utilisateur) {
    //            Log::warning("Utilisateur non trouvé pour téléphone: $telephone");
    //            return response()->json([
    //                'status' => 'error',
    //                'message' => 'Aucun utilisateur trouvé avec ce numéro.'
    //            ], 404);
    //        }
       
    //        $token = Str::random(60);
    //        $hashedToken = hash('sha256', $token);
       
    //        DB::table('password_resets')->updateOrInsert(
    //            ['telephone' => $telephone],
    //            [
    //                'token' => $hashedToken,
    //                'created_at' => Carbon::now()
    //            ]
    //        );
       
    //        return response()->json([
    //            'status' => 'success',
    //            'message' => 'Token généré avec succès.',
    //            'token' => $token,
    //            'telephone' => $telephone
    //        ]);
    //    }
       
    //    public function resetPassword(Request $request)
    //    {
    //        $request->validate([
    //            'token' => 'required',
    //            'telephone' => 'required',
    //            'password' => 'required|string|min:8|confirmed',
    //        ]);
       
    //        $reset = DB::table('password_resets')
    //            ->where('telephone', $request->telephone)
    //            ->first();
       
    //        if (
    //            !$reset ||
    //            !hash_equals($reset->token, hash('sha256', $request->token))
    //        ) {
    //            return response()->json([
    //                'status' => 'error',
    //                'message' => 'Token ou téléphone invalide.'
    //            ], 400);
    //        }
       
    //        $user = User::where('telephone', $request->telephone)->first();
       
    //        if (!$user) {
    //            return response()->json([
    //                'status' => 'error',
    //                'message' => 'Utilisateur non trouvé.'
    //            ], 404);
    //        }
       
    //        $user->password = bcrypt($request->password);
    //        $user->save();
       
    //        DB::table('password_resets')
    //            ->where('telephone', $request->telephone)
    //            ->delete();
       
    //        return response()->json([
    //            'status' => 'success',
    //            'message' => 'Mot de passe réinitialisé avec succès.'
    //        ]);
    //    }
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string|exists:users,telephone',
        ]);
    
        $user = User::where('telephone', $request->telephone)->first();

    
        // Générer un token sécurisé
        $token = Str::random(60);
    
        // Stocker dans password_resets (update ou insert)
        DB::table('password_resets')->updateOrInsert(
            ['telephone' => $request->telephone],
            [
                'token' => $token,
                'created_at' => now()
            ]
        );
    
        // Générer le lien de réinitialisation (exemple vers ton frontend)
        $link = config('app.frontend_url') . "/reset-password?token={$token}&telephone={$user->telephone}";
    
        // Formater le numéro pour Twilio (E.164)
        $clientPhone = preg_replace('/[^0-9]/', '', $user->telephone);
        if (!Str::startsWith($clientPhone, '221')) {
            $clientPhone = '221' . $clientPhone;
        }
        $clientPhone = 'whatsapp:+' . $clientPhone;
        
    
        // Envoyer le lien via WhatsApp
        TwilioService::sendWhatsAppMessage(
            $clientPhone,
            "Bonjour {$user->username}, utilisez ce lien pour réinitialiser votre mot de passe (valide 5 minutes) : $link"
        );
    
        return response()->json([
            'status' => 'success',
            'message' => 'Lien de réinitialisation envoyé via WhatsApp',
            'link' => $link // optionnel pour debug
        ]);
    }
    

    /**
     * Réinitialiser le mot de passe
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'telephone' => 'required|string|exists:users,telephone',
            'password' => 'required|string|confirmed|min:6'
        ]);

        // Récupérer l'entrée password_resets
        $reset = DB::table('password_resets')->where('telephone', $request->telephone)->first();

        if (!$reset || !hash_equals($reset->token, $request->token)) {
            return response()->json(['message' => 'Lien invalide'], 400);
        }

        // Vérifier expiration du token (5 minutes)
        if (Carbon::parse($reset->created_at)->addMinutes(5)->isPast()) {
            return response()->json(['message' => 'Lien expiré'], 400);
        }

        // Réinitialiser le mot de passe
        $user = User::where('telephone', $request->telephone)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Supprimer le token
        DB::table('password_resets')->where('telephone', $request->telephone)->delete();

        return response()->json([
            'message' => 'Mot de passe réinitialisé avec succès'
        ]);
    }

    /**
     * Vérifier la validité du token (optionnel pour GET sur Insomnia)
     */
    public function checkToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'telephone' => 'required|string|exists:users,telephone'
        ]);

        $reset = DB::table('password_resets')->where('telephone', $request->telephone)->first();

        if (!$reset || !hash_equals($reset->token, $request->token)) {
            return response()->json(['message' => 'Token invalide'], 400);
        }

        if (Carbon::parse($reset->created_at)->addMinutes(5)->isPast()) {
            return response()->json(['message' => 'Token expiré'], 400);
        }

        return response()->json([
            'message' => 'Token valide',
            'telephone' => $request->telephone
        ]);
    }
}