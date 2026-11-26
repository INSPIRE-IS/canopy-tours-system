<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesIntegrationSites;
use Gate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    use ValidatesIntegrationSites;

    public function authorize()
    {
        return Gate::allows('users_edit')
            && (! $this->route('user')->hasAnyRoles(['Integration API']) || $this->user()?->hasAnyRoles(['Admin']));
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
}
