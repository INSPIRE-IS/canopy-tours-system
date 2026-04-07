<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionSite;
use App\Models\Service;
use App\Models\Season;
use Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class DynamicQuestionnaireController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('questionnaires_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $isAdmin = $this->isAdminUser();

        $services = Service::whereHas('seasons')
            ->with([
                'seasons:id,name,service_id',
                'question_sites:id,service_id,season_id,question_id,enabled,sort_order,custom_options',
            ])
            ->orderBy('name')
            ->get();

        $questions = Question::select('id', 'name', 'slug', 'type', 'is_visible', 'is_disabled', 'is_enabled', 'is_custom')
            ->orderBy('id')
            ->get();

        return Inertia::render('Admin/Questionnaires/DynamicIndex', [
            'services' => $services,
            'questions' => $questions,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function update(Request $request, Service $service)
    {
        // dd($request->all());
        abort_if(Gate::denies('questionnaires_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'season_id' => ['required', 'integer', 'exists:seasons,id'],
            'questions' => ['required', 'array'],
            'questions.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'questions.*.enabled' => ['required', 'boolean'],
            'questions.*.sort_order' => ['nullable', 'integer'],
            'questions.*.custom_options' => ['nullable', 'array'],
        ]);

        $seasonId = $validated['season_id'];
        $season = Season::where('id', $seasonId)
            ->where('service_id', $service->id)
            ->first();
        if (!$season) {
            return back()->withErrors([
                'season_id' => 'Selected season does not belong to this service.',
            ]);
        }

        $questionFlags = Question::whereIn('id', collect($validated['questions'])->pluck('question_id'))
            ->get(['id', 'is_disabled', 'is_custom'])
            ->keyBy('id');

        foreach ($validated['questions'] as $question) {
            $flags = $questionFlags[$question['question_id']] ?? null;
            $isDisabled = (bool) ($flags?->is_disabled ?? false);
            $isCustom = (bool) ($flags?->is_custom ?? false);
            $enabled = $isDisabled ? true : (bool) $question['enabled'];
            $customOptions = $question['custom_options'] ?? null;

            if ($isCustom && $enabled && empty($customOptions)) {
                return back()->withErrors([
                    'questions' => 'Custom questions require options before enabling.',
                ]);
            }

            QuestionSite::updateOrCreate(
                [
                    'service_id' => $service->id,
                    'question_id' => $question['question_id'],
                    'season_id' => $seasonId,
                ],
                [
                    'enabled' => $enabled,
                    'sort_order' => (int) ($question['sort_order'] ?? 0),
                    'custom_options' => $customOptions,
                ]
            );
        }

        return redirect()
            ->route('admin.dynamic-questionnaires.index')
            ->with('success', 'Questionnaire settings updated.');
    }

    public function storeQuestion(Request $request)
    {
        abort_if(Gate::denies('questionnaires_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if (!$this->isAdminUser()) {
            abort(Response::HTTP_FORBIDDEN, '403 Forbidden');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:questions,slug'],
            'type' => ['required', 'in:indemnity,feedback'],
            'description' => ['nullable', 'string'],
            'options' => ['nullable'],
            'conditional_logic' => ['nullable', 'string'],
            'is_visible' => ['nullable', 'boolean'],
            'is_disabled' => ['nullable', 'boolean'],
            'is_enabled' => ['nullable', 'boolean'],
            'is_custom' => ['nullable', 'boolean'],
        ]);

        $options = null;
        if (array_key_exists('options', $validated)) {
            if (is_array($validated['options'])) {
                $options = $validated['options'];
            } elseif (is_string($validated['options']) && trim($validated['options']) !== '') {
                $raw = trim($validated['options']);
                if (
                    (str_starts_with($raw, '[') && str_ends_with($raw, ']')) ||
                    (str_starts_with($raw, '{') && str_ends_with($raw, '}'))
                ) {
                    $decoded = json_decode($raw, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $options = $decoded;
                    }
                }

                if ($options === null) {
                    $options = collect(explode(',', $raw))
                        ->map(fn($value) => trim($value))
                        ->filter()
                        ->values()
                        ->all();
                }
            }
        }

        $conditionalLogic = null;
        if (!empty($validated['conditional_logic'])) {
            $raw = trim($validated['conditional_logic']);
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $conditionalLogic = $decoded;
            }
        }

        $isDisabled = array_key_exists('is_disabled', $validated) ? (bool) $validated['is_disabled'] : false;
        $isVisible = array_key_exists('is_visible', $validated) ? (bool) $validated['is_visible'] : false;
        $isEnabled = array_key_exists('is_enabled', $validated) ? (bool) $validated['is_enabled'] : false;
        if ($isDisabled) {
            $isVisible = true;
            $isEnabled = true;
        }

        $question = Question::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'options' => $options,
            'conditional_logic' => $conditionalLogic,
            'is_visible' => $isVisible,
            'is_disabled' => $isDisabled,
            'is_enabled' => $isEnabled,
            'is_custom' => (bool) ($validated['is_custom'] ?? false),
        ]);

        $seasons = Season::select('id', 'service_id')->get();
        foreach ($seasons as $season) {
            $maxSort = QuestionSite::where('service_id', $season->service_id)
                ->where('season_id', $season->id)
                ->max('sort_order');

            QuestionSite::firstOrCreate(
                [
                    'service_id' => $season->service_id,
                    'season_id' => $season->id,
                    'question_id' => $question->id,
                ],
                [
                    'enabled' => $question->is_disabled ? true : (bool) $question->is_enabled,
                    'sort_order' => ($maxSort ?? 0) + 1,
                ]
            );
        }

        return redirect()
            ->route('admin.dynamic-questionnaires.index')
            ->with('success', 'Question created successfully.');
    }

    public function updateQuestions(Request $request)
    {
        abort_if(Gate::denies('questionnaires_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if (!$this->isAdminUser()) {
            abort(Response::HTTP_FORBIDDEN, '403 Forbidden');
        }

        $validated = $request->validate([
            'questions' => ['required', 'array'],
            'questions.*.id' => ['required', 'integer', 'exists:questions,id'],
            'questions.*.is_visible' => ['required', 'boolean'],
            'questions.*.is_disabled' => ['required', 'boolean'],
            'questions.*.is_enabled' => ['required', 'boolean'],
            'questions.*.is_custom' => ['required', 'boolean'],
        ]);

        foreach ($validated['questions'] as $questionData) {
            $isDisabled = (bool) $questionData['is_disabled'];
            $isVisible = (bool) $questionData['is_visible'];
            $isEnabled = (bool) $questionData['is_enabled'];

            if ($isDisabled) {
                $isVisible = true;
                $isEnabled = true;
            }

            if (!$isVisible) {
                $isEnabled = false;
            }
            Question::whereKey($questionData['id'])
                ->update([
                    'is_visible' => $isVisible,
                    'is_disabled' => $isDisabled,
                    'is_enabled' => $isEnabled,
                    'is_custom' => (bool) $questionData['is_custom'],
                ]);

            if ($isDisabled) {
                QuestionSite::where('question_id', $questionData['id'])
                    ->update(['enabled' => true]);
            }
        }

        return redirect()
            ->route('admin.dynamic-questionnaires.index')
            ->with('success', 'Question visibility updated.');
    }

    private function isAdminUser(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $roleIds = $user->roles?->pluck('id') ?? collect();
        return $roleIds->intersect([1, 2])->isNotEmpty();
    }
}
