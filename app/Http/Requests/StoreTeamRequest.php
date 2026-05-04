<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class StoreTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('teams_create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'full_name' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string'],
            'reg_no' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'address_code' => ['nullable', 'string'],
            'account_owner' => ['nullable', 'string'],
            'account_number' => ['nullable', 'string'],
            'account_type' => ['nullable', 'string'],
            'bank_name' => ['nullable', 'string'],
            'branch_code' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'image' => ['nullable', 'image', 'max:4096'],
        ];
    }
}
