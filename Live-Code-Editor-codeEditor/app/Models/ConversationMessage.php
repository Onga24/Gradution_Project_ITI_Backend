<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ConversationMessage extends Model {
    protected $table = 'conversation_messages';
    protected $fillable = ['conversation_id','user_id','body'];
    public function user() { return $this->belongsTo(User::class); }
    public function conversation() { return $this->belongsTo(Conversation::class); }
}

