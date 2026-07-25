<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lookup;
use App\Models\LookupItem;
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

        $reportSettings = $items
            ->filter(fn($item) => in_array($item->slug, $this->reportSettingSlugs(), true))
            ->sortBy('id')
            ->map(function ($item) {
                return [
                    'slug' => $item->slug,
                    'name' => $item->name ?? $item->slug,
                    'description' => $item->info ?? '',
                    'rate' => $item->rate,
                    'fallback' => $item->slug === 'franchise_royalty_percent' ? 10 : 2.5,
                ];
            })
            ->values();
        $canManageReportSettings = auth()->user()->roles->contains(1);

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'reportSettings' => $reportSettings,
            'canManageReportSettings' => $canManageReportSettings,
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
        if (auth()->user()->roles->contains(1)) {
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

        if (auth()->user()->roles->contains(1)) {
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
