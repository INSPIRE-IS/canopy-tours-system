<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('teams_edit');
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
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'address_code' => ['nullable', 'string'],
            'telephone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'website_url' => ['nullable', 'string', 'max:255'],
            'account_owner' => ['nullable', 'string'],
            'account_number' => ['nullable', 'string'],
            'account_type' => ['nullable', 'string'],
            'bank_name' => ['nullable', 'string'],
            'bank_branch_name' => ['nullable', 'string', 'max:255'],
            'branch_code' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'frontend_hold_enabled' => ['sometimes', 'boolean'],
            'frontend_hold_minutes' => ['required_if:frontend_hold_enabled,1', 'nullable', 'integer', 'min:1', 'max:120'],
            'frontend_unpaid_booking_hold_hours' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:24'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'image' => ['nullable', 'image', 'max:4096'],
            'invoice_quote_icon' => ['nullable', 'image', 'max:4096'],
        ];
    }
}
