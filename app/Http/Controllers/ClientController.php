<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Service;
use App\Traits\HandlesApiResponse;
use App\Jobs\Client\ClientStoreJob;
use Illuminate\Support\Facades\Log;
use App\Jobs\Client\GetAllClientJob;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Client\ClientRequest;

class ClientController extends Controller
{
    use HandlesApiResponse;

    public function index()
    {
        return $this->safeCall(function () {
            // Dispatch the job synchronously and get the workers
            $workers = GetAllClientJob::dispatchSync();

            return $this->successResponse('Workers fetched successfully', [
                'data' => $workers,
            ]);
        });
    }

    public function store(ClientRequest $request)
    {
        return $this->safeCall(function () use ($request) {
            $user = Auth::user(); // Automatically fetch the authenticated user

            // Ensure the authenticated user is a 'client'
            if (!$user || $user->role !== 'client') {
                return $this->errorResponse('Unauthorized access', 403);
            }

            $validated = $request->validated();

            $workerId = $validated['worker_id'];
            $worker = \App\Models\User::where('id', $workerId)->where('role', 'worker')->first();

            if (!$worker) {
                return $this->errorResponse('Invalid worker_id. Worker not found or does not have the role of worker.', 422);
            }

            $photoPaths = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    if ($photo->isValid()) {
                        $path = $photo->store('photos', 'public');
                        $photoPaths[] = $path;
                    }
                }
            }

            // Add user_id and photos to the validated data
            $validated['photos'] = $photoPaths;
            $validated['user_id'] = $user->id; // Automatically set user_id
            $validated['worker_id'] = $workerId;

            // Store the client using the ClientStoreJob
            ClientStoreJob::dispatchSync($validated);

            // Fetch the client
            $client = Client::where('email', $validated['email'])->latest('id')->first();

            // Log the response data
            Log::info('Client created successfully', ['id' => $client->id, 'data' => $validated]);

            // Step 1: Create the payment intent for this specific service
            $paymentMethodId = $validated['payment_method']; // Extract payment_method from the request
            $paymentIntentResponse = $this->createPaymentIntent($client, $paymentMethodId);


            if (!$paymentIntentResponse['status']) {
                return $this->errorResponse('Payment Intent creation failed', 500);
            }

            // Update the client record with the payment_intent_id
            $client->update([
                'payment_intent_id' => $paymentIntentResponse['payment_intent_id'],
            ]);

            // Step 2: Book a service, including `payment_intent_id`
            try {
                $service = Service::create([
                    'client_id' => $user->id, // Automatically use the authenticated user's ID
                    'worker_id' => $workerId,
                    'payment_intent_id' => $paymentIntentResponse['payment_intent_id'], // Include payment_intent_id
                    'status' => 'pending', // Default status
                ]);
            } catch (\Exception $e) {
                Log::error('Error booking service', ['error' => $e->getMessage()]);
                return $this->errorResponse('Service booking failed', 500);
            }

            $client = $client->fresh();

            // Return all relevant data in the success response
            return $this->successResponse('Client created successfully, payment intent created, and service booked.', [
                'client' => $client,
                'payment_intent_id' => $paymentIntentResponse['payment_intent_id'],
                'service_id' => $service->id,
                'photos' => $client->photos,
                'amount' => $client->amount,
                'worker_id' => $client->worker_id,
                'email' => $client->email,
                'service_status' => $service->status, // Return the status of the service
                'payment_method' => $paymentIntentResponse['payment_method'],
            ]);
        });
    }


    private function createPaymentIntent(Client $client, string $paymentMethodId)
    {
        try {
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

            // Create a new Stripe Customer for the client if one does not exist
            $stripeCustomer = \Stripe\Customer::create([
                'name' => $client->full_name,
                'email' => $client->email,
            ]);

            // Attach the PaymentMethod to the customer
            $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach([
                'customer' => $stripeCustomer->id,
            ]);

            // Step 1: Create the PaymentIntent
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $client->amount * 100,  // Convert to cents
                'currency' => 'usd',
                'payment_method_types' => ['card'], // Restrict to card payments
                'customer' => $stripeCustomer->id,
                'payment_method' => $paymentMethod->id, // Attach the payment method
                'capture_method' => 'manual', // Ensure manual capture
            ]);

            // Step 2: Confirm the PaymentIntent
            $paymentIntent->confirm(); // Call confirm() on the paymentIntent object

            // Save the PaymentIntent details in your database
            Payment::create([
                'payment_intent_id' => $paymentIntent->id,
                'client_id' => $client->id,
                'worker_id' => $client->worker_id,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
                'payment_method' => $paymentIntent->payment_method, // Get the payment method
                'customer' => $client->full_name,
                'payment_date' => now(),
            ]);

            return [
                'status' => true,
                'payment_intent_id' => $paymentIntent->id,
                'payment_method' => $paymentIntent->payment_method,
            ];
        } catch (\Exception $e) {
            Log::error('Error creating PaymentIntent', ['error' => $e->getMessage()]);
            return ['status' => false];
        }
    }

    private function bookService($data)
    {
        try {
            // Create a service without the client_work_req_id
            $service = Service::create([
                'client_id' => $data['client_id'],
                'worker_id' => $data['worker_id'],
                'status' => 'pending', // Default status
            ]);

            return [
                'status' => true,
                'service_id' => $service->id,
            ];
        } catch (\Exception $e) {
            Log::error('Error booking service', ['error' => $e->getMessage()]);
            return ['status' => false];
        }
    }

    public function show(Client $client)
    {
        return $this->safeCall(function () use ($client) {
            return $this->successResponse('Client fetched successfully', [
                'data' => $client,
            ]);
        });
    }
}
