<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use App\Models\QuestionnaireField;
use Gate;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Continent;
use App\Models\Country;
use App\Models\QuestionnaireAnswer;
use App\Models\Season;
use App\Models\Service;
use App\Models\State;
use App\Models\TypeData;
use App\Models\TypeInput;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuestionnaireController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('questionnaires_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');

        $questionnaires = Questionnaire::query()
            ->with('fields')
            ->where('name', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);

        //$questionnaires
        return Inertia::render('Admin/Questionnaires/Index', [
            'questionnaires' => $questionnaires,
            'inputTypes' => TypeInput::all(), //  ['text', 'textarea', 'number', 'select', 'radio', 'checkbox', 'date'],
            'dataTypes' => TypeData::all(), // ['string', 'integer', 'boolean', 'date'],
            'services' => Service::with('seasons')->get(),
            'questionnaireFields' => QuestionnaireField::all(),
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'], // 'required', 
            'description' => ['nullable', 'string'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'season_id' => ['required', 'integer', 'exists:seasons,id'],
            'fields' => ['required', 'array'],
            // 'fields.*.label' => ['required', 'string'],
            // 'fields.*.input_type' => ['required', 'string'],
            // 'fields.*.data_type' => ['required', 'string'],
            // 'fields.*.required' => ['boolean'],
            // 'fields.*.options' => ['nullable', 'string'],
            'fields.*.id' => ['required', 'integer', 'exists:questionnaire_fields,id'],
            'fields.*.visable' => ['required', 'boolean'],
            'fields.*.sort_order' => ['required', 'integer'],
            'fields.*.required' => ['required', 'boolean'],
        ]);

        $name = Service::findOrFail($validated['service_id'])->name;
        $description = Season::findOrFail($validated['season_id'])->name;

        // create questionnaire
        $questionnaire = Questionnaire::updateOrCreate(
            [
                'team_id' => $request->team_id ?? null,
                'service_id' => $validated['service_id'],
                'season_id' => $validated['season_id'],
            ],
            [
                'name' => $name,
                'description' => $description,
            ]
        );


        $syncData = collect($validated['fields'])->mapWithKeys(function ($field) {
            return [
                $field['id'] => [
                    'visable'    => $field['visable'],
                    'sort_order' => $field['sort_order'],
                    'required'   => $field['required'],
                ]
            ];
        });

        $questionnaire->fields()->sync($syncData);


        // store fields
        // foreach ($validated['fields'] as $field) {
        //     $questionnaire->fields()->create([
        //         'question' => $field['question'],
        //         'slug' => $field['slug'],
        //         'type_input_id' => $field['input_type'],
        //         'type_data_id' => $field['data_type'],
        //         'required' => $field['required'] ?? false,
        //         'options' => $field['options'] ?? null,
        //     ]);
        // }

        // 'required',
        // 'hidden',
        // 'sort_order',

        return redirect()->route('admin.questionnaire.index');
    }

    public function update(Request $request, Questionnaire $questionnaire)
    {

        //dd($request->all());
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'], // 'required', 
            'description' => ['nullable', 'string'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'season_id' => ['required', 'integer', 'exists:seasons,id'],
            'fields'      => ['array'],
            'fields.*.id' => ['required', 'integer', 'exists:questionnaire_fields,id'],
            'fields.*.visable' => ['required', 'boolean'],
            'fields.*.sort_order' => ['required', 'integer'],
            'fields.*.required' => ['boolean'],
        ]);

        $name = Service::findOrFail($validated['service_id'])->name;
        $description = Season::findOrFail($validated['season_id'])->name;

        $questionnaire = Questionnaire::updateOrCreate(
            [
                'team_id' => $request->team_id ?? null,
                'service_id' => $validated['service_id'],
                'season_id' => $validated['season_id'],
            ],
            [
                'name' => $name,
                'description' => $description,
            ]
        );

        $syncData = collect($validated['fields'])->mapWithKeys(function ($field) {
            return [
                $field['id'] => [
                    'visable'    => $field['visable'],
                    'sort_order' => $field['sort_order'],
                    'required'   => $field['required'],
                ]
            ];
        });

        $questionnaire->fields()->sync($syncData);

        return redirect()->route('admin.questionnaire.index')
            ->with('success', 'Questionnaire updated successfully.');
    }

    public function show(Questionnaire $questionnaire)
    {
        //dd($questionnaire);
        abort_if(Gate::denies('questionnaires_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // Load with fields for preview
        $questionnaire->load([
            'fields.Typeinput', // eager load input type relation
            'fields.Typedata',  // eager load data type relation
        ]);

        return Inertia::render('Admin/Questionnaires/Show', [
            'questionnaire' => $questionnaire,
            'questions' => $questionnaire->fields, // alias fields to questions for front-end clarity
        ]);
    }

    public function answerStore(Request $request)
    {
        $validated = $request->validate([
            'questionnaire_id' => ['required', 'integer'],
            'answers' => ['required', 'array'],
        ]);

        $questionnaire = Questionnaire::findOrFail($validated['questionnaire_id']);

        foreach ($validated['answers'] as $questionId => $answer) {
            QuestionnaireAnswer::create([
                'questionnaire_id' => $questionnaire->id,
                'questionnaire_field_id' => $questionId,
                'customer_id' => auth()->user()->id,
                'value' => $answer,
            ]);
        }

        return redirect()->route('admin.questionnaire.index');
    }

    public function destroy(Questionnaire $questionnaire)
    {
        abort_if(Gate::denies('questionnaire_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $questionnaire->delete();

        return back();
    }

    public function getContinents()
    {
        return Continent::orderBy('name')->get();
    }

    public function getCountries($continentId)
    {
        return Country::where('continent_id', $continentId)->orderBy('name')->get();
    }

    public function getStates($countryId)
    {
        return State::where('country_id', $countryId)->orderBy('name')->get();
    }
}
