<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

class WorkerStoreRequest extends FormRequest
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
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'contact_number' => 'required|string|max:20',
            'service_location' => 'required|string',
            'zip_code' => 'required|string|max:10',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'file|image|mimes:jpeg,png,jpg,gif|max:2048', 
            'service_type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'privacy_policy_agreement' => 'required|boolean',
        ];
    }
}
