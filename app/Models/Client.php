<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'full_name',
        'email',
        'contact_number',
        'service_location',
        'zip_code',
        'photos',
        'start_time',
        'end_time',
        'amount',
        'description',
        'privacy_policy_agreement',
    ];

    protected $casts = [
        'photos' => 'array', // Cast photos field as an array
    ];
}

