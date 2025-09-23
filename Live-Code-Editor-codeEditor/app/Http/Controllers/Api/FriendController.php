<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function store(Request $request) {
    $data = $request->validate(['requested_id'=>'required|exists:users,id']);
    $me = $request->user();
    $otherId = (int)$data['requested_id'];
    if ($me->id === $otherId) return response()->json(['message'=>'Cannot add yourself'],422);

    // check existing pair (bidirectional)
    $exist = \App\Models\Friendship::where(function($q)use($me,$otherId){
        $q->where('requester_id',$me->id)->where('requested_id',$otherId);
    })->orWhere(function($q)use($me,$otherId){
        $q->where('requester_id',$otherId)->where('requested_id',$me->id);
    })->first();

    if ($exist && $exist->status === 'accepted') {
        // find existing conversation between users
        $conv = \App\Models\Conversation::where('is_direct',true)
            ->whereHas('users', fn($q)=> $q->where('user_id',$me->id))
            ->whereHas('users', fn($q)=> $q->where('user_id',$otherId))
            ->first();
        return response()->json(['success'=>true,'friendship'=>$exist,'conversation'=>$conv]);
    }

    // create friendship (demo: accepted directly)
    $friend = \App\Models\Friendship::create([
        'requester_id' => $me->id,
        'requested_id' => $otherId,
        'status' => 'accepted'
    ]);

    // create direct conversation
    $conversation = \App\Models\Conversation::create(['is_direct'=>true]);
    $conversation->users()->attach([$me->id, $otherId]);

    $conversation->load('users');

    return response()->json(['success'=>true,'friendship'=>$friend,'conversation'=>$conversation], 201);
}

}
