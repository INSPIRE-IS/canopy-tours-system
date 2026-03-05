<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInventorySlotRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('inventories_edit');
    }

    public function rules(): array
    {
        return [
            'service_id' => [
                'required',
                'integer',
            ],
            'season_id' => [
                'required',
                'integer',
            ],
            'start_date' => [
                'required',
                'date',
            ],
            'end_date' => [
                'required',
                'date',
            ],
            'time_slot' => [
                'required',
                'string',
            ],
            'internal_only' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
