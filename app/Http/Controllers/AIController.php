<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;

use Illuminate\Http\Request;

class AIController extends Controller
{
    public function ask(Request $request)
    {
        $question = $request->input('message');
        $response = Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'Tu es un assistant vocal pour une plateforme citoyenne. Parle simplement.'],
                ['role' => 'user', 'content' => $question],
            ]
        ]);
        
        return response()->json([
            'full_response' => $response->json()
        ]);
        
    }
}