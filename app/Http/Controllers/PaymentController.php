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


    public function releasePayment(Request $request, Client $client)
    {
        return $this->safeCall(function () use ($request, $client) {
            if (!$client) {
                return $this->errorResponse('Client not found.', 404);
            }

            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

            $paymentIntent = \Stripe\PaymentIntent::retrieve($client->payment_intent_id);

            if ($paymentIntent->status !== 'requires_capture') {
                return $this->errorResponse('PaymentIntent is not ready for capture.', 400);
            }

            // Capture the PaymentIntent
            $paymentIntent->capture();

            // Update the payment details in the database
            $payment = Payment::where('payment_intent_id', $client->payment_intent_id)->first();
            $payment->update([
                'payment_method' => $paymentIntent->payment_method, // Stripe payment method ID
            ]);

            return $this->successResponse('Payment released successfully');
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
