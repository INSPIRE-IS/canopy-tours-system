<?php

namespace App\Http\Requests;

use App\Models\Inventory;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateInventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('inventories_edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
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
            // 'day_slots.*' => [
            //     'integer',
            // ],
            // 'day_slots' => [
            //     'required',
            //     'array',
            // ],
            'time_slots.*' => [
                'string',
                'distinct',
            ],
            'time_slots' => [
                'required',
                'array',
            ],
            'slot_funneling' => 'nullable|integer|min:0|max:6',
            'slot_cutoff_hours' => 'nullable|integer|min:0|max:24',
            'updated_by_id' => [
                'required',
                'integer',
            ],
            'allow_internal_override' => [
                'required',
            ],
            'allow_slot_cutoff_hours' => 'required',
            'allow_slot_funneling' => 'required',

        ];
    }
}
