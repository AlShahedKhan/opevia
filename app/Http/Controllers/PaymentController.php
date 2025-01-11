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
     * Create a PaymentIntent and hold funds in escrow.
     */
    public function createPaymentIntent(Request $request, Client $client)
    {
        return $this->safeCall(function () use ($request, $client) {
            // Validate the request
            $request->validate([
                'worker_id' => 'required|exists:workers,id', // Ensure worker_id is valid
            ]);

            $workerId = $request->input('worker_id');

            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

            // Create a Stripe Customer
            $stripeCustomer = \Stripe\Customer::create([
                'name' => $client->full_name,
                'email' => $client->email,
            ]);

            // Create a PaymentIntent
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $client->amount * 100, // Convert amount to cents
                'currency' => 'usd',
                'payment_method_types' => ['card'],
                'capture_method' => 'manual',
                'customer' => $stripeCustomer->id,
            ]);

            // Save the PaymentIntent ID to the client record
            $client->payment_intent_id = $paymentIntent->id;
            if (!$client->save()) {
                return $this->errorResponse('Failed to save PaymentIntent ID.', 500);
            }

            // Save payment details in the payments table
            Payment::create([
                'payment_intent_id' => $paymentIntent->id,
                'client_id' => $client->id,
                'worker_id' => $workerId, // Save the worker_id here
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
                'customer' => $client->full_name,
                'payment_date' => now(),
            ]);

            return $this->successResponse('Payment Intent created successfully', [
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
            ]);
        });
    }

    /**
     * Confirm the PaymentIntent and attach a payment method.
     */
    public function confirmPaymentIntent(Request $request, Client $client)
    {
        return $this->safeCall(function () use ($request, $client) {
            if (!$client) {
                return $this->errorResponse('Client not found.', 404);
            }

            if (!$client->payment_intent_id) {
                return $this->errorResponse('No PaymentIntent ID found for this client.', 400);
            }

            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

            // Retrieve the PaymentIntent
            $paymentIntent = \Stripe\PaymentIntent::retrieve($client->payment_intent_id);

            // Attach a payment method
            $paymentMethodId = $request->input('payment_method_id');
            if (!$paymentMethodId) {
                return $this->errorResponse('Payment method ID is required.', 400);
            }

            $paymentIntent->payment_method = $paymentMethodId;
            $paymentIntent->save();

            // Confirm the PaymentIntent
            $paymentIntent->confirm();

            // Check PaymentIntent status
            if ($paymentIntent->status !== 'requires_capture') {
                return $this->errorResponse('Payment confirmation failed.', 400);
            }

            // Update the database with payment method and status
            $payment = Payment::where('payment_intent_id', $client->payment_intent_id)->first();
            if ($payment) {
                $payment->update([
                    'payment_method' => $paymentIntent->payment_method,
                    'payment_date' => now(),
                ]);
            }

            return $this->successResponse('Payment confirmed successfully', [
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
            ]);
        });
    }

    /**
     * Release payment to the worker.
     */
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
