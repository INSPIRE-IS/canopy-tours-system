<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVoucherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('vouchers_edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'voucher_code' => 'required|string|max:255',
            'amount' => 'required|float',
            'note' => 'required|string|max:255',
            'expire_date' => 'required|date',
            'service_id' => 'required|integer',
            'season_id' => 'required|integer',
            'team_id' => 'required|integer',
        ];
    }
}
