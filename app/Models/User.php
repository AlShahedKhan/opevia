<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',       // User Name
        'email',      // Email Address
        'password',   // Password
        'role',       // Role: 'client' or 'worker'
        'is_admin',   // Admin flag
    ];

    protected $hidden = [
        'password',         // Hide password
        'remember_token',   // Hide remember token
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Relationship: User has one Client.
     */
    public function client()
    {
        return $this->hasOne(Client::class, 'user_id');
    }

    /**
     * Relationship: User has one Worker.
     */
    public function worker()
    {
        return $this->hasOne(Worker::class, 'user_id');
    }

    /**
     * Get the identifier for JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Get custom claims for JWT.
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'is_admin' => $this->is_admin,
        ];
    }
}
