<?php

namespace App\Http\Controllers;

use Stripe\Stripe;
use App\Models\Client;
use App\Models\Worker;
use Stripe\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Notifications\PaymentHeldNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Notifications\PaymentReleasedNotification;

class PaymentController extends Controller
{
    /**
     * Create a PaymentIntent and hold funds in escrow.
     */
    public function createPaymentIntent(Client $client)
    {
        Log::info('Creating PaymentIntent - Request received');

        // Check if the client exists
        if (!$client) {
            Log::error("Client not found: {$client->id}");
            return response()->json(['message' => 'Client not found.'], 404);
        }

        Log::info("Received Client ID: {$client->id}, Amount: {$client->amount}");

        // Set Stripe API key
        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            // Create a PaymentIntent
            $paymentIntent = PaymentIntent::create([
                'amount' => $client->amount * 100, // Convert to cents
                'currency' => 'usd',
                'payment_method_types' => ['card'],
                'capture_method' => 'manual', // Manual capture to hold funds
            ]);

            Log::info("Stripe PaymentIntent created successfully: {$paymentIntent->id}");

            // Save PaymentIntent ID to the database
            $client->payment_intent_id = $paymentIntent->id;
            $client->save();

            // Notify the worker that payment is held in escrow
            $worker = $client->user->worker; // Fetch the associated worker
            if ($worker) {
                Log::info("Notifying Worker ID: {$worker->id} about held payment.");
                $worker->notify(new PaymentHeldNotification($client));
            }

            // Return the client secret and payment intent ID to the client
            return response()->json([
                'message' => 'Payment Intent created successfully',
                'data' => [
                    'payment_intent_id' => $paymentIntent->id,
                    'client_secret' => $paymentIntent->client_secret,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Error creating PaymentIntent for Client ID: {$client->id}, Error: {$e->getMessage()}");
            return response()->json(['message' => 'Error creating payment intent: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Confirm the PaymentIntent and attach a payment method.
     */
    public function confirmPaymentIntent(Request $request, Client $client)
    {
        Log::info("Confirming PaymentIntent for Client ID: {$client->id}");

        // Check if the client exists
        if (!$client) {
            Log::error("Client not found: {$client->id}");
            return response()->json(['message' => 'Client not found.'], 404);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            // Retrieve the PaymentIntent
            $paymentIntent = PaymentIntent::retrieve($client->payment_intent_id);

            // Attach a payment method (replace with your valid payment method ID)
            $paymentIntent->payment_method = $request->input('payment_method_id');
            $paymentIntent->save();

            // Confirm the PaymentIntent
            $paymentIntent->confirm();

            // Ensure the PaymentIntent is now in "requires_capture" status
            if ($paymentIntent->status !== 'requires_capture') {
                Log::error("PaymentIntent confirmation failed. Status: {$paymentIntent->status}");
                return response()->json(['message' => 'Payment confirmation failed.'], 400);
            }

            Log::info("PaymentIntent confirmed successfully: {$paymentIntent->id}");

            return response()->json([
                'message' => 'Payment confirmed successfully',
                'data' => [
                    'payment_intent_id' => $paymentIntent->id,
                    'status' => $paymentIntent->status,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Error confirming PaymentIntent for Client ID: {$client->id}, Error: {$e->getMessage()}");
            return response()->json(['message' => 'Error confirming payment intent: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Release payment to the worker.
     */
    public function releasePayment(Request $request, Client $client)
    {
        Log::info("Received request to release payment for Client ID: {$client->id}");

        // Check if the client exists
        if (!$client) {
            Log::error("Client not found: {$client->id}");
            return response()->json(['message' => 'Client not found.'], 404);
        }

        // Fetch and log user and worker information
        $clientUser = $client->user ?? null;
        $worker = $client->worker ?? null; // Fetch worker via the 'worker_id' relation

        if (!$worker) {
            Log::warning("Worker not found for Client ID: {$client->id}");
            return response()->json(['message' => 'Worker not found.'], 404);
        }

        Log::info("Client User ID: " . optional($clientUser)->id);
        Log::info("Worker ID: " . optional($worker)->id);

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            // Retrieve the PaymentIntent
            $paymentIntent = PaymentIntent::retrieve($client->payment_intent_id);

            // Check if the PaymentIntent is ready for capture
            if ($paymentIntent->status !== 'requires_capture') {
                Log::error("PaymentIntent is not ready for capture. Status: {$paymentIntent->status}");
                return response()->json(['message' => 'PaymentIntent is not ready for capture.'], 400);
            }

            // Capture the PaymentIntent
            $paymentIntent->capture();
            Log::info("PaymentIntent captured successfully: {$paymentIntent->id}");

            // Notify the worker
            Log::info("Notifying Worker ID: {$worker->id} that payment is released.");
            $worker->notify(new PaymentReleasedNotification($client));

            // Notify the client
            if ($clientUser) {
                Log::info("Notifying Client ID: {$clientUser->id} that worker received payment.");
                $clientUser->notify(new PaymentReceivedNotification($worker));
            } else {
                Log::warning("User not found for Client ID: {$client->id}");
            }

            return response()->json(['message' => 'Payment released successfully']);
        } catch (\Exception $e) {
            Log::error("Error releasing payment for Client ID: {$client->id}, Error: {$e->getMessage()}");
            return response()->json(['message' => 'Error releasing payment: ' . $e->getMessage()], 500);
        }
    }


/**
 * Refund a PaymentIntent.
 */
/**
 * Refund a PaymentIntent for a worker.
 */
public function refundPayment(Request $request, Worker $worker)
{
    Log::info("Processing refund request for Worker ID: {$worker->id}");

    try {
        // Validate the request
        $request->validate([
            'payment_intent_id' => 'required|string', // PaymentIntent ID
            'amount' => 'nullable|numeric|min:1', // Optional: Amount in cents
        ]);

        // Set Stripe API key
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        // Retrieve the PaymentIntent to ensure it exists
        $paymentIntent = \Stripe\PaymentIntent::retrieve($request->payment_intent_id);

        if (!$paymentIntent) {
            Log::error("PaymentIntent not found: {$request->payment_intent_id}");
            return response()->json(['message' => 'PaymentIntent not found.'], 404);
        }

        Log::info("Refunding PaymentIntent ID: {$request->payment_intent_id} for Worker ID: {$worker->id}");

        // Create a refund
        $refund = \Stripe\Refund::create([
            'payment_intent' => $request->payment_intent_id,
            'amount' => $request->has('amount') ? $request->amount * 100 : null, // Refund full amount if 'amount' is not provided
        ]);

        // Log successful refund
        Log::info("Refund processed successfully for PaymentIntent ID: {$request->payment_intent_id}");

        return response()->json([
            'status' => true,
            'message' => 'Refund processed successfully.',
            'refund' => $refund,
        ], 200);
    } catch (\Exception $e) {
        // Log the error
        Log::error("Error processing refund for Worker ID: {$worker->id}, PaymentIntent ID: {$request->payment_intent_id}, Error: {$e->getMessage()}");

        return response()->json([
            'status' => false,
            'message' => 'Refund processing failed.',
            'error' => $e->getMessage(),
        ], 500);
    }
}



}
