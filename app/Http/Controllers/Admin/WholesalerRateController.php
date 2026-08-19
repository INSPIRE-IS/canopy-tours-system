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
            ->whereHas('wholesaler')
            ->when($search, function ($q) use ($search) {
                $q->whereHas('wholesaler', function ($qq) use ($search) {
                    $qq->where('name', 'like', '%' . $search . '%');
                });
            })
            ->orderBy($sort, $direction)
            ->orderBy('effective_from')
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
            'name' => ['nullable', 'string', 'max:120'],
            'wholesaler_id' => ['required', Rule::exists('wholesalers', 'id')->where('team_id', $teamId)->whereNull('deleted_at')],
            'service_id' => ['required', Rule::exists('services', 'id')->where('team_id', $teamId)],
            'season_id' => [
                'required',
                Rule::exists('seasons', 'id')
                    ->where('team_id', $teamId)
                    ->where('service_id', $request->input('service_id')),
            ],
            'price_adult' => ['required', 'numeric', 'min:0'],
            'price_child' => ['required', 'numeric', 'min:0'],
            'price_guide' => ['nullable', 'numeric', 'min:0'],
            'bill_back' => ['nullable', 'boolean'],
            'is_prepaid_voucher' => ['nullable', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
        ]);
        $data['team_id'] = $teamId;
        $data['name'] = trim($data['name'] ?? '') ?: 'Default rate';
        $data['bill_back'] = $request->boolean('bill_back');
        $data['is_prepaid_voucher'] = $request->boolean('is_prepaid_voucher');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['price_guide'] = $data['price_guide'] ?? 0;
        $this->assertNoOverlappingActiveRate($data);

        WholesalerRate::create($data);
        return redirect()->back()->with('message', 'Wholesaler rate created.');
    }

    public function update(Request $request, WholesalerRate $wholesalerRate)
    {
        $teamId = auth()->user()->team_id;
        abort_if((int) $wholesalerRate->team_id !== (int) $teamId, 403);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'wholesaler_id' => ['required', Rule::exists('wholesalers', 'id')->where('team_id', $teamId)->whereNull('deleted_at')],
            'service_id' => ['required', Rule::exists('services', 'id')->where('team_id', $teamId)],
            'season_id' => [
                'required',
                Rule::exists('seasons', 'id')
                    ->where('team_id', $teamId)
                    ->where('service_id', $request->input('service_id')),
            ],
            'price_adult' => ['required', 'numeric', 'min:0'],
            'price_child' => ['required', 'numeric', 'min:0'],
            'price_guide' => ['nullable', 'numeric', 'min:0'],
            'bill_back' => ['nullable', 'boolean'],
            'is_prepaid_voucher' => ['nullable', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
        ]);
        $data['team_id'] = $teamId;
        $data['name'] = trim($data['name'] ?? '') ?: 'Default rate';
        $data['bill_back'] = $request->boolean('bill_back');
        $data['is_prepaid_voucher'] = $request->boolean('is_prepaid_voucher');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['price_guide'] = $data['price_guide'] ?? 0;
        $this->assertNoOverlappingActiveRate($data, $wholesalerRate->id);

        $wholesalerRate->update($data);
        return redirect()->back()->with('message', 'Wholesaler rate updated.');
    }

    public function destroy(WholesalerRate $wholesalerRate)
    {
        abort_if((int) $wholesalerRate->team_id !== (int) auth()->user()->team_id, 403);

        $wholesalerRate->delete();
        return redirect()->back()->with('message', 'Wholesaler rate removed.');
    }

    private function assertNoOverlappingActiveRate(array $data, ?int $ignoreId = null): void
    {
        if (empty($data['is_active'])) {
            return;
        }

        $newFrom = $data['effective_from'] ?? null;
        $newTo = $data['effective_to'] ?? null;

        $overlap = WholesalerRate::query()
            ->where('wholesaler_id', $data['wholesaler_id'])
            ->where('service_id', $data['service_id'])
            ->where('season_id', $data['season_id'])
            ->where('team_id', $data['team_id'])
            ->where('is_active', true)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where(function ($query) use ($newTo) {
                $query->whereNull('effective_from');
                if ($newTo) {
                    $query->orWhereDate('effective_from', '<=', $newTo);
                } else {
                    $query->orWhereNotNull('effective_from');
                }
            })
            ->where(function ($query) use ($newFrom) {
                $query->whereNull('effective_to');
                if ($newFrom) {
                    $query->orWhereDate('effective_to', '>=', $newFrom);
                } else {
                    $query->orWhereNotNull('effective_to');
                }
            })
            ->exists();

        if (!$overlap) {
            return;
        }

        throw ValidationException::withMessages([
            'effective_from' => 'This active wholesaler rate overlaps another active rate for the same wholesaler, service, and season. Archive the old rate or adjust the dates first.',
        ]);
    }
}
