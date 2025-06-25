<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyFileRequest;
use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\UpdateFileRequest;
use App\Models\File;
use App\Models\Team;
use App\Models\TypeFile;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class FileController extends Controller
{
    public function index(Request $request)
    {
        /** @disregard P1013 */
        abort_if(!auth()->user()->can('file_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'file_name'); // Default to 'name' if no sort is provided
        $sortDirection = $request->get('direction', 'asc');

        $user = auth()->user();
        //dd($user->hasAnyRole(['admin', 'super']));
        $teamId = Session::get('team_id'); //setPermissionsTeamId(auth('api')->user()->getTeamIdFromToken());

        $Files = File::with(['site', 'type_file'])
            ->where('file_name', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->where('site_id', $teamId)
            ->paginate(25);

        $sites = Team::select('name as label', 'id as value')->get();

        $type_files = TypeFile::select('name as label', 'id as value')->get();

        /** @disregard P1013 */
        return Inertia::render('File/Index', [
            'Files' => $Files,
            'sites' => $sites,
            'typeFiles' => $type_files,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'can' => [
                'file_show' => true, // auth()->user()->can('file_show'),
                'file_create' => true, // auth()->user()->can('file_create'),
                'file_edit' => false, // auth()->user()->can('file_edit'),
                'file_delete' =>  false, // auth()->user()->can('file_delete'),

            ],
        ]);
    }

    public function store(StoreFileRequest $request)
    {

        $image_file = $request->file('image');
        $fileName = time() . '_' . $image_file->getClientOriginalName();
        $type_file = TypeFile::find($request->type_file_id);
        $folder_name = $type_file ? $type_file->name : 'general';
        //$directoryPath = 'uploads/' . $folder_name . '/' . $request->table_row_id;
        $directoryPath = 'storage/';


        $filePath = $image_file->storeAs($directoryPath, $fileName, 'public');
        $file = File::create([
            'site_id'   => $request->site_id,
            'table_name' => $request->table_name,
            'category_items',
            'file_name' => $fileName,
            'file_type' => $request->file_type, // certificate
            'type_file_id' => $request->type_file_id, // certificate
            'table_row_id' => $request->table_row_id,
            'path' => $filePath,
        ]);

        //$file = File::create($request->all());

        return redirect()->route('files.index');
    }

    public function update(UpdateFileRequest $request, File $file)
    {
        $file->update($request->all());

        return redirect()->route('files.index');
    }

    public function show(File $file)
    {
        //abort_if(Gate::denies('file_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $file->load('site', 'type_file');

        return view('files.show', compact('file'));
    }

    public function destroy(File $file)
    {
        //abort_if(Gate::denies('file_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $file->delete();

        return back();
    }
}
