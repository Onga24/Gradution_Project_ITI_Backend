<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Project;

use Illuminate\Http\Request;

class MessageController extends Controller
{
        public function index(Request $request, Project $project)
    {
        $user = $request->user();

        $isMember = $project->members()->where('user_id', $user->id)->exists();
        if (!$isMember && $user->role !== 'admin') {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        $messages = $project->messages()->with('user')->orderBy('created_at')->get();

        return response()->json(['success' => true, 'messages' => $messages]);
    }

    public function store(Request $request, Project $project)
    {
        $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $user = $request->user();

        $isMember = $project->members()->where('user_id', $user->id)->exists();
        if (!$isMember && $user->role !== 'admin') {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        $message = $project->messages()->create([
            'user_id' => $user->id,
            'body' => $request->input('body'),
        ]);

        $message->load('user');

        return response()->json(['success' => true, 'message' => $message], 201);
    }

}
