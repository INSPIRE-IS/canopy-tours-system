<?php

namespace App\Http\Requests;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

class StoreInventorySlotRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('inventories_create');
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
            'time_slots' => [
                'required',
                'array',
                'min:1',
            ],
            'time_slots.*' => [
                'string',
            ],
            'internal_only' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
