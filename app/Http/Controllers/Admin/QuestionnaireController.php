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
            ->where('name', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);

        return Inertia::render('Admin/Questionnaires/Index', [
            'questionnaires' => $questionnaires,
            'inputTypes' => TypeInput::all(), //  ['text', 'textarea', 'number', 'select', 'radio', 'checkbox', 'date'],
            'dataTypes' => TypeData::all(), // ['string', 'integer', 'boolean', 'date'],
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'fields' => ['required', 'array'],
            'fields.*.label' => ['required', 'string'],
            'fields.*.input_type' => ['required', 'string'],
            'fields.*.data_type' => ['required', 'string'],
            'fields.*.required' => ['boolean'],
            'fields.*.options' => ['nullable', 'string'],
        ]);

        // create questionnaire
        $questionnaire = Questionnaire::create($request->only('name', 'description', 'team_id'));

        // store fields
        foreach ($validated['fields'] as $field) {
            $questionnaire->fields()->create([
                'question' => $field['label'],
                'slug' => $field['label'],
                'type_input_id' => $field['input_type'],
                'type_data_id' => $field['data_type'],
                'required' => $field['required'] ?? false,
                'options' => $field['options'] ?? null,
            ]);
        }

        // 'required',
        // 'hidden',
        // 'sort_order',

        return redirect()->route('admin.questionnaire.index');
    }

    public function update(Request $request, Questionnaire $questionnaire)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $questionnaire->update($request->only('name', 'description'));

        return redirect()->route('admin.questionnaires.index');
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
