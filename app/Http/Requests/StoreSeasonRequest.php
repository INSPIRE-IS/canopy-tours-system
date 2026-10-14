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
                'date_format:'.config('panel.date_format'),
            ],
            'end_date' => [
                'required',
                'date_format:'.config('panel.date_format'),
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
            'include_public_extras' => [
                'nullable',
                'boolean',
            ],
            'extras' => [
                'required_if:include_public_extras,true',
                'array',
                'min:1',
                'max:20',
            ],
            'extras.*' => [
                'array:id,name,description,unit_price,is_per_guest,is_active',
            ],
            'extras.*.id' => [
                'nullable',
                'integer',
            ],
            'extras.*.name' => [
                'required',
                'string',
                'max:30',
            ],
            'extras.*.description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'extras.*.unit_price' => [
                'required',
                'numeric',
                'min:0.01',
                'decimal:0,2',
            ],
            'extras.*.is_per_guest' => [
                'required',
                'boolean',
            ],
            'extras.*.is_active' => [
                'required',
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

    public function messages(): array
    {
        return [
            'extras.required_if' => 'Add at least one extra or turn off public extras for this season.',
            'extras.min' => 'Add at least one extra or turn off public extras for this season.',
            'extras.max' => 'A season can have up to 20 public extras.',
            'extras.*.name.required' => 'Enter a name for each extra.',
            'extras.*.name.max' => 'Use 30 characters or fewer for the extra name.',
            'extras.*.description.max' => 'Use 255 characters or fewer for the extra description.',
            'extras.*.unit_price.required' => 'Enter a price for each extra.',
            'extras.*.unit_price.min' => 'Extra prices must be greater than zero.',
            'extras.*.unit_price.decimal' => 'Extra prices may have no more than two decimal places.',
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
