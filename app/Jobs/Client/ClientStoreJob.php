<?php

namespace App\Jobs\Client;

use App\Models\Client;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ClientStoreJob implements ShouldQueue
{
    use Queueable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        Log::info('ClientStoreJob started', ['data' => $this->data]);

        $photoPaths = $this->data['photos'] ?? [];

        try {
            // Create the client record with the photo paths
            Client::create([
                'full_name' => $this->data['full_name'],
                'email' => $this->data['email'],
                'contact_number' => $this->data['contact_number'],
                'service_location' => $this->data['service_location'],
                'zip_code' => $this->data['zip_code'],
                'photos' => json_encode($photoPaths), // Store the file paths as JSON
                'start_time' => $this->data['start_time'],
                'end_time' => $this->data['end_time'],
                'amount' => $this->data['amount'],
                'description' => $this->data['description'] ?? null,
                'privacy_policy_agreement' => $this->data['privacy_policy_agreement'],
            ]);

            Log::info('ClientStoreJob completed', ['photos' => $photoPaths]);
        } catch (\Exception $e) {
            Log::error('Failed to create client', [
                'error' => $e->getMessage(),
                'data' => $this->data,
            ]);
        }
    }
}
