<?php

namespace App\Jobs\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegisterJob implements ShouldQueue
{
    use Dispatchable;

    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info('RegisterJob started', ['data' => $this->data]);

            User::create([
                'name' => $this->data['name'],
                'email' => $this->data['email'],
                'password' => Hash::make($this->data['password']),
                'role' => $this->data['role'],
                'is_admin' => $this->data['is_admin'] ?? false,
            ]);

            Log::info('RegisterJob completed successfully.');
        } catch (\Exception $e) {
            Log::error('RegisterJob failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}

