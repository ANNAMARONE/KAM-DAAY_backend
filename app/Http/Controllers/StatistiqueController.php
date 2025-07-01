<?php
namespace App\Http\Controllers;
use App\Models\Client;
use App\Models\Vente;
use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class StatistiqueController extends Controller{
    public function statistiques()
    {
        $user = Auth::user();
    
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $nombreClients = Client::where('user_id', $user->id)->count();
    
        $revenusDuMois = DB::table('produit_ventes')
        ->join('ventes', 'produit_ventes.vente_id', '=', 'ventes.id')
        ->where('ventes.user_id', $user->id)
        ->whereBetween('ventes.created_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ])
        ->sum('produit_ventes.montant_total');

    
        $ventesAujourdhui = Vente::where('user_id', $user->id)
                                 ->whereDate('created_at', Carbon::today())
                                 ->count();
    
                                 $positifs = DB::table('feedback')
                                 ->join('ventes', 'feedback.vente_id', '=', 'ventes.id')
                                 ->where('ventes.user_id', $user->id)
                                 ->where('feedback.satisfait', 1)
                                 ->count();
                             
                             $totalFeedbacks = DB::table('feedback')
                                 ->join('ventes', 'feedback.vente_id', '=', 'ventes.id')
                                 ->where('ventes.user_id', $user->id)
                                 ->count();
                             
        $tauxSatisfaction = $totalFeedbacks > 0 ? round(($positifs / $totalFeedbacks) * 100, 2) : 0;
    
        // Tendances des 6 derniers mois
        $ventesParMois = DB::table('produit_ventes')
        ->join('ventes', 'produit_ventes.vente_id', '=', 'ventes.id')
        ->where('ventes.user_id', $user->id)
        ->where('ventes.created_at', '>=', Carbon::now()->subMonths(6))
        ->selectRaw('MONTH(ventes.created_at) as mois, SUM(produit_ventes.montant_total) as total')
        ->groupByRaw('MONTH(ventes.created_at)')
        ->pluck('total', 'mois');
    
    
        return response()->json([
            'nombre_clients' => $nombreClients,
            'revenus_du_mois' => $revenusDuMois,
            'ventes_aujourdhui' => $ventesAujourdhui,
            'taux_satisfaction' => $tauxSatisfaction,
            'ventes_par_mois' => $ventesParMois
        ]);
    }
}