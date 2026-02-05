<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyBoardingPassRequest;
use App\Models\Season;
use App\Models\Service;
use App\Models\BoardingPassSection;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

use App\Models\BoardingPassTemplate;


class BoardingPassController extends Controller
{
    public function index(Request $request)
    {
        //abort_if(Gate::denies('boardingpass_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(Gate::denies('boarding_pass_templates_access'), 403);
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        /** @var \App\Models\User $user */
        $boardingpassId = Session::get('boardingpass_id');
        $boardingpass = BoardingPassTemplate::with('sections')
            ->where('name', 'like', '%' . $searchTerm . '%')
            ->where('team_id', auth()->user()->team_id)
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);

        $sectionTitles = collect(BoardingPassSection::SECTION_TITLES)
            ->map(fn($label, $key) => ['id' => $key, 'name' => $label])
            ->values();
        $sectionIcons = collect(BoardingPassSection::SECTION_ICONS)
            ->map(fn($path, $key) => ['id' => $key, 'name' => $key, 'path' => $path])
            ->values();

        $services = Service::select('id', 'name', 'team_id')
            ->where('team_id', auth()->user()->team_id)
            ->orderBy('name')
            ->get();
        $seasons = Season::select('id', 'name', 'service_id', 'team_id')
            ->where('team_id', auth()->user()->team_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/BoardingPass/Index', [
            'boardingpass' => $boardingpass,
            'services' => $services,
            'seasons' => $seasons,
            'sectionTitles' => $sectionTitles,
            'sectionIcons' => $sectionIcons,
            'sectionTitleIconMap' => BoardingPassSection::SECTION_TITLE_ICON_MAP,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }


    public function store(Request $request)
    {
        abort_if(Gate::denies('boardingpass_create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'footer' => ['nullable', 'string'],
            'service_id' => ['nullable', 'exists:services,id'],
            'season_id' => ['nullable', 'exists:seasons,id'],
            'sections' => ['array'],
            'sections.*.title' => ['required', 'string'],
            'sections.*.icon_path' => ['nullable', 'string'],
            'sections.*.items' => ['array'],
            'sections.*.items.*' => ['string'],
        ]);

        $data['team_id'] = auth()->user()->team_id;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['is_default'] = $data['is_default'] ?? true;

        $sections = $data['sections'] ?? [];
        unset($data['sections']);

        $template = BoardingPassTemplate::create($data);

        foreach ($sections as $index => $sectionData) {
            BoardingPassSection::create([
                'boarding_pass_template_id' => $template->id,
                'title' => $sectionData['title'],
                'icon_path' => $sectionData['icon_path'] ?? null,
                'items' => $sectionData['items'] ?? [],
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }

        return redirect()->route('admin.boardingpass.index');
    }

    public function update(Request $request, BoardingPassTemplate $boardingpass)
    {
        abort_if(Gate::denies('boardingpass_edit'), 403);
        if ($boardingpass->team_id !== auth()->user()->team_id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'footer' => ['nullable', 'string'],
            'service_id' => ['nullable', 'exists:services,id'],
            'season_id' => ['nullable', 'exists:seasons,id'],
            'sections' => ['array'],
            'sections.*.title' => ['required', 'string'],
            'sections.*.icon_path' => ['nullable', 'string'],
            'sections.*.items' => ['array'],
            'sections.*.items.*' => ['string'],
        ]);

        $data['team_id'] = auth()->user()->team_id;
        $sections = $data['sections'] ?? [];
        unset($data['sections']);

        $boardingpass->update($data);

        $boardingpass->sections()->delete();
        foreach ($sections as $index => $sectionData) {
            BoardingPassSection::create([
                'boarding_pass_template_id' => $boardingpass->id,
                'title' => $sectionData['title'],
                'icon_path' => $sectionData['icon_path'] ?? null,
                'items' => $sectionData['items'] ?? [],
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }

        return redirect()->route('admin.boardingpass.index');
    }


    public function show(BoardingPassTemplate $boardingpass)
    {
        abort_if(Gate::denies('boardingpass_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($boardingpass->team_id !== auth()->user()->team_id) {
            abort(403);
        }

        return view('Admin.boardingpass.show', compact('boardingpass'));
    }

    public function destroy(BoardingPassTemplate $boardingpass)
    {
        abort_if(Gate::denies('boardingpass_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($boardingpass->team_id !== auth()->user()->team_id) {
            abort(403);
        }

        $boardingpass->delete();

        return back();
    }
}
