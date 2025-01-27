<?php

namespace App\Http\Controllers\Service;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Service;
use Illuminate\Http\Request;
use App\Traits\HandlesApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Jobs\Service\BookServiceJob;
use Illuminate\Support\Facades\Auth;


class ServiceController extends Controller
{
    use HandlesApiResponse;

    public function index()
    {
        return $this->safeCall(function () {
            $user = Auth::user();

            if (!$user || $user->role !== 'worker') {
                return $this->errorResponse('Unauthorized access', 403);
            }

            $services = Service::where('worker_id', $user->id)
                ->with('client')->get();
            return $this->successResponse('Services fetched successfully', [
                'data' => $services,
            ]);
        });
    }



    public function acceptService(Service $service)
    {
        return $this->safeCall(function () use ($service) {
            $user = Auth::user();

            // Ensure the authenticated user is the assigned worker for this service
            if ($user->id !== $service->worker_id) {
                return $this->errorResponse('Unauthorized access', 403);
            }

            // Update the service status to 'processing'
            $service->update(['status' => 'processing']);

            // Retrieve the payment_intent_id from the services table
            $paymentIntentId = $service->payment_intent_id;

            if (!$paymentIntentId) {
                return $this->errorResponse('PaymentIntent ID not found for this service.', 400);
            }

            // Confirm the PaymentIntent
            $paymentIntentResponse = $this->confirmPaymentIntent(request(), $paymentIntentId);

            if ($paymentIntentResponse instanceof \Illuminate\Http\JsonResponse) {
                $response = $paymentIntentResponse->getData(true);
            } else {
                $response = $paymentIntentResponse;
            }

            Log::info('Payment confirmation response:', $response);

            // Check for status in the response
            if (!isset($response['status']) || !$response['status']) {
                return $this->errorResponse($response['message'] ?? 'Payment confirmation failed.', 400);
            }

            // Extract `payment_intent_id` from the response
            $confirmedPaymentIntentId = $response['data']['payment_intent_id'] ?? null;

            if (!$confirmedPaymentIntentId) {
                return $this->errorResponse('Payment confirmation failed: PaymentIntent ID is missing.', 400);
            }

            return $this->successResponse('Service successfully accepted and payment confirmed.', [
                'service_id' => $service->id,
                'payment_intent_id' => $confirmedPaymentIntentId,
                'status' => $response['data']['status'],
            ]);
        });
    }

    private function confirmPaymentIntent(Request $request, $paymentIntentId)
    {
        return $this->safeCall(function () use ($request, $paymentIntentId) {
            // Log the PaymentIntent ID for debugging
            Log::info('PaymentIntent ID: ' . $paymentIntentId);

            // Set up Stripe API with the secret key
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

            // Retrieve the PaymentIntent from Stripe
            try {
                $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
                Log::info('PaymentIntent Status: ' . $paymentIntent->status);
            } catch (\Exception $e) {
                return $this->errorResponse('Error retrieving PaymentIntent: ' . $e->getMessage(), 500);
            }

            // If the PaymentIntent has already succeeded, skip the confirmation
            if ($paymentIntent->status === 'succeeded') {
                Log::info('PaymentIntent already succeeded, skipping confirmation.');
                return $this->successResponse('Payment already confirmed.', [
                    'payment_intent_id' => $paymentIntent->id,
                    'status' => $paymentIntent->status,
                ]);
            }

            // Ensure payment method ID is provided in the request
            $paymentMethodId = $request->input('payment_method_id');
            if (!$paymentMethodId) {
                return $this->errorResponse('Payment method ID is required.', 400);
            }

            // Log payment method ID for debugging
            Log::info('Payment Method ID: ' . $paymentMethodId);

            // Attach the provided payment method to the PaymentIntent if required
            if ($paymentIntent->status === 'requires_payment_method' || $paymentIntent->status === 'requires_confirmation') {
                $paymentIntent->payment_method = $paymentMethodId;
                try {
                    $paymentIntent->save();
                } catch (\Exception $e) {
                    return $this->errorResponse('Error saving PaymentIntent: ' . $e->getMessage(), 500);
                }

                // Confirm the PaymentIntent with capture_method set to 'manual' (this holds the payment)
                try {
                    $paymentIntent->confirm(['capture_method' => 'manual']);
                } catch (\Exception $e) {
                    return $this->errorResponse('Error confirming PaymentIntent: ' . $e->getMessage(), 500);
                }

                // Check if the PaymentIntent is ready for capture
                if ($paymentIntent->status !== 'requires_capture') {
                    return $this->errorResponse('Payment confirmation failed. Payment status is not "requires_capture".', 400);
                }
            } else {
                return $this->errorResponse('PaymentIntent status is not valid for confirmation.', 400);
            }

            // Return success response with the PaymentIntent ID and status
            return $this->successResponse('Payment confirmed successfully and held.', [
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
            ]);
        });
    }

