<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;
use App\Notifications\PaymentHeldNotification;
use App\Notifications\PaymentReleasedNotification;
use App\Notifications\PaymentReceivedNotification;

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


}
