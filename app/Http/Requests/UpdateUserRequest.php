<?php

namespace App\Http\Requests;

use App\Models\Role;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('users_edit');
    }

    public function rules()
    {
        return [
            'name' => [
                'string',
                'required',
            ],
            'email' => [
                'required',
                'unique:users,email,'.request()->route('user')->id,
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
