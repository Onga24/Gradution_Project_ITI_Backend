<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{
    public function autocomplete(Request $request)
    {
        $request->validate([
            'code' => 'required|string|min:1',
        ]);

        $apiKey = env('GROQ_API_KEY');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'    => 'llama-3.1-8b-instant',
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => 'You are a helpful coding assistant.'
                    ],
                    [
                        'role'    => 'user',
                        'content' => "Complete this code:\n\n" . $request->code
                    ],
                ],
                'max_tokens' => 150,
                'temperature' => 0.2,
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error'   => 'Groq API request failed',
                    'details' => $response->json(),
                ], 500);
            }

            $json = $response->json();
            $suggestion = $json['choices'][0]['message']['content'] ?? '';

            return response()->json([
                'suggestion' => $suggestion,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Server Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
