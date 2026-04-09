<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpAnswer;
use App\Models\HelpQuestion;
use App\Models\TypeHelpQuestion;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class HelpQuestionsController extends Controller
{
    protected function applyHelpSearch($query, string $searchTerm): void
    {
        $searchTerm = trim($searchTerm);
        if ($searchTerm === '') {
            return;
        }

        $terms = preg_split('/\s+/', $searchTerm, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($terms)) {
            return;
        }

        $query->where(function ($outer) use ($terms) {
            foreach ($terms as $term) {
                $like = '%' . $term . '%';
                $outer->orWhere(function ($inner) use ($like) {
                    $inner->where('help_questions.question', 'like', $like)
                        ->orWhere('ha.answer', 'like', $like)
                        ->orWhere('thq.name', 'like', $like);
                });
            }
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        abort_if(Gate::denies('help_questions_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'question');
        $sortDirection = $request->get('direction', 'asc');
        /** @var \App\Models\User $user */
        $user = $request->user();
        $teamId = $user?->team_id;
        $isAdmin = $user?->is_admin ?? false;
        $roleIds = $user?->roles?->pluck('id')->all() ?? [];
        $teamFilter = $request->input('team_id');
        $query = HelpQuestion::with(['typeHelpQuestion', 'answer', 'team'])
            ->leftJoin('type_help_questions as thq', 'help_questions.type_help_question_id', '=', 'thq.id')
            ->leftJoin('help_answers as ha', 'help_questions.id', '=', 'ha.help_question_id')
            ->select('help_questions.*')
            ->distinct()
            ->when(!$isAdmin && $teamId, function ($query) use ($teamId) {
                $query->where(function ($sub) use ($teamId) {
                    $sub->whereNull('team_id')
                        ->orWhere('team_id', $teamId);
                });
            })
            ->when($isAdmin && $teamFilter, function ($query) use ($teamFilter) {
                $query->where(function ($sub) use ($teamFilter) {
                    $sub->whereNull('team_id')
                        ->orWhere('team_id', $teamFilter);
                });
            });
        $this->applyHelpSearch($query, $searchTerm);

        if (!$isAdmin) {
            $query->where(function ($sub) use ($roleIds) {
                $sub->whereNull('thq.role_ids');
                foreach ($roleIds as $roleId) {
                    $sub->orWhereJsonContains('thq.role_ids', (int) $roleId);
                }
            });
        }

        $typeHelpQuestions = TypeHelpQuestion::select('id', 'name')
            ->when(!$isAdmin, function ($query) use ($roleIds) {
                $query->where(function ($sub) use ($roleIds) {
                    $sub->whereNull('role_ids');
                    foreach ($roleIds as $roleId) {
                        $sub->orWhereJsonContains('role_ids', (int) $roleId);
                    }
                });
            })
            ->orderByRaw('coalesce(order_id, 9999)')
            ->orderBy('name')
            ->get();

        if (!$isAdmin) {
            $helpQuestions = $query->orderByRaw('coalesce(thq.order_id, 9999)')->orderBy('question')->get();
            $activeQuestion = $helpQuestions->first();

            return Inertia::render('Admin/HelpQuestions/Show', [
                'helpQuestions' => $helpQuestions,
                'activeQuestion' => $activeQuestion,
                'typeHelpQuestions' => $typeHelpQuestions,
                'searchTerm' => $searchTerm,
            ]);
        }

        $helpQuestions = $query->orderByRaw('coalesce(thq.order_id, 9999)')->orderBy($sortColumn, $sortDirection)->paginate(10);

        return Inertia::render('Admin/HelpQuestions/Index', [
            'helpQuestions' => $helpQuestions,
            'typeHelpQuestions' => $typeHelpQuestions,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'teamFilter' => $teamFilter,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort_if(Gate::denies('help_questions_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'question' => 'required|string',
            'type_help_question_id' => 'required|exists:type_help_questions,id',
            'answer' => 'nullable|string',
            'team_id' => 'nullable|exists:teams,id',
            'is_internal' => 'boolean',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $isAdmin = $user?->is_admin ?? false;
        $teamId = $isAdmin ? ($data['team_id'] ?? null) : $user?->team_id;

        $helpQuestion = HelpQuestion::create([
            'question' => $data['question'],
            'type_help_question_id' => $data['type_help_question_id'],
            'team_id' => $teamId,
            'is_internal' => (bool) ($data['is_internal'] ?? false),
        ]);

        if (!empty($data['answer'])) {
            HelpAnswer::updateOrCreate(
                ['help_question_id' => $helpQuestion->id],
                ['answer' => $data['answer']]
            );
        }

        return redirect()->route('admin.help-questions.index')
            ->with('success', 'Help question created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, HelpQuestion $helpQuestion)
    {
        abort_if(Gate::denies('help_questions_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        /** @var \App\Models\User $user */
        $user = $request->user();
        $teamId = $user?->team_id;
        $isAdmin = $user?->is_admin ?? false;
        $roleIds = $user?->roles?->pluck('id')->all() ?? [];
        $searchTerm = $request->get('search', '');

        if (!$isAdmin) {
            $isAllowed = is_null($helpQuestion->team_id) || ($teamId && $helpQuestion->team_id === $teamId);
            abort_if(!$isAllowed, Response::HTTP_FORBIDDEN, '403 Forbidden');
        }

        $helpQuestion->load(['typeHelpQuestion', 'answer', 'team']);
        if (!$isAdmin) {
            $allowedRoles = $helpQuestion->typeHelpQuestion?->role_ids ?? null;
            $allowed = empty($allowedRoles) || count(array_intersect($roleIds, $allowedRoles)) > 0;
            abort_if(!$allowed, Response::HTTP_FORBIDDEN, '403 Forbidden');
        }
        $helpQuestions = HelpQuestion::with(['typeHelpQuestion', 'answer', 'team'])
            ->leftJoin('type_help_questions as thq', 'help_questions.type_help_question_id', '=', 'thq.id')
            ->leftJoin('help_answers as ha', 'help_questions.id', '=', 'ha.help_question_id')
            ->select('help_questions.*')
            ->distinct()
            ->when(!$isAdmin && $teamId, function ($query) use ($teamId) {
                $query->where(function ($sub) use ($teamId) {
                    $sub->whereNull('team_id')
                        ->orWhere('team_id', $teamId);
                });
            })
            ->when(!$isAdmin, function ($query) use ($roleIds) {
                $query->where(function ($sub) use ($roleIds) {
                    $sub->whereNull('thq.role_ids');
                    foreach ($roleIds as $roleId) {
                        $sub->orWhereJsonContains('thq.role_ids', (int) $roleId);
                    }
                });
            });
        $this->applyHelpSearch($helpQuestions, $searchTerm);
        $helpQuestions = $helpQuestions
            ->orderByRaw('coalesce(thq.order_id, 9999)')
            ->orderBy('question')
            ->get();
        $typeHelpQuestions = TypeHelpQuestion::select('id', 'name')
            ->when(!$isAdmin, function ($query) use ($roleIds) {
                $query->where(function ($sub) use ($roleIds) {
                    $sub->whereNull('role_ids');
                    foreach ($roleIds as $roleId) {
                        $sub->orWhereJsonContains('role_ids', (int) $roleId);
                    }
                });
            })
            ->orderByRaw('coalesce(order_id, 9999)')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/HelpQuestions/Show', [
            'helpQuestions' => $helpQuestions,
            'activeQuestion' => $helpQuestion,
            'typeHelpQuestions' => $typeHelpQuestions,
            'searchTerm' => $searchTerm,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HelpQuestion $helpQuestion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HelpQuestion $helpQuestion)
    {
        abort_if(Gate::denies('help_questions_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'question' => 'required|string',
            'type_help_question_id' => 'required|exists:type_help_questions,id',
            'answer' => 'nullable|string',
            'team_id' => 'nullable|exists:teams,id',
            'is_internal' => 'boolean',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $isAdmin = $user?->is_admin ?? false;
        $teamId = $isAdmin ? ($data['team_id'] ?? null) : $user?->team_id;

        $helpQuestion->update([
            'question' => $data['question'],
            'type_help_question_id' => $data['type_help_question_id'],
            'team_id' => $teamId,
            'is_internal' => (bool) ($data['is_internal'] ?? false),
        ]);

        HelpAnswer::updateOrCreate(
            ['help_question_id' => $helpQuestion->id],
            ['answer' => $data['answer'] ?? '']
        );

        return redirect()->route('admin.help-questions.index')
            ->with('success', 'Help question updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HelpQuestion $helpQuestion)
    {
        abort_if(Gate::denies('help_questions_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $helpQuestion->delete();
        return redirect()->route('admin.help-questions.index')
            ->with('success', 'Help question removed.');
    }
}
