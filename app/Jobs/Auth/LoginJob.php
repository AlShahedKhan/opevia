<?php

namespace App\Jobs\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;

class LoginJob implements ShouldQueue
{
    use Dispatchable;

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
    public function handle(): array
    {
        try {
            Log::info('LoginJob started', ['data' => $this->data]);

            // Check if the user exists
            $user = User::where('email', $this->data['email'])->first();

            if (!$user) {
                Log::error('LoginJob failed: User not found');
                return [
                    'status' => false,
                    'message' => 'User not found',
                    'code' => 404,
                ];
            }

            // Validate password
            if (!Hash::check($this->data['password'], $user->password)) {
                Log::error('LoginJob failed: Invalid password');
                return [
                    'status' => false,
                    'message' => 'Invalid password',
                    'code' => 401,
                ];
            }

            Log::info('LoginJob completed successfully', ['user' => $user]);

            return [
                'status' => true,
                'message' => 'Login successful',
                'code' => 200,
                'user' => $user,
            ];
        } catch (\Exception $e) {
            Log::error('LoginJob failed with exception', ['error' => $e->getMessage()]);
            return [
                'status' => false,
                'message' => 'Something went wrong. Please try again.',
                'code' => 500,
            ];
        }
    }
}
