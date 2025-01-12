<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class WorkerProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Image (optional)
            'first_name' => 'nullable|string|max:255',         // First name (optional)
            'last_name' => 'nullable|string|max:255',          // Last name (optional)
            'phone' => 'nullable|string|unique:users,phone,' . $this->user()->id, // Phone (optional, must be unique)
            'email' => 'nullable|email|exists:users,email',    // Email is required but cannot be updated
            'location' => 'nullable|string|max:255',           // Location (optional)
            'service_type' => 'nullable|string|max:255',       // Service type (optional)
            'work_experience' => 'nullable|string|max:255',    // Work experience (optional)
            'describe_yourself' => 'nullable|string|max:1000', // Description (optional)
        ];
    }
}
