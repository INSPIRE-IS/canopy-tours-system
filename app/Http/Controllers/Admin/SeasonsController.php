<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSeasonRequest;
use App\Http\Requests\UpdateSeasonRequest;
use App\Models\Season;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use App\Models\File;
use App\Models\SeasonField;
use App\Models\SeasonFieldValue;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;

class SeasonsController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('season_access'), 403);
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        /** @var \App\Models\User $user */
        $teamId = Session::get('team_id');
        $seasons = Season::with([
            'service',
            'file', // single file relationship
            'seasonfieldValues.field',
            'created_by',
            'updated_by',
            'team',
        ])
            ->where('name', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);

        $seasonFields = SeasonField::orderBy('type_category_id')
            ->orderBy('order')
            ->get();
        $services = Service::select('name', 'id')->get();

        // $fieldValues = $season->fieldValues->pluck('value', 'field_id');
        // $seasons->getCollection()->transform(function ($season) {
        //     $fields = $season->fieldValues->mapWithKeys(function ($fv) {
        //         return [
        //             $fv->field->name => $fv->value,
        //         ];
        //     });
        //     return [
        //         'id' => $season->id,
        //         'name' => $season->name,
        //         'fields' => $fields,
        //         //'created_by'  => $created_by, 'updated_by'  => $updated_by, 'team'  => $team,
        //         // add more if needed
        //     ];
        // });
        // dd($seasons);
        return Inertia::render('Admin/Seasons/Index1', [
            'seasons' => $seasons,
            'services' => $services,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'seasonFields' => $seasonFields,
            'fieldValues' => null,
        ]);
    }

    public function store(StoreSeasonRequest $request)
    {
        //dd($request->all());
        //$season = Season::create($request->all());

        if (true) {
            $seasonFieldRules = [];

            $fields = SeasonField::all();
            foreach ($fields as $field) {
                $rules = [];

                if ($field->required) {
                    $rules[] = 'required';
                } else {
                    $rules[] = 'nullable';
                }

                // Optionally add more logic based on type_data_id
                switch ($field->type_data_id) {
                    case 1:
                        $rules[] = 'string';
                        break;
                    case 2:
                        $rules[] = 'string';
                        break;
                    case 3:
                        $rules[] = 'numeric';
                        break;
                        // Extend as needed
                }

                $seasonFieldRules["fields.{$field->id}"] = $rules;
            }

            $validator = Validator::make($request->all(), array_merge([
                'name' => 'required|string|max:255',
                'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // adjust as needed
            ], $seasonFieldRules));

            $validator->validate();

            $season = Season::create([
                'service_id' => $request->service_id,
                'name' => $request->name,
                'created_by_id' => auth()->id(),
                'team_id' => auth()->user()->team_id,
            ]);

            foreach ($request->fields as $fieldId => $value) {
                SeasonFieldValue::create([
                    'season_id' => $season->id,
                    'field_id' => $fieldId,
                    'value' => $value,
                    'team_id' => auth()->user()->team_id,
                ]);
            }
            // Handle file upload
            if ($request->hasFile('file')) {
                $uploaded = $request->file('file');
                $path = $uploaded->store("seasons/{$season->id}", 'public'); // stores in `storage/app/public/seasons/{id}`

                File::create([
                    'team_id'      => 1, // auth()->user()->team_id,
                    'table_name'   => 'seasons',
                    'table_row_id' => $season->id,
                    'file_name'    => $uploaded->getClientOriginalName(),
                    'file_type'    => $uploaded->getMimeType(),
                    'type_file_id' => 1, // optional enum/type
                    'path'         => $path,
                ]);
            }
        }

        //return redirect()->back()->with('success', 'Season created successfully');

        return redirect()->route('admin.seasons.index');
    }

    public function update(UpdateSeasonRequest $request, Season $season)
    {
        ///dd('here');
        //dd($request->all());
        //$season->update($request->all());
        $seasonFieldRules = [];

        $fields = SeasonField::all();
        foreach ($fields as $field) {
            $rules = [];

            if ($field->required) {
                $rules[] = 'required';
            } else {
                $rules[] = 'nullable';
            }

            switch ($field->type_data_id) {
                case 1:
                    $rules[] = 'string';
                    break;
                case 2:
                    $rules[] = 'string';
                    break;
                case 3:
                    $rules[] = 'numeric';
                    break;
            }

            $seasonFieldRules["fields.{$field->id}"] = $rules;
        }

        $validator = Validator::make($request->all(), array_merge([
            'name' => 'required|string|max:255',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], $seasonFieldRules));

        $validator->validate();

        $season->update([
            'service_id' => $request->service_id,
            'name' => $request->name,
            'updated_by_id' => auth()->id(),
        ]);

        foreach ($request->fields as $fieldId => $value) {
            SeasonFieldValue::updateOrCreate(
                [
                    'season_id' => $season->id,
                    'field_id' => $fieldId,
                ],
                [
                    'value' => $value,
                    'team_id' => auth()->user()->team_id,
                ]
            );
        }

        // File upload
        if ($request->hasFile('file')) {
            $uploaded = $request->file('file');
            $path = $uploaded->store("seasons/{$season->id}", 'public');

            // Optional: delete old file(s) if you want to replace
            File::where([
                'table_name' => 'seasons',
                'table_row_id' => $season->id,
            ])->delete();

            File::create([
                'team_id'      => auth()->user()->team_id,
                'table_name'   => 'seasons',
                'table_row_id' => $season->id,
                'file_name'    => $uploaded->getClientOriginalName(),
                'file_type'    => $uploaded->getMimeType(),
                'type_file_id' => 1, // adjust based on your system
                'path'         => $path,
            ]);
        }
        // return redirect()->back()->with('success', 'Season updated successfully');

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
        abort_if(Gate::denies('season_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $season->delete();

        return back();
    }
}
