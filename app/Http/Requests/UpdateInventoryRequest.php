<?php

namespace App\Http\Requests;

use App\Models\Inventory;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UpdateInventoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->boolean('allow_slot_funneling')) {
            $this->merge(['slot_funneling' => 0]);
        }
    }

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
        $teamId = auth()->user()->team_id;
        $inventoryId = optional($this->route('inventory'))->id;
        $seasonUniqueRule = Rule::unique('inventories', 'season_id')
            ->ignore($inventoryId)
            ->where(function ($query) use ($teamId) {
                $query->whereNull('deleted_at');
                if ($teamId === null) {
                    $query->whereNull('team_id');
                    return;
                }
                $query->where(function ($scoped) use ($teamId) {
                    $scoped->where('team_id', $teamId)
                        ->orWhereNull('team_id');
                });
            });

        return [
            'service_id' => [
                'required',
                'integer',
            ],
            'season_id' => [
                'required',
                'integer',
                $seasonUniqueRule,
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
