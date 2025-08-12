<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpQuestion;
use App\Models\TypeHelpQuestion;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class HelpQuestionsController extends Controller
{
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
        $teamId = Session::get('team_id');
        $closeouts = HelpQuestion::with(['typeHelpQuestion', 'answer'])
            ->where('question', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);

        $typeHelpQuestions = TypeHelpQuestion::select('id', 'name')->get();

        return Inertia::render('Admin/HelpQuestions/Index', [
            'helpQuestions' => $closeouts,
            'typeHelpQuestions' => $typeHelpQuestions,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(HelpQuestion $helpQuestion)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HelpQuestion $helpQuestion)
    {
        //
    }
}
