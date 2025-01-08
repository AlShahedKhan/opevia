<?php

namespace App\Jobs\Contact;

use App\Models\Contact;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ContactStoreJob implements ShouldQueue
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
        Log::info('ContactStoreJob started', $this->data);

        Contact::create([
            'name' => $this->data['name'],
            'email' => $this->data['email'],
            'message' => $this->data['message'],
        ]);

        Log::info('ContactStoreJob completed');
    }
}
