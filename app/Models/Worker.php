<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Worker extends Model
{
    protected $table = 'workers';

    protected $fillable = [
        'company_name',
        'email',
        'contact_number',
        'service_location',
        'zip_code',
        'photos',
        'service_type',
        'description',
        'privacy_policy_agreement',
    ];

    protected $casts = [
        'photos' => 'array', // Cast photos field as an array
    ];
}
