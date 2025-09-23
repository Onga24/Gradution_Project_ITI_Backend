<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class ChatAssistantController extends Controller
{
     public function handleChat(Request $request)
    {
        $validatedData = $request->validate([
            'message' => 'required|string',
            'currentFile' => 'nullable|array',
            'allFiles' => 'nullable|array',
        ]);

        $userMessage = $validatedData['message'];
        $currentFile = $validatedData['currentFile'] ?? null;
        $allFiles = $validatedData['allFiles'] ?? [];
        $fileExtension = pathinfo($currentFile['name'] ?? '', PATHINFO_EXTENSION);
        $content = $currentFile['content'] ?? '';

        // Prepare the prompt for the AI
        $prompt = "You are an AI coding assistant. You can provide code tips, analyze code, and debug issues.
        The user is working in a collaborative coding environment.
        The current active file is named: '{$currentFile['name']}'.
        Here is the content of the current file:
        
        -- START FILE CONTENT --
        {$content}
        -- END FILE CONTENT --
        
        The user's message is: '{$userMessage}'.
        
        Based on the user's message and the provided context, provide a helpful and concise response. Use markdown for formatting.";

        // Call the OpenAI API
        try {
            $result = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini', // or another suitable model like gpt-4-turbo
                'messages' => [
                    ['role' => 'system', 'content' => $prompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

            $aiResponse = $result->choices[0]->message->content;

            return response()->json(['message' => $aiResponse]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error communicating with the AI service. Please check your API key and network connection.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
