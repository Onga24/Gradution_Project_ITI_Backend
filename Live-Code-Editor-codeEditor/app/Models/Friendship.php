<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Friendship extends Model {
    protected $fillable = ['requester_id','requested_id','status'];
    public function requester() { return $this->belongsTo(User::class,'requester_id'); }
    public function requested() { return $this->belongsTo(User::class,'requested_id'); }
}

