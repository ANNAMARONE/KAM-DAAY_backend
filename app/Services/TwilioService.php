<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    public static function sendWhatsAppMessage($to, $message)
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $from = env('TWILIO_WHATSAPP_FROM');

        // Vérification détaillée
        if (!$sid) {
            throw new \Exception("TWILIO_SID manquant dans .env");
        }
        if (!$token) {
            throw new \Exception("TWILIO_AUTH_TOKEN manquant dans .env");
        }
        if (!$from) {
            throw new \Exception("TWILIO_WHATSAPP_FROM manquant dans .env");
        }

        try {
            $client = new Client($sid, $token);

            $response = $client->messages->create($to, [
                'from' => $from,
                'body' => $message
            ]);

            return $response->sid ?? null; // retourne l’ID du message si OK
        } catch (\Exception $e) {
            // Log de l’erreur pour debug
            \Log::error("Erreur Twilio : " . $e->getMessage());

            // On remonte l'erreur avec le message clair
            throw new \Exception("Échec d’envoi WhatsApp : " . $e->getMessage());
        }
    }
}