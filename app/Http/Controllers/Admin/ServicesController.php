<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyServiceRequest;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\File;
use App\Models\Service;
use App\Models\ServiceFieldValue;
use App\Models\ServiceField;
use App\Models\User;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ServicesController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('services_access'), 403);
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        /** @var \App\Models\User $user */
        $teamId = Session::get('team_id');
        $services = Service::with([
            'file', // single file relationship
            'servicefieldValues.field',
            'created_by',
            'updated_by',
            'team',
        ])
            ->where('name', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);
        $serviceFields = ServiceField::orderBy('type_category_id')
            ->orderBy('order')
            ->get();
        return Inertia::render('Admin/Services/Index1', [
            'services' => $services,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'serviceFields' => $serviceFields,
            'fieldValues' => null,
        ]);
    }

    public function store(StoreServiceRequest $request)
    {
        //dd($request->all());

        if (true) {
            $serviceFields = [];

            $fields = ServiceField::all();
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

                $serviceFieldRules["fields.{$field->id}"] = $rules;
            }

            $validator = Validator::make($request->all(), array_merge([
                'name' => 'required|string|max:255',
                'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // adjust as needed
            ], $serviceFieldRules));

            $validator->validate();

            // $season = Season::create([
            //     'service_id' => $request->service_id,
            //     'name' => $request->name,
            //     'created_by_id' => auth()->id(),
            //     'team_id' => auth()->user()->team_id,
            // ]);
            $service = Service::create($request->all());

            foreach ($request->fields as $fieldId => $value) {
                ServiceFieldValue::create([
                    'service_id' => $service->id,
                    'field_id' => $fieldId,
                    'value' => $value,
                    'team_id' => auth()->user()->team_id,
                ]);
            }
            // Handle file upload
            if ($request->hasFile('file')) {
                $uploaded = $request->file('file');
                $path = $uploaded->store("services/{$service->id}", 'public'); // stores in `storage/app/public/seasons/{id}`

                File::create([
                    'team_id'      => 1, // auth()->user()->team_id,
                    'table_name'   => 'services',
                    'table_row_id' => $service->id,
                    'file_name'    => $uploaded->getClientOriginalName(),
                    'file_type'    => $uploaded->getMimeType(),
                    'type_file_id' => 1, // optional enum/type
                    'path'         => $path,
                ]);
            }
        }

        return redirect()->route('admin.services.index');
    }

    public function update(UpdateServiceRequest $request, Service $service)
    {
        $serviceFieldRules = [];

        $fields = ServiceField::all();
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

            $serviceFieldRules["fields.{$field->id}"] = $rules;
        }

        $validator = Validator::make($request->all(), array_merge([
            'name' => 'required|string|max:255',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], $serviceFieldRules));

        $validator->validate();

        // $season->update([
        //     'season_id' => $request->season_id,
        //     'name' => $request->name,
        //     'updated_by_id' => auth()->id(),
        // ]);
        $service->update($request->all());

        foreach ($request->fields as $fieldId => $value) {
            ServiceFieldValue::updateOrCreate(
                [
                    'service_id' => $service->id,
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
            $path = $uploaded->store("services/{$service->id}", 'public');

            // Optional: delete old file(s) if you want to replace
            File::where([
                'table_name' => 'services',
                'table_row_id' => $service->id,
            ])->delete();

            File::create([
                'team_id'      => auth()->user()->team_id,
                'table_name'   => 'services',
                'table_row_id' => $service->id,
                'file_name'    => $uploaded->getClientOriginalName(),
                'file_type'    => $uploaded->getMimeType(),
                'type_file_id' => 1, // adjust based on your system
                'path'         => $path,
            ]);
        }




        return redirect()->route('admin.services.index');
    }

    public function show(Service $service)
    {
        abort_if(Gate::denies('service_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $service->load('created_by', 'updated_by', 'team');

        return view('Admin.services.show', compact('service'));
    }

    public function destroy(Service $service)
    {
        abort_if(Gate::denies('service_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $service->delete();

        return back();
    }
}
