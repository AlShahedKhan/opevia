<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Jobs\Auth\RegisterJob;
use App\Traits\HandlesApiResponse;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Jobs\Auth\LoginJob;
use App\Jobs\Auth\LogoutJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;

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
}
