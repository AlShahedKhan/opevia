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
    public function handle()
    {
        Log::info("ForgetPasswordJob started", ['email' => $this->email]);

        $status = Password::sendResetLink(['email' => $this->email]);

        Log::info("ForgetPasswordJob completed", ['status' => $status]);

        return $status;
    }
}