    public function cancelService(Service $service)
    {
        return $this->safeCall(function () use ($service) {
            $user = Auth::user();

            // Check if the authenticated user's ID matches the worker_id
            if ($user->id !== $service->worker_id) {
                return $this->errorResponse('Unauthorized access', 403);
            }

            $service->update(['status' => 'canceled']);
            return $this->successResponse('Service successfully canceled.', [
                'service_id' => $service->id,
            ]);
        });
    }

    public function completeService(Service $service)
    {
        return $this->safeCall(function () use ($service) {
            $user = Auth::user();

            // Ensure the authenticated user is the client who owns this service
            if ($user->id !== $service->client_id) {
                return $this->errorResponse('Unauthorized access', 403);
            }

            // Update the service status to 'completed'
            $service->update(['status' => 'completed']);

            // Retrieve the payment_intent_id from the services table
            $paymentIntentId = $service->payment_intent_id;

            if (!$paymentIntentId) {
                return $this->errorResponse('PaymentIntent ID not found for this service.', 400);
            }

            // Set up Stripe API with the secret key
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

            // Retrieve the PaymentIntent from Stripe
            try {
                $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
                Log::info('PaymentIntent Details:', (array) $paymentIntent);
            } catch (\Exception $e) {
                return $this->errorResponse('Error retrieving PaymentIntent: ' . $e->getMessage(), 500);
            }

            // Handle the `requires_payment_method` status
            if ($paymentIntent->status === 'requires_payment_method') {
                return $this->errorResponse('PaymentIntent requires a valid payment method.', 400);
            }

            // Handle the `requires_confirmation` status
            if ($paymentIntent->status === 'requires_confirmation') {
                try {
                    $paymentIntent->confirm();
                } catch (\Exception $e) {
                    return $this->errorResponse('Error confirming PaymentIntent: ' . $e->getMessage(), 500);
                }
            }

            // Check if the PaymentIntent is ready for capture
            if ($paymentIntent->status !== 'requires_capture') {
                return $this->errorResponse('PaymentIntent is not ready for capture.', 400);
            }

            // Capture the PaymentIntent
            try {
                $paymentIntent->capture();
            } catch (\Exception $e) {
                return $this->errorResponse('Error capturing PaymentIntent: ' . $e->getMessage(), 500);
            }

            // Update the Payment record with the payment method
            $payment = Payment::where('payment_intent_id', $paymentIntentId)->first();
            if ($payment) {
                $payment->update([
                    'payment_method' => $paymentIntent->payment_method,
                ]);
            }

            return $this->successResponse('Service successfully completed and payment released.', [
                'service_id' => $service->id,
                'payment_intent_id' => $paymentIntentId,
                'status' => $paymentIntent->status,
            ]);
        });
    }






    public function refundPayment(Request $request, Service $service)
    {
        return $this->safeCall(function () use ($request, $service) {
            $user = Auth::user();

            // Ensure the authenticated user is either the worker or client associated with the service
            if ($user->id !== $service->worker_id && $user->id !== $service->client_id) {
                return $this->errorResponse('Unauthorized access', 403);
            }

            // Validate the request
            $request->validate([
                'payment_intent_id' => 'required|string',
                'refund_reason' => 'nullable|string',
            ]);

            // Retrieve the payment intent ID from the service to ensure it's accurate
            $paymentIntentId = $service->payment_intent_id;
            if ($paymentIntentId !== $request->payment_intent_id) {
                return $this->errorResponse('Invalid PaymentIntent ID.', 400);
            }

            // Set up Stripe API with the secret key
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

            // Retrieve the PaymentIntent to confirm its existence
            try {
                $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
            } catch (\Exception $e) {
                return $this->errorResponse('Error retrieving PaymentIntent: ' . $e->getMessage(), 500);
            }

            // Create a refund for the PaymentIntent
            try {
                $refund = \Stripe\Refund::create([
                    'payment_intent' => $paymentIntentId,
                    'reason' => $request->refund_reason ?? 'requested_by_customer',
                ]);
            } catch (\Exception $e) {
                return $this->errorResponse('Error processing refund: ' . $e->getMessage(), 500);
            }

            // Update the payment record in the database
            $payment = Payment::where('payment_intent_id', $paymentIntentId)->first();
            if ($payment) {
                $payment->update([
                    'refund_date' => now(),
                    'refund_reason' => $request->refund_reason,
                    'refund_status' => 'refunded',
                ]);
            }

            // Update the service status to 'refunded'
            $service->update(['status' => 'refunded']);

            return $this->successResponse('Refund processed successfully.', [
                'service_id' => $service->id,
                'refund_id' => $refund->id,
                'payment_intent_id' => $paymentIntentId,
                'refund_status' => $refund->status,
            ]);
        });
    }

    public function show(Service $service)
    {
        return $this->safeCall(function () use ($service) {
            $user = Auth::user();

            // Check if the authenticated user's ID matches the worker_id
            if ($user->id !== $service->worker_id) {
                return $this->errorResponse('Unauthorized access', 403);
            }

            // Load the related client data
            $service->load('client');

            return $this->successResponse('Service fetched successfully', [
                'data' => $service,
            ]);
        });
    }

