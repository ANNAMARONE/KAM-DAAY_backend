<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
      try{
        // Validate the request data
        $validatedData = $request->validate([
            'profile' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'telephone' => 'required|unique:users,telephone|regex:/^([0-9\s\-\+\(\)]*)$/|min:9',
            'role' => 'required|in:admin,vendeuse',
            'localite' => 'required|string|max:255',
            'statut' => 'required|in:actif,inactif',
            'domaine_activite' => 'required|string|max:255',
        ]);
       $filname=null;
       if($request->hasFile('profile')) {
            // Store the profile image
            $file = $request->file('profile');
            $filname = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/profiles'), $filname);
        }
        // Create the user
       $user = User::create([
            'profile' => $filname,
            'username' => $validatedData['username'],
            'password' => bcrypt($validatedData['password']),
            'telephone' => $validatedData['telephone'],
            'role' => $validatedData['role'],
            'localite' => $validatedData['localite'],
            'statut' => $validatedData['statut'] ? 'actif' : 'inactif',
            'domaine_activite' => $validatedData['domaine_activite'],
        ]);

        if($request->role=='admin'){
            $user->assignRole('admin');
        } elseif ($request->role=='vendeuse') {
            $user->assignRole('vendeuse');
        }
        // Return a response
        return response()->json(['message' => 'User registered successfully'], 201);
    } catch(\Exception $e){
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
                'domaine_activite' => 'required|string|max:255',
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
    
}