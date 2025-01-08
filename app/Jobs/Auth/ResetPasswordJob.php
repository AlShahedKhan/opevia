<?php

namespace App\Jobs\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ResetPasswordJob implements ShouldQueue
{
    use Queueable;

    protected $credentials;

    /**
     * Create a new job instance.
     */
    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * Execute the job.
     */
    public function handle(): string
    {
        Log::info('ResetPasswordJob started', ['credentials' => $this->credentials]);
        $status = Password::reset(
            $this->credentials,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        Log::info('ResetPasswordJob completed', ['status' => $status]);

        return $status;
    }
}
