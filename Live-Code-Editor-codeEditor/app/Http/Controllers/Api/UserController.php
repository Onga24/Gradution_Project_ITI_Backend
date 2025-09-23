<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function search(Request $request) {
    $q = $request->query('q','');
    $user = $request->user();
    $users = \App\Models\User::where('id','!=',$user->id)
        ->when($q, fn($qBuilder)=>$qBuilder->where('name','like',"%{$q}%")->orWhere('email','like',"%{$q}%"))
        ->limit(30)->get(['id','name','email']);
    return response()->json(['success'=>true,'users'=>$users]);
}

}
