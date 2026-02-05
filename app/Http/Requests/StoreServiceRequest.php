<?php

namespace App\Http\Requests;

use App\Models\Season;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('services_create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'string',
                'required',
                'unique:services',
            ],
            // 'duration' => [
            //     'date_format:' . config('panel.time_format'),
            //     'nullable',
            // ],
            'capacity' => [
                'required',
                'integer',
                'min: 1',
                'max:100',
            ],
            'pillbox_conditions' => [
                'string',
                'nullable',
            ],
            'allow_internal_capacity_override' => [
                'required',
            ],
            // 'pillbox_text' => [
            //     'string',
            //     'required',
            // ],
            // 'arrangements' => [
            //     'required',
            // ],
            // 'inclusions_exclusions' => [
            //     'required',
            // ],
            // 'notes' => [
            //     'required',
            // ],
            // 'lat_lon_start' => [
            //     'required',
            // ],
            // 'lat_long_end' => [
            //     'required',
            // ],
        ];
    }
}
