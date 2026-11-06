<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FranchiseFeeRatePeriod;
use App\Services\FranchiseFeeRatePeriodService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class FranchiseFeeRatePeriodController extends Controller
{
    public function __construct(private readonly FranchiseFeeRatePeriodService $periods)
    {
    }

    public function store(Request $request)
    {
        $teamId = $this->authorizedTeamId();
        $validated = $this->validatePeriod($request);

        $this->periods->ensureNoOverlap(
            $teamId,
            $validated['effective_from'],
            $validated['effective_to'] ?? null,
            null,
            $validated['is_active'] ?? true,
        );

        FranchiseFeeRatePeriod::create([
            ...$validated,
            'team_id' => $teamId,
        ]);

        return back()->with('success', 'Franchise fee period added.');
    }

    public function update(Request $request, FranchiseFeeRatePeriod $franchiseFeeRatePeriod)
    {
        $teamId = $this->authorizedTeamId();
        abort_if((int) $franchiseFeeRatePeriod->team_id !== $teamId, Response::HTTP_FORBIDDEN);

        $validated = $this->validatePeriod($request);
        $this->periods->ensureNoOverlap(
            $teamId,
            $validated['effective_from'],
            $validated['effective_to'] ?? null,
            (int) $franchiseFeeRatePeriod->id,
            $validated['is_active'] ?? true,
        );

        $franchiseFeeRatePeriod->update($validated);

        return back()->with('success', 'Franchise fee period updated.');
    }

    public function destroy(FranchiseFeeRatePeriod $franchiseFeeRatePeriod)
    {
        $teamId = $this->authorizedTeamId();
        abort_if((int) $franchiseFeeRatePeriod->team_id !== $teamId, Response::HTTP_FORBIDDEN);

        $franchiseFeeRatePeriod->delete();

        return back()->with('success', 'Franchise fee period removed.');
    }

    private function authorizedTeamId(): int
    {
        abort_if(Gate::denies('teams_access'), Response::HTTP_FORBIDDEN);
        abort_unless(auth()->user()?->roles?->contains(1), Response::HTTP_FORBIDDEN);
        abort_if(!auth()->user()?->team_id, Response::HTTP_UNPROCESSABLE_ENTITY, 'Select a site before managing franchise fees.');

        return (int) auth()->user()->team_id;
    }

    private function validatePeriod(Request $request): array
    {
        return $request->validate([
            'royalty_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'marketing_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'effective_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_from'],
            'is_active' => ['sometimes', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
