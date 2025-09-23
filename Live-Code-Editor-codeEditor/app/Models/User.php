<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Otp;
use Illuminate\Database\Eloquent\SoftDeletes;



class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, softDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function otps()
{
    return $this->hasMany(Otp::class);
}

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }


        public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_users')->withPivot('role')->withTimestamps();
    }


    public function conversations() {
    return $this->belongsToMany(\App\Models\Conversation::class,'conversation_user')->withTimestamps();
}
public function friendshipsRequested() {
    return $this->hasMany(\App\Models\Friendship::class,'requester_id');
}
public function friendshipsReceived() {
    return $this->hasMany(\App\Models\Friendship::class,'requested_id');
}



}
