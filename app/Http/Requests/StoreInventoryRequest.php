<?php

namespace App\Http\Requests;

use App\Models\Inventory;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreInventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('inventory_create');
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
            'season_name' => [
                'string',
                'required',
            ],
            'start_date' => [
                'required',
                'date_format:' . config('panel.date_format'),
            ],
            'end_date' => [
                'required',
                'date_format:' . config('panel.date_format'),
            ],
            'rake_rate_adult' => [
                'numeric',
                'required',
            ],
            'rake_rate_child' => [
                'numeric',
                'required',
            ],
            'rake_rate_guide' => [
                'numeric',
                'required',
            ],
            'day_slots.*' => [
                'integer',
            ],
            'day_slots' => [
                'required',
                'array',
            ],
            'time_slots.*' => [
                'string',
            ],
            'time_slots' => [
                'required',
                'array',
            ],
            'duration' => [
                'date_format:' . config('panel.time_format'),
                'nullable',
            ],
            'capacity' => [
                'required',
                'integer',
                'min: 1',
                'max:100',
            ],
            'allow_internal_override' => [
                'required',
            ],
            'status' => [
                'required',
            ],
            'pillbox_conditions' => [
                'string',
                'nullable',
            ],
            'updated_by_id' => [
                'required',
                'integer',
            ],
        ];
    }
}
