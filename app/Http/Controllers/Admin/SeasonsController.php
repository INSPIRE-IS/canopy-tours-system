<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSeasonRequest;
use App\Http\Requests\UpdateSeasonRequest;
use App\Models\Season;
use App\Models\Service;
use App\Services\QuestionnaireSeeder;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class SeasonsController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('seasons_access'), 403);
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'service_id');
        $sortDirection = $request->get('direction', 'asc');
        $seasons = Season::with([
            'service',
            'extras',
            'created_by',
            'updated_by',
            'team',
        ])
            ->where('service_id', 'like', '%'.$searchTerm.'%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);

        $services = Service::select('name', 'id')->get();

        return Inertia::render('Admin/Seasons/Index1', [
            'seasons' => $seasons,
            'services' => $services,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'fieldValues' => null,
        ]);
    }

    public function store(StoreSeasonRequest $request)
    {
        $validated = $request->validated();
        $extras = $validated['extras'] ?? [];
        unset($validated['extras']);

        DB::transaction(function () use ($validated, $extras) {
            $season = Season::create($validated);
            $this->syncExtras($season, $extras);
            app(QuestionnaireSeeder::class)->seedForSeason($season);
        });

        return redirect()->route('admin.seasons.index');
    }

    public function update(UpdateSeasonRequest $request, Season $season)
    {
        $validated = $request->validated();
        $shouldSyncExtras = array_key_exists('extras', $validated);
        $extras = $validated['extras'] ?? [];
        unset($validated['extras']);

        DB::transaction(function () use ($season, $validated, $extras, $shouldSyncExtras) {
            $season->update($validated);
            if ($shouldSyncExtras) {
                $this->syncExtras($season, $extras);
            }
        });

        return redirect()->route('admin.seasons.index');
    }

    public function show(Season $season)
    {
        abort_if(Gate::denies('season_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $season->load('created_by', 'updated_by', 'team');

        return view('Admin.seasons.show', compact('season'));
    }

    public function destroy(Season $season)
    {
        abort_if(Gate::denies('seasons_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $season->delete();

        return back();
    }

    private function syncExtras(Season $season, array $rows): void
    {
        $existing = $season->extras()->get()->keyBy('id');
        $keptIds = [];

        foreach (array_values($rows) as $sortOrder => $row) {
            $attributes = [
                'team_id' => $season->team_id,
                'name' => trim($row['name']),
                'description' => filled($row['description'] ?? null)
                    ? trim($row['description'])
                    : null,
                'unit_price' => round((float) $row['unit_price'], 2),
                'is_per_guest' => (bool) $row['is_per_guest'],
                'is_active' => (bool) $row['is_active'],
                'sort_order' => $sortOrder,
            ];

            $extra = ! empty($row['id']) ? $existing->get((int) $row['id']) : null;

            if ($extra) {
                $extra->update($attributes);
            } else {
                $extra = $season->extras()->create($attributes);
            }

            $keptIds[] = $extra->id;
        }

        $existing
            ->reject(fn ($extra) => in_array($extra->id, $keptIds, true))
            ->each->delete();
    }
}
