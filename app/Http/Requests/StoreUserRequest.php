<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesIntegrationSites;
use Gate;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    use ValidatesIntegrationSites;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('users_create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'string',
                'required',
            ],
            'email' => [
                'required',
                'email',
                'unique:users',
            ],
            'send_password_setup' => ['sometimes', 'boolean'],
            'password' => [
                $this->boolean('send_password_setup') ? 'nullable' : 'required',
                'string',
            ],
            'roles.*' => [
                'integer',
                'exists:roles,id',
            ],
            'roles' => ['nullable', 'array'],
            'team_id' => ['nullable', 'exists:teams,id'],
            // 'roles' => [
            //     'required',
            //     'array',
            // ],
        ];
    }
}
