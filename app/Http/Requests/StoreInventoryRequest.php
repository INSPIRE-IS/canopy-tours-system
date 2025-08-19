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
        return Gate::allows('inventories_create');
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
            'created_by_id' => [
                'required',
                'integer',
            ],
            'allow_internal_override' => [
                'required',
            ],
        ];
    }
}
