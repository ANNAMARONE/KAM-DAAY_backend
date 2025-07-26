<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vente;
use App\Models\Feedback;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            $data = $request->all();

            $message = $data['entry'][0]['changes'][0]['value']['messages'][0] ?? null;

            if (!$message || !isset($message['interactive']['button_reply']['id'])) {
                return response()->json(['message' => 'Aucun bouton cliqué détecté.'], 200);
            }

            $buttonId = $message['interactive']['button_reply']['id']; // ex: satisfait_31
            $from = $message['from']; // numéro client, ex: 221784615847

            // Extraction des données
            if (str_starts_with($buttonId, 'satisfait_')) {
                $venteId = (int) str_replace('satisfait_', '', $buttonId);
                $satisfait = true;
            } elseif (str_starts_with($buttonId, 'non_satisfait_')) {
                $venteId = (int) str_replace('non_satisfait_', '', $buttonId);
                $satisfait = false;
            } else {
                return response()->json(['message' => 'ID de bouton invalide'], 400);
            }

            // Vérifier si la vente existe
            $vente = Vente::find($venteId);
            if (!$vente) {
                return response()->json(['message' => 'Vente introuvable'], 404);
            }

            // Vérifier si feedback déjà donné
            if (Feedback::where('vente_id', $venteId)->exists()) {
                return response()->json(['message' => 'Feedback déjà enregistré'], 200);
            }

            // Enregistrement du feedback
            Feedback::create([
                'vente_id' => $venteId,
                'satisfait' => $satisfait,
            ]);

            // Envoyer une réponse au client via WhatsApp
            $token = env('WHATSAPP_TOKEN');
            $phoneId = env('WA_PHONE_ID');

            $messageText = $satisfait
                ? "🙏 Merci beaucoup pour votre retour positif !"
                : "😞 Merci pour votre retour. Nous sommes désolés et allons améliorer notre service.";

            $response = Http::withToken($token)->post("https://graph.facebook.com/v19.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $from,
                'type' => 'text',
                'text' => [
                    'body' => $messageText,
                ],
            ]);

            Log::info('Réponse client envoyée sur WhatsApp', [
                'to' => $from,
                'message' => $messageText,
                'response' => $response->json()
            ]);

            return response()->json(['message' => 'Feedback enregistré avec succès'], 201);

        } catch (\Exception $e) {
            Log::error('Erreur Webhook WhatsApp', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur serveur', 'error' => $e->getMessage()], 500);
        }
    }

    public function verify(Request $request)
    {
        $verifyToken = 'kamdaay_verification';

        $mode = $request->get('hub_mode');
        $token = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200);
        }

        return response('Unauthorized', 403);
    }
}