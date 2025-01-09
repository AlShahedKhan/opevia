<?php

namespace App\Jobs\Client;

use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ClientStoreJob implements ShouldQueue
{
    use Queueable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info('ClientStoreJob started', ['data' => $this->data]);

        $photoPaths = $this->data['photos'] ?? [];
        $client = null;

        try {
            // Wrap the operation in a transaction
            DB::transaction(function () use (&$client, $photoPaths) {
                $client = Client::create([
                    'user_id' => $this->data['user_id'], // Save user ID
                    'worker_id' => $this->data['worker_id'], // Save worker ID
                    'full_name' => $this->data['full_name'],
                    'email' => $this->data['email'],
                    'contact_number' => $this->data['contact_number'],
                    'service_location' => $this->data['service_location'],
                    'zip_code' => $this->data['zip_code'],
                    'photos' => json_encode($photoPaths),
                    'start_time' => $this->data['start_time'],
                    'end_time' => $this->data['end_time'],
                    'amount' => $this->data['amount'],
                    'description' => $this->data['description'] ?? null,
                    'privacy_policy_agreement' => $this->data['privacy_policy_agreement'],
                ]);


                Log::info('ClientStoreJob completed', [
                    'id' => $client->id,
                    'photos' => $photoPaths,
                ]);
            });

            return $client; // Return the created client instance
        } catch (\Exception $e) {
            Log::error('Failed to create client', [
                'error' => $e->getMessage(),
                'data' => $this->data,
            ]);

            throw $e; // Rethrow exception for error handling in controller
        }
    }
}
