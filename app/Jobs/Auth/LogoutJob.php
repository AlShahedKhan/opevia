<?php

namespace App\Jobs\Auth;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogoutJob implements ShouldQueue
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
        Log::info('LogoutJob started', ['data' => $this->data]);

        $data = Cookie::forget('jwt');

        Log::info('LogoutJob completed', ['data' => $data]);
    }
}
