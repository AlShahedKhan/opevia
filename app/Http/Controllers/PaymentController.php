<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Worker;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\HandlesApiResponse;

class PaymentController extends Controller
{
    use HandlesApiResponse;

    public function index()
    {
        return $this->safeCall(function () {
            $user = Auth::user();

            // Check if the user is an admin
            if (!$user || !$user->is_admin) {
                return $this->errorResponse('Unauthorized access', 403);
            }

            $payments = Payment::with(['client', 'worker'])->get();
            return $this->successResponse('Payments fetched successfully', [
                'data' => $payments,
            ]);
        });
    }


  
    /**
     * Refund a PaymentIntent for a worker.
     */
    public function refundPayment(Request $request, Worker $worker)
    {
        return $this->safeCall(function () use ($request, $worker) {
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

            $request->validate([
                'payment_intent_id' => 'required|string',
                'refund_reason' => 'nullable|string',
            ]);

            $paymentIntent = \Stripe\PaymentIntent::retrieve($request->payment_intent_id);

            $refund = \Stripe\Refund::create([
                'payment_intent' => $request->payment_intent_id,
            ]);

            $payment = Payment::where('payment_intent_id', $request->payment_intent_id)->first();
            $payment->update([
                'refund_date' => now(),
                'refund_reason' => $request->refund_reason,
            ]);

            return $this->successResponse('Refund processed successfully');
        });
    }
}
