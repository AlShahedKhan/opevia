<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\Service;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'image',                // Profile image
        'first_name',           // First name
        'last_name',            // Last name
        'email',                // Email address
        'phone',                // Phone number
        'location',             // Location
        'service_type',         // Type of service offered
        'work_experience',      // Work experience
        'describe_yourself',    // Self-description
        'password',             // Password
        'role',                 // Role: 'client' or 'worker'
        'is_admin',             // Admin flag
    ];

    // Hidden attributes for arrays
    protected $hidden = [
        'password',         // Hide password
        'remember_token',   // Hide remember token
    ];

    // Attribute casting
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

    /**
     * Relationship: User can book many services as a client.
     */
    public function bookedServices()
    {
        return $this->hasMany(Service::class, 'client_id');
    }

    /**
     * Relationship: User can provide many services as a worker.
     */
    public function providedServices()
    {
        return $this->hasMany(Service::class, 'worker_id');
    }

    /**
     * Relationship: User can give ratings as a client.
     */
    public function givenRatings()
    {
        return $this->hasMany(Rating::class, 'client_id');
    }

    /**
     * Relationship: User can receive ratings as a worker.
     */
    public function receivedRatings()
    {
        return $this->hasMany(Rating::class, 'worker_id');
    }
}
