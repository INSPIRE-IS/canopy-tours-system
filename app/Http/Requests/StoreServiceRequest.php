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
        return Gate::allows('season_create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 'name' => [
            //     'string',
            //     'required',
            //     'unique:services',
            // ],
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
            //     'string',
            //     'required',
            // ],
            // 'lat_long_end' => [
            //     'string',
            //     'required',
            // ],
        ];
    }
}
