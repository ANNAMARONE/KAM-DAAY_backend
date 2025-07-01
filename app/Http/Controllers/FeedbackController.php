<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedbackRequest;
use App\Http\Requests\UpdateFeedbackRequest;
use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(StoreFeedbackRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Feedback $feedback)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Feedback $feedback)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFeedbackRequest $request, Feedback $feedback)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Feedback $feedback)
    {
        //
    }

    public function getFeedbacksRecents()
    {
        $user = Auth::user();
    
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        // Inclure vente + client à travers vente
        $feedbacks = Feedback::with('vente.client') // ✅ Eager load en profondeur
            ->whereHas('vente', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    
        return response()->json($feedbacks);
    }
    
    public function tauxSatisfactionPositif()
    {
        $user = Auth::user();
    
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        // Nombre de feedbacks positifs liés aux ventes de l'utilisateur
        $nombrePositifs = Feedback::whereHas('vente', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('satisfait', 1)->count();
    
        // Nombre total de feedbacks liés aux ventes de l'utilisateur
        $totalFeedbacks = Feedback::whereHas('vente', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();
    
        // Calcul du taux
        $taux = $totalFeedbacks > 0 
            ? round(($nombrePositifs / $totalFeedbacks) * 100, 2)
            : 0;
    
        return response()->json([
            'success' => true,
            'taux_satisfaction_positif' => $taux
        ]);
    }
    
    

}