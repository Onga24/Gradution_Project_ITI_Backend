<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConversationMessageController extends Controller
{
    public function store(Request $request, \App\Models\Conversation $conversation) {
    $data = $request->validate(['body'=>'required|string']);
    $user = $request->user();
    if (!$conversation->users()->where('user_id',$user->id)->exists()) {
        return response()->json(['message'=>'Not authorized'],403);
    }
    $msg = $conversation->messages()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'body' => $data['body']
    ]);
    $msg->load('user');
    return response()->json(['success'=>true,'message'=>$msg],201);
}

}
