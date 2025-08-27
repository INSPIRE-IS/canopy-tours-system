<?php

namespace App\Http\Requests;

use App\Models\Service;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('services_edit');
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
                'unique:services,name,' . request()->route('service')->id,
            ],
            // 'duration' => [
            //     'date_format:' . config('panel.time_format'),
            //     'nullable',
            // ],
            'capacity' => [
                'required',
                'integer',
                'min:-2147483648',
                'max:2147483647',
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
