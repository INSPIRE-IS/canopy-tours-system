<?php

namespace App\Http\Requests;

use App\Models\Role;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $integrationRoleId = Role::query()->where('name', 'Integration API')->value('id');
            $roleIds = collect($this->input('roles', []))->map(fn ($id) => (int) $id);

            if (! $integrationRoleId || ! $roleIds->contains((int) $integrationRoleId)) {
                return;
            }

            if (! $this->user()?->hasAnyRoles(['Admin'])) {
                $validator->errors()->add('roles', 'Only an Admin may assign the Integration API role.');
            }
            if ($roleIds->count() !== 1) {
                $validator->errors()->add('roles', 'The Integration API role cannot be combined with another role.');
            }
            if (! $this->filled('team_id')) {
                $validator->errors()->add('team_id', 'A site is required for an Integration API user.');
            }
        });
    }
}
