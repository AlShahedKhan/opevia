<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Pest\Support\Str;
use App\Jobs\Auth\LoginJob;
use App\Jobs\Auth\LogoutJob;
use App\Jobs\Auth\RegisterJob;
use App\Traits\HandlesApiResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use App\Jobs\Auth\ForgetPasswordJob;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\ForgetPasswordRequest;

class AuthController extends Controller
{
    use HandlesApiResponse;
    public function register(RegisterRequest $request)
    {
        return $this->safeCall(function () use ($request) {
            $validated = $request->validated();

            if (!$validated) {
                return $this->errorResponse('Validation error', 422);
            }

            $data = $request->only(['name', 'email', 'password']);
            RegisterJob::dispatchSync($data);
            $user = User::where('email', $data['email'])->first();

            if (!$user) {
                return $this->errorResponse('User not found after registration', 500);
            }

            $token = JWTAuth::fromUser($user);
            $cookie = cookie('jwt', $token, 60 * 24);

            return response()->json([
                'status' => true,
                'message' => 'User registered successfully',
                'user' => $user,
                'token' => $token,
            ])->cookie($cookie);
        });
    }

    public function login(LoginRequest $request)
    {
        return $this->safeCall(function () use ($request) {
            $validated = $request->validated();

            if (!$validated) {
                return $this->errorResponse('Validation error', 422);
            }

            $data = $request->only(['email', 'password']);

            LoginJob::dispatchSync($data);

            $user = User::where('email', $data['email'])->first();

            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            if (!Hash::check($data['password'], $user->password)) {
                return $this->errorResponse('Invalid password', 401);
            }

            $token = JWTAuth::fromUser($user);
            $cookie = cookie('jwt', $token, 60 * 24);

            return response()->json([
                'status' => true,
                'message' => 'User logged in successfully',
                'user' => $user,
                'token' => $token,
            ])->cookie($cookie);
        });
    }

    public function logout()
    {
        return $this->safeCall(function () {
            LogoutJob::dispatchSync('jwt');
            return response()->json([
                'status' => true,
                'message' => 'User logged out successfully',
            ]);
        });
    }

    public function forgotPassword(ForgetPasswordRequest $request)
    {
        return $this->safeCall(function () use ($request) {
            $validated = $request->validated();

            if (!$validated) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'status_code' => 422,
                ], 422);
            }

            $job = new ForgetPasswordJob($request->email);

            // Dispatch the job and capture its response
            $jobResponse = $job->handle();

            if ($jobResponse['status']) {
                return response()->json($jobResponse, 200);
            }

            return response()->json($jobResponse, 500);
        });
    }




    public function resetPassword(ResetPasswordRequest $request)
    {
        return $this->safeCall(function () use ($request) {
            $validated = $request->validated();

            if (!$validated) {
                return $this->errorResponse('Validation error', 422);
            }

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                    ])->setRememberToken(Str::random(60));

                    $user->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'status' => true,
                    'message' => 'Password reset successfully',
                ]);
            } else {
                return $this->errorResponse('Failed to reset password', 422, ['email' => __($status)]);
            }
        });
    }
}
