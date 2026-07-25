<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WholesalerRate;
use App\Models\Season;
use App\Models\Service;
use App\Models\Wholesaler;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class WholesalerRateController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'id');
        $direction = $request->get('direction', 'desc');
        $teamId = auth()->user()->team_id;

        $rates = WholesalerRate::with(['season', 'wholesaler.team', 'service'])
            ->where('team_id', $teamId)
            ->when($search, function ($q) use ($search) {
                $q->whereHas('wholesaler', function ($qq) use ($search) {
                    $qq->where('name', 'like', '%' . $search . '%');
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();
        // dd($rates);
        $services = Service::with(['seasons' => fn ($query) => $query->where('team_id', $teamId)])
            ->select('id', 'name')
            ->where('team_id', $teamId)
            ->get();

        return Inertia::render('Admin/WholesalerRates/Index', [
            'rates' => $rates,
            'searchTerm' => $search,
            'sortColumn' => $sort,
            'sortDirection' => $direction,
            'services' => $services,
            'wholesalers' => Wholesaler::select('id', 'name')->where('team_id', $teamId)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $teamId = auth()->user()->team_id;

        $data = $request->validate([
            'wholesaler_id' => ['required', Rule::exists('wholesalers', 'id')->where('team_id', $teamId)],
            'service_id' => ['required', Rule::exists('services', 'id')->where('team_id', $teamId)],
            'season_id' => [
                'required',
                Rule::exists('seasons', 'id')
                    ->where('team_id', $teamId)
                    ->where('service_id', $request->input('service_id')),
            ],
            'price_adult' => ['required', 'numeric', 'min:0'],
            'price_child' => ['nullable', 'numeric', 'min:0'],
            'price_guide' => ['nullable', 'numeric', 'min:0'],
        ]);
        $data['team_id'] = $teamId;
        $this->assertUniqueRate($data);

        WholesalerRate::create($data);
        return redirect()->back()->with('message', 'Wholesaler rate created.');
    }

    public function update(Request $request, WholesalerRate $wholesalerRate)
    {
        $teamId = auth()->user()->team_id;
        abort_if((int) $wholesalerRate->team_id !== (int) $teamId, 403);

        $data = $request->validate([
            'wholesaler_id' => ['required', Rule::exists('wholesalers', 'id')->where('team_id', $teamId)],
            'service_id' => ['required', Rule::exists('services', 'id')->where('team_id', $teamId)],
            'season_id' => [
                'required',
                Rule::exists('seasons', 'id')
                    ->where('team_id', $teamId)
                    ->where('service_id', $request->input('service_id')),
            ],
            'price_adult' => ['required', 'numeric', 'min:0'],
            'price_child' => ['nullable', 'numeric', 'min:0'],
            'price_guide' => ['nullable', 'numeric', 'min:0'],
        ]);
        $data['team_id'] = $teamId;
        $this->assertUniqueRate($data, $wholesalerRate->id);

        $wholesalerRate->update($data);
        return redirect()->back()->with('message', 'Wholesaler rate updated.');
    }

    public function destroy(WholesalerRate $wholesalerRate)
    {
        abort_if((int) $wholesalerRate->team_id !== (int) auth()->user()->team_id, 403);

        $wholesalerRate->delete();
        return redirect()->back()->with('message', 'Wholesaler rate removed.');
    }

    private function assertUniqueRate(array $data, ?int $ignoreId = null): void
    {
        $exists = WholesalerRate::withTrashed()
            ->where('wholesaler_id', $data['wholesaler_id'])
            ->where('service_id', $data['service_id'])
            ->where('season_id', $data['season_id'])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if (!$exists) {
            return;
        }

        throw ValidationException::withMessages([
            'season_id' => 'This wholesaler, service, and season combination is already saved. Please choose different options or close this form and edit the existing record.',
        ]);
    }
}
