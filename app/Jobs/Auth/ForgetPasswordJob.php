<?php

namespace App\Jobs\Auth;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Password;

class ForgetPasswordJob implements ShouldQueue
{
    use Queueable;

    protected $email;

    /**
     * Create a new job instance.
     */
    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
     * Execute the job.
     */
    public function handle(): array
    {
        Log::info("ForgetPasswordJob started", ['email' => $this->email]);

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            Log::info("ForgetPasswordJob completed", ['status' => $status]);
            return [
                'status' => true,
                'message' => 'Password reset link sent successfully',
            ];
        } else {
            Log::error("ForgetPasswordJob failed", ['status' => $status]);
            return [
                'status' => false,
                'message' => 'Unable to send password reset link',
                'errors' => ['email' => __($status)],
            ];
        }
    }
}
