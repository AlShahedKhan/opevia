<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'client_id',
        'worker_id',
        'payment_intent_id',
        'status',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // public function client()
    // {
    //     return $this->belongsTo(User::class, 'client_id');
    // }
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function clientWorkRequest()
    {
        return $this->belongsTo(Client::class, 'client_work_req_id');
    }
}
