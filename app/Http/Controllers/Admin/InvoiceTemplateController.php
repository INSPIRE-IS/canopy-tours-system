<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceTemplate;
use App\Models\TypeInvoice;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Gate;
use Illuminate\Support\Facades\Auth;
use Mockery\Matcher\Type;
use Symfony\Component\HttpFoundation\Response;


class InvoiceTemplateController extends Controller
{
    public function index(Request $request)
    {

        abort_if(Gate::denies('user_alerts_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'name'); // Default to 'name' if no sort is provided
        $sortDirection = $request->get('direction', 'asc');

        $templates = InvoiceTemplate:: //with(['users'])
            where('name', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);
        // $users = User::select('name', 'id')->get();

        // return Inertia::render('Admin/Alerts/Index', [
        //     'Alerts' => $Alerts,
        //     'users' => $users ?? [],
        //     'searchTerm' => $searchTerm,
        //     'sortColumn' => $sortColumn,
        //     'sortDirection' => $sortDirection,
        // ]);
        $typeInvoices = TypeInvoice::all();
        $typeInvoices = $typeInvoices->map(function ($typeInvoice) {
            return [
                'id' => $typeInvoice->id,
                'name' => $typeInvoice->name,
                'type' => $typeInvoice->slug,
            ];
        });


        return inertia('Admin/InvoiceTemplates/Index', compact('templates', 'typeInvoices'));
    }

    public function create()
    {
        return inertia('Admin/InvoiceTemplates/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:invoice,quote,proforma,receipt',
            'content' => 'required|string',
            'is_default' => 'boolean'
        ]);

        $auth = Auth::user();
        if ($validated['is_default']) {
            $invoiceTemplates = InvoiceTemplate::where('team_id', $auth->team_id)->where('type', $validated['type'])->get();
            if ($invoiceTemplates->count() > 0) {
                $invoiceTemplates->each(function ($template) {
                    $template->is_default = false;
                    $template->save();
                });
            }
        }


        InvoiceTemplate::create($request->only(['name', 'type', 'content', 'is_default', 'team_id' => $auth->team_id]));

        return redirect()->route('admin.invoice-templates.index')
            ->with('success', 'Template created');
    }

    public function edit(InvoiceTemplate $invoiceTemplate)
    {
        return inertia('Admin/InvoiceTemplates/Edit', compact('invoiceTemplate'));
    }

    public function update(Request $request, InvoiceTemplate $invoiceTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:invoice,quote,proforma,receipt',
            'content' => 'required|string',
            'is_default' => 'boolean'
        ]);

        $auth = Auth::user();
        if ($validated['is_default']) {
            $invoiceTemplates = InvoiceTemplate::where('team_id', $auth->team_id)->where('type', $validated['type'])->get();
            if ($invoiceTemplates->count() > 0) {
                $invoiceTemplates->each(function ($template) {
                    $template->is_default = false;
                    $template->save();
                });
            }
        }
        $invoiceTemplate->update($request->only(['name', 'type', 'content', 'is_default']));

        return redirect()->route('admin.invoice-templates.index')
            ->with('success', 'Template updated');
    }

    public function preview(InvoiceTemplate $invoiceTemplate)
    {
        $html = $invoiceTemplate->content; // inject placeholders later
        $pdf = Pdf::loadHTML($html);
        return $pdf->stream('preview.pdf'); // shows inline in browser
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InvoiceTemplate $invoiceTemplate)
    {
        $invoiceTemplate->delete();

        return redirect()->route('admin.invoice-templates.index')
            ->with('success', 'Template deleted');
    }
}
