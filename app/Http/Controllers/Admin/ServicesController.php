<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyServiceRequest;
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
use Illuminate\Support\Facades\Log;

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

    public function store(Request $request)
    {
        // dd($request->all());

        if (true) {
            $serviceFields = [];
            $serviceFieldRules = [];

            $fields = ServiceField::all();
            $fieldsById = $fields->keyBy('id');
            foreach ($fields as $field) {
                $rules = [];

                if ($field->required && !$this->isOptionalFrontendContentField($field)) {
                    $rules[] = 'required';
                } else {
                    $rules[] = 'nullable';
                }

                $rules = array_merge($rules, $this->serviceFieldTypeRules($field));

                $serviceFieldRules["fields.{$field->id}"] = $rules;
            }
            $data = $request->validate(array_merge([
                'name' => 'required|string|max:255',
                'tag' => [
                    // 'string',
                    // 'max:255',
                ],
                'duration' => [
                    //'date_format:' . config('panel.time_format'),
                ],
                'capacity' => [
                    'required',
                    'integer',
                    'min: 1',
                    'max:100',
                ],
                'pillbox_conditions' => [
                    'string',
                    'nullable',
                ],
                'allow_internal_capacity_override' => [
                    'required',
                ],
                'is_online_bookable' => [
                    'nullable',
                    'boolean',
                ],
                'show_on_admin_bookings' => [
                    'nullable',
                    'boolean',
                ],
                'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // adjust as needed
                'fields' => 'array',
            ], $serviceFieldRules));

            Log::info($data);
            $data['allow_internal_capacity_override'] =
                $request->boolean('allow_internal_capacity_override');
            $data['is_online_bookable'] = $request->boolean('is_online_bookable', true);
            $data['show_on_admin_bookings'] = $request->boolean('show_on_admin_bookings', true);
            Log::info($data);
            $service = Service::create($data);
            // $service = Service::create($request->all());

            foreach ($request->input('fields', []) as $fieldId => $value) {
                $field = $fieldsById->get((int) $fieldId);
                ServiceFieldValue::create([
                    'service_id' => $service->id,
                    'field_id' => $fieldId,
                    'value' => $this->normalizeServiceFieldValue($value, $field),
                    'team_id' => auth()->user()->team_id,
                ]);
            }
            // Handle file upload
            if ($request->hasFile('file')) {
                $uploaded = $request->file('file');
                $path = $uploaded->store("services/{$service->id}", 'public'); // stores in `storage/app/public/seasons/{id}`

                File::create([
                    'team_id'      => auth()->user()->team_id,
                    'table_name'   => 'services',
                    'table_row_id' => $service->id,
                    'file_name'    => $uploaded->getClientOriginalName(),
                    'file_type'    => $uploaded->getMimeType(),
                    'type_file_id' => 2, // optional enum/type
                    'path'         => $path,
                ]);
            }
        }

        return redirect()->route('admin.services.index');
    }

    public function update(Request $request, Service $service)
    {
        $serviceFieldRules = [];

        $fields = ServiceField::all();
        $fieldsById = $fields->keyBy('id');
        foreach ($fields as $field) {
            $rules = [];

            if ($field->required && !$this->isOptionalFrontendContentField($field)) {
                $rules[] = 'required';
            } else {
                $rules[] = 'nullable';
            }

            $rules = array_merge($rules, $this->serviceFieldTypeRules($field));

            $serviceFieldRules["fields.{$field->id}"] = $rules;
        }

        $data = $request->validate(array_merge([
            'name' => 'required|string|max:255',
            'tag' => [
                // 'string',
                // 'max:255',
            ],
            'duration' => [
                //'date_format:' . config('panel.time_format'),
            ],
            'capacity' => [
                'required',
                'integer',
                'min: 1',
                'max:100',
            ],
            'pillbox_conditions' => [
                'string',
                'nullable',
            ],
            'allow_internal_capacity_override' => [
                'required',
            ],
            'is_online_bookable' => [
                'nullable',
                'boolean',
            ],
            'show_on_admin_bookings' => [
                'nullable',
                'boolean',
            ],
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'fields' => 'array',
        ], $serviceFieldRules));

        $data['allow_internal_capacity_override'] =
            $request->boolean('allow_internal_capacity_override');
        $data['is_online_bookable'] = $request->has('is_online_bookable')
            ? $request->boolean('is_online_bookable')
            : (bool) $service->is_online_bookable;
        $data['show_on_admin_bookings'] = $request->has('show_on_admin_bookings')
            ? $request->boolean('show_on_admin_bookings')
            : (bool) ($service->show_on_admin_bookings ?? true);

        // $season->update([
        //     'season_id' => $request->season_id,
        //     'name' => $request->name,
        //     'updated_by_id' => auth()->id(),
        // ]);
        $service->update($data);

        foreach ($request->input('fields', []) as $fieldId => $value) {
            $field = $fieldsById->get((int) $fieldId);
            ServiceFieldValue::updateOrCreate(
                [
                    'service_id' => $service->id,
                    'field_id' => $fieldId,
                ],
                [
                    'value' => $this->normalizeServiceFieldValue($value, $field),
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
        abort_if(Gate::denies('services_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $service->load('created_by', 'updated_by', 'team');

        return view('Admin.services.show', compact('service'));
    }

    public function destroy(Service $service)
    {
        abort_if(Gate::denies('services_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $service->delete();

        return back();
    }

    private function isOptionalFrontendContentField(ServiceField $field): bool
    {
        return in_array($field->slug, [
            'description',
            'condition_text',
            'activity_description',
            'arrangements',
            'requirements',
            'inclusions',
            'exclusions',
            'notes',
            'lat_lon_start',
            'lat_long_end',
            'minimum_age',
        ], true);
    }

    private function serviceFieldTypeRules(ServiceField $field): array
    {
        if ($field->slug === 'minimum_age') {
            return ['integer', 'min:0', 'max:120'];
        }

        return match ((int) $field->type_data_id) {
            1, 2 => ['string'],
            3 => ['numeric'],
            default => [],
        };
    }

    private function normalizeServiceFieldValue(mixed $value, ?ServiceField $field): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!$field || !$this->isOptionalFrontendContentField($field) || !is_string($value)) {
            return $value;
        }

        $plainText = trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', html_entity_decode(strip_tags($value)))));

        return in_array($plainText, ['', '.'], true) ? null : $value;
    }
}
