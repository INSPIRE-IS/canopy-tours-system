<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FranchiseFeeRatePeriod;
use App\Models\Lookup;
use App\Models\LookupItem;
use App\Services\FranchiseFeeRateResolver;
use Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LookupsController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('teams_access'), 403);

        $teamId = auth()->user()->team_id;
        LookupItem::ensureSettingsForTeam($teamId);
        $lookup = Lookup::where('slug', 'settings')->first();

        $items = $lookup
            ? LookupItem::defaultsForTeam($teamId, $lookup->id)
            : collect();

        $settings = $items
            ->reject(fn($item) => in_array($item->slug, $this->reportSettingSlugs(), true))
            ->sortBy('id')
            ->map(function ($item) {
                $label = $item->name ?? $item->slug;
                $info = $item->info ?? $item->description ?? '';
                if (trim((string) $info) === trim((string) $label)) {
                    $info = '';
                }
                return [
                    'slug' => $item->slug,
                    'name' => $label,
                    'description' => $info,
                    'info' => $info,
                    'value' => (bool) ($item->value ?? 0),
                ];
            })
            ->values();

        $canManageReportSettings = auth()->user()->roles->contains(1);
        $franchiseFeePeriods = collect();
        if ($canManageReportSettings && $teamId) {
            $franchiseFeePeriods = FranchiseFeeRatePeriod::withoutGlobalScope('team_id')
                ->where('team_id', $teamId)
                ->orderByDesc('effective_from')
                ->get()
                ->map(fn (FranchiseFeeRatePeriod $period) => [
                    'id' => $period->id,
                    'royalty_percent' => (float) $period->royalty_percent,
                    'marketing_percent' => (float) $period->marketing_percent,
                    'effective_from' => $period->effective_from?->toDateString(),
                    'effective_to' => $period->effective_to?->toDateString(),
                    'is_active' => (bool) $period->is_active,
                    'is_current' => $period->is_active
                        && $period->effective_from?->lte(today())
                        && ($period->effective_to === null || $period->effective_to->gte(today())),
                    'note' => $period->note,
                ])
                ->values();
        }
        $franchiseFeeDefaults = [
            'royalty_percent' => FranchiseFeeRateResolver::FALLBACK_ROYALTY_PERCENT,
            'marketing_percent' => FranchiseFeeRateResolver::FALLBACK_MARKETING_PERCENT,
        ];

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'canManageReportSettings' => $canManageReportSettings,
            'franchiseFeePeriods' => $franchiseFeePeriods,
            'franchiseFeeDefaults' => $franchiseFeeDefaults,
        ]);
    }

    public function store(Request $request)
    {
        abort_if(Gate::denies('teams_access'), 403);

        $lookup = Lookup::firstOrCreate(
            ['slug' => 'settings'],
            ['name' => 'Settings', 'team_id' => null]
        );

        $teamId = auth()->user()->team_id;
        $today = now()->toDateString();

        LookupItem::ensureSettingsForTeam($teamId);
        $items = LookupItem::defaultsForTeam($teamId, $lookup->id);
        $booleanItems = $items->reject(fn($item) => in_array($item->slug, $this->reportSettingSlugs(), true));
        $rules = $booleanItems->mapWithKeys(fn($item) => [$item->slug => 'boolean'])->all();
        if (auth()->user()->roles->contains(1) && $request->has('report_settings')) {
            $rules['report_settings.franchise_royalty_percent'] = ['nullable', 'numeric', 'min:0', 'max:100'];
            $rules['report_settings.franchise_marketing_percent'] = ['nullable', 'numeric', 'min:0', 'max:100'];
        }
        $validated = $request->validate($rules);

        foreach ($booleanItems as $item) {
            $slug = $item->slug;
            $value = (bool) ($validated[$slug] ?? $item->value ?? 0);

            LookupItem::updateOrCreate(
                [
                    'lookup_id' => $lookup->id,
                    'slug' => $slug,
                    'team_id' => $teamId,
                ],
                [
                    'name' => $item->name ?? $slug,
                    'info' => $item->info ?? $item->name ?? $slug,
                    'rate' => null,
                    'multiplier' => null,
                    'value' => $value ? 1 : 0,
                    'effective_from' => $today,
                    'effective_to' => null,
                    'is_default' => true,
                    'is_active' => true,
                ]
            );
        }

        if (auth()->user()->roles->contains(1) && $request->has('report_settings')) {
            foreach ($this->reportSettingSlugs() as $slug) {
                $source = $items->firstWhere('slug', $slug);
                $rate = $validated['report_settings'][$slug] ?? null;

                LookupItem::updateOrCreate(
                    [
                        'lookup_id' => $lookup->id,
                        'slug' => $slug,
                        'team_id' => $teamId,
                    ],
                    [
                        'name' => $source->name ?? str_replace('_', ' ', $slug),
                        'info' => $source->info ?? $source->name ?? $slug,
                        'rate' => $rate === null || $rate === '' ? null : (float) $rate,
                        'multiplier' => null,
                        'value' => null,
                        'effective_from' => $today,
                        'effective_to' => null,
                        'is_default' => true,
                        'is_active' => true,
                    ]
                );
            }
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Settings updated.');
    }

    private function reportSettingSlugs(): array
    {
        return [
            'franchise_royalty_percent',
            'franchise_marketing_percent',
        ];
    }
}
