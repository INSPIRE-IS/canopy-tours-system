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
                'nullable',
                'string',
            ],
            'time_slots' => [
                'nullable',
                'array',
            ],
            'time_slots.*' => [
                'string',
            ],
            'ids' => [
                'nullable',
                'array',
            ],
            'ids.*' => [
                'integer',
            ],
            'capacity_override' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'note' => [
                'nullable',
                'string',
            ],
            'internal_only' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
