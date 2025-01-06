<?php

namespace App\Jobs\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class RegisterJob implements ShouldQueue
{
    use Queueable;

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
    public function handle(): void
    {
        try {
            Log::info('RegisterJob started', ['data' => $this->data]);

            $user = User::create([
                'name' => $this->data['name'],
                'email' => $this->data['email'],
                'password' => Hash::make($this->data['password']),
            ]);

            Log::info('RegisterJob completed', ['user' => $user]);
        } catch (\Exception $e) {
            Log::error('RegisterJob failed', ['error' => $e->getMessage()]);
        }
    }
}
