<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Message; 





class Project extends Model
{
    use HasFactory, softDeletes;

        protected $fillable = [
        'name',
        'owner_id',
        'invite_code',
        'code'
    ];

        public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

        public function members()
    {
        return $this->belongsToMany(User::class, 'project_users')
                    ->withPivot('role')
                    ->withTimestamps();
    }

public function messages()
{
    return $this->hasMany(Message::class);
}


}
