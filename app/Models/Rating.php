<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = ['worker_id', 'client_id', 'rating'];


    public function worker()
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
