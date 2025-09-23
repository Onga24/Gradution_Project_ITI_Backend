<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model {
    protected $fillable = ['title','is_direct'];
    public function users() { return $this->belongsToMany(User::class,'conversation_user')->withTimestamps(); }
    public function messages() { return $this->hasMany(ConversationMessage::class); }
}
