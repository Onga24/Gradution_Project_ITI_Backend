<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
    return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
                    'invite_code' => 'required|string|exists:projects,invite_code',

        ];
    }
}
