<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyTypeFileRequest;
use App\Http\Requests\StoreTypeFileRequest;
use App\Http\Requests\UpdateTypeFileRequest;
use App\Models\TypeFile;
use Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class TypeFileController extends Controller
{
    public function index(Request $request)
    {
        //abort_if(Gate::denies('type_file_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        /** @disregard P1013 */
        abort_if(!auth()->user()->can('type_file_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'name'); // Default to 'name' if no sort is provided
        $sortDirection = $request->get('direction', 'asc');
        $typeFiles = TypeFile::where('name', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(25);
        /** @disregard P1013 */
        return Inertia::render('TypeFile/Index', [
            'typeFiles' => $typeFiles,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'can' => [
                'type_file_show' => false, //auth()->user()->can('type_file_show'),
                'type_file_create' => auth()->user()->can('type_file_create'),
                'type_file_edit' => auth()->user()->can('type_file_edit'),
                'type_file_delete' => auth()->user()->can('type_file_delete'),

            ],
        ]);
    }

    public function store(StoreTypeFileRequest $request)
    {
        $typeFile = TypeFile::create($request->all());

        return redirect()->route('type-files.index');
    }

    public function update(UpdateTypeFileRequest $request, TypeFile $typeFile)
    {
        $typeFile->update($request->all());

        return redirect()->route('type-files.index');
    }

    public function show(TypeFile $typeFile)
    {
        //abort_if(Gate::denies('type_file_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('typeFiles.show', compact('typeFile'));
    }

    public function destroy(TypeFile $typeFile)
    {
        //abort_if(Gate::denies('type_file_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $typeFile->delete();

        return back();
    }
}
