<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'user_id',                  // Foreign key for users table
        'worker_id',                // Foreign key for workers table
        'full_name',                // Full Name
        'email',                    // Email Address
        'contact_number',           // Contact Number
        'service_location',         // Service Location
        'zip_code',                 // ZIP Code
        'photos',                   // Photos field
        'start_time',               // Start Time
        'end_time',                 // End Time
        'amount',                   // Amount
        'description',              // Description
        'privacy_policy_agreement', // Privacy Policy Agreement
        'payment_intent_id',        // Stripe PaymentIntent ID
    ];

    protected $casts = [
        'photos' => 'array', // Cast photos field as an array
    ];

    /**
     * Relationship: Client belongs to a User.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }
}
