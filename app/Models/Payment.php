<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_intent_id',
        'client_id',
        'worker_id',
        'currency',
        'amount',
        'payment_method',
        'description',
        'customer',
        'payment_date',
        'refund_date',
        'refund_reason',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }
}
