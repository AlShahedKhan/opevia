<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Worker extends Model
{
    use HasFactory, Notifiable;
    protected $table = 'workers';

    protected $fillable = [
        'user_id',               // Foreign key for users table
        'company_name',          // Company Name
        'email',                 // Email Address
        'contact_number',        // Contact Number
        'service_location',      // Service Location
        'zip_code',              // ZIP Code
        'photos',                // Photos field
        'service_type',          // Service Type
        'description',           // Description
        'privacy_policy_agreement', // Privacy Policy Agreement
    ];

    protected $casts = [
        'photos' => 'array', // Cast photos field as an array
    ];

    /**
     * Relationship: Worker belongs to a User.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
