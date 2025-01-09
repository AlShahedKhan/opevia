<?php

namespace App\Jobs\Worker;

use App\Models\Worker;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class WorkerStoreJob implements ShouldQueue
{
    use Queueable;

    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('WorkerStoreJob started', ['data' => $this->data]);

        $photoPaths = $this->data['photos'] ?? [];

        try {
            // Create the worker record with the photo paths
            Worker::create([
                'user_id' => $this->data['user_id'], // Save user ID
                'company_name' => $this->data['company_name'],
                'email' => $this->data['email'],
                'contact_number' => $this->data['contact_number'],
                'service_location' => $this->data['service_location'],
                'zip_code' => $this->data['zip_code'],
                'photos' => json_encode($photoPaths), // Store the file paths as JSON
                'service_type' => $this->data['service_type'],
                'description' => $this->data['description'] ?? null,
                'privacy_policy_agreement' => $this->data['privacy_policy_agreement'],
            ]);

            Log::info('WorkerStoreJob completed', ['photos' => $photoPaths]);
        } catch (\Exception $e) {
            Log::error('Failed to create worker', [
                'error' => $e->getMessage(),
                'data' => $this->data,
            ]);
        }
    }
}
