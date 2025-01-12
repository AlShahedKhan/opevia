<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ClientProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow if the user is authenticated and has the 'client' role
        if (!auth()->check() || auth()->user()->role !== 'client') {
            abort(response()->json([
                'status' => false,
                'message' => 'Access denied. Only clients are authorized to update their profiles.',
                'status_code' => 403,
                'error' => 'This action is unauthorized.'
            ], 403));
        }

        return true;
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Optional image file
            'first_name' => 'nullable|string|max:255',                  // First name (optional)
            'last_name' => 'nullable|string|max:255',                   // Last name (optional)
            'phone' => 'nullable|string|unique:users,phone,' . $this->user()->id, // Phone (optional, must be unique)
            'email' => 'nullable|email|exists:users,email',             // Email is optional but validated
            'location' => 'nullable|string|max:255',                    // Location (optional)
            'describe_yourself' => 'nullable|string|max:1000',          // Description (optional)
        ];
    }
}
