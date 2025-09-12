<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ZokoService
{
    public function sendMessage(string $to, string $message): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('ZOKO_API_KEY'),
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post(env('ZOKO_API_URL'), [
                "to"   => $to, 
                "type" => "text",
                "text" => [
                    "body" => $message
                ]
            ]);

            if ($response->successful()) {
                return [
                    "status" => "success",
                    "data"   => $response->json(),
                ];
            }

            return [
                "status"  => "error",
                "message" => "Échec d’envoi WhatsApp",
                "details" => $response->json(),
            ];
        } catch (\Exception $e) {
            return [
                "status"  => "error",
                "message" => "Erreur lors de l’envoi WhatsApp",
                "details" => $e->getMessage(),
            ];
        }
    }
}