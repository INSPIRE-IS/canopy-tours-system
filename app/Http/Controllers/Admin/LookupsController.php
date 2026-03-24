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
        $lookup = Lookup::where('slug', 'settings')->first();

        $items = $lookup
            ? LookupItem::defaultsForTeam($teamId, $lookup->id)
            : collect();

        $settings = $items
            ->sortBy('id')
            ->map(function ($item) {
                $label = $item->name ?? $item->slug;
                $info = $item->info ?? $label;
                return [
                    'slug' => $item->slug,
                    'name' => $label,
                    'description' => $info,
                    'info' => $info,
                    'value' => (bool) ($item->multiplier ?? 0),
                ];
            })
            ->values();

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
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

        $items = LookupItem::defaultsForTeam($teamId, $lookup->id);
        $rules = $items->mapWithKeys(fn($item) => [$item->slug => 'boolean'])->all();
        $validated = $request->validate($rules);

        foreach ($items as $item) {
            $slug = $item->slug;
            $value = (bool) ($validated[$slug] ?? $item->multiplier ?? 0);

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
                    'multiplier' => $value ? 1 : 0,
                    'effective_from' => $today,
                    'effective_to' => null,
                    'is_default' => true,
                    'is_active' => true,
                ]
            );
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Settings updated.');
    }
}
