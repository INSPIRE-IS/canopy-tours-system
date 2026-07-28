<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCloseoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('closeouts_edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:255',
            'priority' => 'nullable|integer|min:0|max:1000',
            'service_id' => 'required|integer',
            'season_id' => 'required|integer',
            'team_id' => 'required|integer',
            'day_id' => 'required|array|min:1',
            'day_id.*' => 'required|integer',
            'time_slot_id' => 'required|array|min:1',
            'time_slot_id.*' => 'required|integer',
        ];
    }
}
