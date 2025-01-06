<?php

namespace App\Jobs\Auth;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class LoginJob implements ShouldQueue
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
        try {
            Log::info('LoginJob started', ['data' => $this->data]);

            $user = User::where('email', $this->data['email'])->first();

            if (!$user) {
                Log::error('LoginJob failed', ['error' => 'User not found']);
                return;
            }

            if (!Hash::check($this->data['password'], $user->password)) {
                Log::error('LoginJob failed', ['error' => 'Invalid password']);
                return;
            }
            Log::info('LoginJob completed', ['user' => $user]);
        } catch (\Exception $e) {
            Log::error('LoginJob failed', ['error' => $e->getMessage()]);
        }
    }
}
