<?php

namespace App\Http\Requests;

use App\Models\Season;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreSeasonRequest extends FormRequest
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
            'service_id' => [
                'required',
                'integer',
            ],
            'name' => [
                'string',
                'required',
            ],
            'start_date' => [
                'required',
                //'date_format:' . config('panel.date_format'),
            ],
            'end_date' => [
                //'date_format:' . config('panel.date_format'),
                'nullable',
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
        ];
    }
}
