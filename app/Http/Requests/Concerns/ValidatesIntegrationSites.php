<?php

namespace App\Http\Requests\Concerns;

use App\Models\Role;
use App\Models\Team;
use Illuminate\Validation\Validator;

trait ValidatesIntegrationSites
{
    private ?array $integrationTeamIds = null;

    public function integrationTeamIds(): ?array
    {
        return $this->integrationTeamIds;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $roleId = Role::query()->where('name', 'Integration API')->value('id');
            $roles = array_map('intval', $this->input('roles') ?? []);
            if (! $roleId || ! in_array((int) $roleId, $roles, true)) {
                return;
            }

            if (! $this->user()?->hasAnyRoles(['Admin'])) {
                $validator->errors()->add('roles', 'Only an Admin may assign the Integration API role.');
            }
            if (count($roles) !== 1) {
                $validator->errors()->add('roles', 'The Integration API role cannot be combined with another role.');
            }

            $key = 'integration_allowed_team_ids';
            $value = $this->input($key);
            if (! is_string($value) || ! preg_match('/\A[1-9][0-9]*(?: *, *[1-9][0-9]*)*\z/', trim($value))) {
                $validator->errors()->add($key, 'Enter one or more site IDs separated by commas, for example 3, 7.');

                return;
            }

            $parts = array_map('trim', explode(',', $value));
            $ids = array_values(array_unique(array_map('intval', $parts)));
            if (array_filter($parts, fn ($id) => (string) (int) $id !== $id)
                || Team::withoutGlobalScope('team_id')->whereKey($ids)->count() !== count($ids)) {
                $validator->errors()->add($key, 'Each site ID must belong to an active site.');

                return;
            }

            $this->integrationTeamIds = $ids;
        });
    }
}
