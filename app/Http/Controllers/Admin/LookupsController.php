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
    private function settingsDefinition(): array
    {
        return [
            [
                'slug' => 'enable_pickup_dropoff',
                'name' => 'Enable Pickup/Dropoff',
                'description' => 'Show pickup/dropoff fields on booking forms.',
                'info' => 'Show pickup/dropoff fields on booking forms.',
                'default' => true,
            ],
            [
                'slug' => 'auto_send_invoice_on_booking',
                'name' => 'Auto Send Invoice on Booking',
                'description' => 'Send the invoice email automatically when a booking is created or updated.',
                'info' => 'Send the invoice email automatically when a booking is created or updated.',
                'default' => true,
            ],
            [
                'slug' => 'auto_send_quote_on_booking',
                'name' => 'Auto Send Quote on Booking',
                'description' => 'Send a quote email automatically when a booking is created or updated.',
                'info' => 'Send a quote email automatically when a booking is created or updated.',
                'default' => false,
            ],
            [
                'slug' => 'enable_assign_staff',
                'name' => 'Enable Assign Staff',
                'description' => 'Show assigned staff section on booking add view.',
                'info' => 'Show assigned staff section on booking add view.',
                'default' => true,
            ],
        ];
    }

    public function index()
    {
        abort_if(Gate::denies('teams_access'), 403);

        $teamId = auth()->user()->team_id;
        $lookup = Lookup::where('slug', 'settings')->first();

        $definitions = $this->settingsDefinition();
        $items = $lookup
            ? LookupItem::defaultsForTeam($teamId, $lookup->id)
            : collect();

        $settings = collect($definitions)->map(function ($setting) use ($items) {
            $item = $items->firstWhere('slug', $setting['slug']);
            $value = $item?->multiplier;

            return [
                'slug' => $setting['slug'],
                'name' => $setting['name'],
                'description' => $setting['description'],
                'info' => $item?->info ?? $setting['info'] ?? $setting['description'],
                'value' => $value === null ? (bool) $setting['default'] : (bool) $value,
            ];
        })->values();

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function store(Request $request)
    {
        abort_if(Gate::denies('teams_access'), 403);

        $validated = $request->validate([
            'enable_pickup_dropoff' => 'boolean',
            'auto_send_invoice_on_booking' => 'boolean',
            'auto_send_quote_on_booking' => 'boolean',
            'enable_assign_staff' => 'boolean',
        ]);

        $lookup = Lookup::firstOrCreate(
            ['slug' => 'settings'],
            ['name' => 'Settings', 'team_id' => null]
        );

        $teamId = auth()->user()->team_id;
        $today = now()->toDateString();

        foreach ($this->settingsDefinition() as $setting) {
            $slug = $setting['slug'];
            $value = (bool) ($validated[$slug] ?? $setting['default']);

            LookupItem::updateOrCreate(
                [
                    'lookup_id' => $lookup->id,
                    'slug' => $slug,
                    'team_id' => $teamId,
                ],
                [
                    'name' => $setting['name'],
                    'info' => $setting['info'] ?? $setting['description'],
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
