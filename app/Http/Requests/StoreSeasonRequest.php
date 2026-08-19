<?php

namespace App\Http\Requests;

use App\Models\Season;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSeasonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('seasons_create');
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
                Rule::exists('services', 'id')->where('team_id', auth()->user()->team_id),
            ],
            'name' => [
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
                'after_or_equal:start_date',
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
            'allow_internal_price_override' => [
                'nullable',
                'boolean',
            ],
            'internal_only' => [
                'nullable',
                'boolean',
            ],

            'status' => [
                'required',
                'boolean',
            ],
            'created_by_id' => [
                'required',
                'integer',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateNoOverlappingSeason($validator);
        });
    }

    private function validateNoOverlappingSeason(Validator $validator): void
    {
        $overlap = Season::query()
            ->where('team_id', auth()->user()->team_id)
            ->where('service_id', $this->input('service_id'))
            ->whereDate('start_date', '<=', $this->input('end_date'))
            ->whereDate('end_date', '>=', $this->input('start_date'))
            ->exists();

        if ($overlap) {
            $validator->errors()->add('start_date', 'This season overlaps an existing season for the same service. Adjust the dates or edit the existing season.');
        }
    }
}