    public function pendingServices()
    {
        return $this->safeCall(function () {
            $user = Auth::user();

            // Check if the user has the role of 'worker'
            if (!$user || $user->role !== 'worker') {
                return $this->errorResponse('Unauthorized access', 403);
            }

            // Fetch pending services where the worker_id matches the authenticated user's ID and include related client data
            $services = Service::where('worker_id', $user->id)
                ->where('status', 'pending')
                ->with('clientWorkRequest')
                ->get();

            if ($services->isEmpty()) {
                return $this->successResponse('No pending services found.', [
                    'data' => [],
                ]);
            }

            return $this->successResponse('Pending services fetched successfully', [
                'data' => $services,
            ]);
        });
    }
    public function completedServices()
    {
        return $this->safeCall(function () {
            $user = Auth::user();

            // Check if the user has the role of 'worker'
            if (!$user || $user->role !== 'worker') {
                return $this->errorResponse('Unauthorized access', 403);
            }

            // Fetch pending services where the worker_id matches the authenticated user's ID and include related client data
            $services = Service::where('worker_id', $user->id)
                ->where('status', 'completed')
                ->with('clientWorkRequest')
                ->get();

            if ($services->isEmpty()) {
                return $this->successResponse('No completed services found.', [
                    'data' => [],
                ]);
            }

            return $this->successResponse('Completed services fetched successfully', [
                'data' => $services,
            ]);
        });
    }
    public function processingServices()
    {
        return $this->safeCall(function () {
            $user = Auth::user();

            // Check if the user has the role of 'worker'
            if (!$user || $user->role !== 'worker') {
                return $this->errorResponse('Unauthorized access', 403);
            }

            // Fetch pending services where the worker_id matches the authenticated user's ID and include related client data
            $services = Service::where('worker_id', $user->id)
                ->where('status', 'processing')
                ->with('clientWorkRequest')
                ->get();

            if ($services->isEmpty()) {
                return $this->successResponse('No processing services found.', [
                    'data' => [],
                ]);
            }

            return $this->successResponse('processing services fetched successfully', [
                'data' => $services,
            ]);
        });
    }

    public function clientPendingServices()
    {
        return $this->safeCall(function () {
            $user = Auth::user();

            // Log the authenticated user's details
            Log::info('Authenticated user:', [
                'id' => $user->id ?? null,
                'role' => $user->role ?? 'guest',
            ]);

            // Check if the user has the role of 'client'
            if (!$user || $user->role !== 'client') {
                Log::warning('Unauthorized access attempt', [
                    'user_id' => $user->id ?? null,
                    'role' => $user->role ?? 'guest',
                ]);
                return $this->errorResponse('Unauthorized access', 403);
            }

            // Fetch pending services with related client work request
            $services = Service::where('client_id', $user->id)
                ->where('status', 'pending')
                ->with(['clientWorkRequest' => function ($query) {
                    // Log relationship queries
                    Log::info('Fetching client work request');
                }])
                ->get();

            // Log the query result
            Log::info('Services fetched for client:', [
                'client_id' => $user->id,
                'services_count' => $services->count(),
                'services' => $services->toArray(),
            ]);

            // Handle empty results
            if ($services->isEmpty()) {
                Log::info('No pending services found for client:', ['client_id' => $user->id]);
                return $this->successResponse('No pending services found.', [
                    'data' => [],
                ]);
            }

            return $this->successResponse('Pending services fetched successfully', [
                'data' => $services,
            ]);
        });
    }

    public function clientProcessingServices()
    {
        return $this->safeCall(function () {
            $user = Auth::user();

            // Check if the user has the role of 'client'
            if (!$user || $user->role !== 'client') {
                return $this->errorResponse('Unauthorized access', 403);
            }

            // Fetch pending services where the client_id matches the authenticated user's ID and include related client data
            $services = Service::where('client_id', $user->id)
                ->where('status', 'processing')
                ->with('clientWorkRequest')
                ->get();

            if ($services->isEmpty()) {
                return $this->successResponse('No processing services found.', [
                    'data' => [],
                ]);
            }

            return $this->successResponse('processing services fetched successfully', [
                'data' => $services,
            ]);
        });
    }

    public function clientCompletedServices()
    {
        return $this->safeCall(function () {
            $user = Auth::user();

            // Check if the user has the role of 'client'
            if (!$user || $user->role !== 'client') {
                return $this->errorResponse('Unauthorized access', 403);
            }

            // Fetch pending services where the client_id matches the authenticated user's ID and include related client data
            $services = Service::where('client_id', $user->id)
                ->where('status', 'completed')
                ->with('clientWorkRequest')
                ->get();

            if ($services->isEmpty()) {
                return $this->successResponse('No completed services found.', [
                    'data' => [],
                ]);
            }

            return $this->successResponse('Completed services fetched successfully', [
                'data' => $services,
            ]);
        });
    }
}
