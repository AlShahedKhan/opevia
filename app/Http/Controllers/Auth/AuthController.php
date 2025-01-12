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
use App\Jobs\Auth\ResetPasswordJob;
use App\Http\Controllers\Controller;
use App\Jobs\Auth\ForgetPasswordJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\ForgetPasswordRequest;
use App\Http\Requests\Auth\ClientProfileUpdateRequest;
use App\Http\Requests\Auth\WorkerProfileUpdateRequest;

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

            $data = $request->only(['first_name', 'last_name', 'email', 'password', 'role', 'is_admin']);

            // Dispatch the RegisterJob to handle user creation
            RegisterJob::dispatchSync($data); // Now it will work

            // Fetch the created user
            $user = User::where('email', $data['email'])->first();

            if (!$user) {
                return $this->errorResponse('User not found after registration', 500);
            }

            // Generate JWT token for the user with custom claims
            $token = JWTAuth::fromUser($user);

            return $this->successResponse('User registered successfully', [
                'user' => $user,
                'token' => $token,
            ]);
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

            // Dispatch the LoginJob to handle login validation
            $result = LoginJob::dispatchSync($data); // Now we return the result from LoginJob

            // If the job returns an error, handle it
            if (!$result['status']) {
                return $this->errorResponse($result['message'], $result['code']);
            }

            $user = $result['user'];
            $token = JWTAuth::fromUser($user);
            $cookie = cookie('jwt', $token, 60 * 24); // 1 day

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
                return $this->errorResponse('Validation error', 422);
            }

            $job = new ForgetPasswordJob($request->email);

            $status = $job->handle();

            if ($status === Password::RESET_LINK_SENT) {
                return $this->successResponse('Password reset link sent successfully');
            } else {
                return $this->errorResponse('Unable to send password reset link', 422);
            }
        });
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        return $this->safeCall(function () use ($request) {
            $validated = $request->validated();

            if (!$validated) {
                return $this->errorResponse('Validation error', 422);
            }

            $credentials = $request->only('email', 'password', 'password_confirmation', 'token');

            $job = new ResetPasswordJob($credentials);

            $status = $job->handle();

            if ($status === Password::PASSWORD_RESET) {
                return $this->successResponse('Password reset successfully');
            } else {
                return $this->errorResponse('Failed to reset password', 422, __($status));
            }
        });
    }

    public function WorkerProfileUpdate(WorkerProfileUpdateRequest $request)
    {
        return $this->safeCall(function () use ($request) {
            // Check if the authenticated user's role is not 'worker'
            if (Auth::user()->role !== 'worker') {
                return $this->errorResponse('Access denied. Only workers can update their profiles.', 403);
            }

            // Get the authenticated user
            $user = Auth::user();

            // Prepare the validated data
            $validatedData = $request->validated();

            // Handle image upload if an image is present
            if ($request->hasFile('image')) {
                // Delete the old image if it exists
                if ($user->image) {
                    \Storage::disk('public')->delete($user->image);
                }

                // Store the new image
                $imagePath = $request->file('image')->store('profile-images', 'public');

                // Add the image path to the validated data
                $validatedData['image'] = $imagePath;
            }

            // Merge existing data with the new data, retaining old values for null fields
            $updatedData = array_merge($user->toArray(), $validatedData);

            // Update the user's profile
            $user->update($updatedData);

            // Return the response
            return $this->successResponse('Profile updated successfully.', [
                'user' => $user->fresh(), // Return the updated user instance
            ]);
        });
    }

    public function ClientProfileUpdate(ClientProfileUpdateRequest $request)
    {
        return $this->safeCall(function () use ($request) {
            // Get the authenticated user
            $user = Auth::user();

            // Prepare the validated data
            $validatedData = $request->validated();

            // Handle image upload if an image is present
            if ($request->hasFile('image')) {
                // Delete the old image if it exists
                if ($user->image) {
                    \Storage::disk('public')->delete($user->image);
                }

                // Store the new image
                $imagePath = $request->file('image')->store('profile-images', 'public');

                // Add the image path to the validated data
                $validatedData['image'] = $imagePath;
            }

            // Merge existing data with the new data, retaining old values for null fields
            $updatedData = array_merge($user->toArray(), $validatedData);

            // Update the user's profile
            $user->update($updatedData);

            // Return the response
            return $this->successResponse('Profile updated successfully.', [
                'user' => $user->fresh(), // Return the updated user instance
            ]);
        });
    }
}
