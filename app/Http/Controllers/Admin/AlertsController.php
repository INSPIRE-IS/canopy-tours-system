<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyUserAlertRequest;
use App\Http\Requests\StoreAlertRequest;
use App\Http\Requests\UpdateAlertRequest;
use App\Models\User;
use App\Models\Alert;
use App\Models\QuestionAnswer;
use Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class AlertsController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('user_alerts_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //abort_if(Gate::denies('team_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'alert_text'); // Default to 'name' if no sort is provided
        $sortDirection = $request->get('direction', 'asc');

        $userAlerts = Alert::with(['users'])
            ->where('alert_text', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);
        $users = User::select('name', 'id')->get();

        return Inertia::render('Admin/UserAlerts/Index', [
            'userAlerts' => $userAlerts,
            'users' => $users ?? [],
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function store(StoreAlertRequest $request)
    {
        $userAlert = Alert::create($request->all());
        $userAlert->users()->sync($request->input('users', []));

        return redirect()->route('admin.user-alerts.index');
    }

    public function update(UpdateAlertRequest $request, Alert $userAlert)
    {
        dd('not implemented');
        $userAlert->update($request->all());
        $userAlert->users()->sync($request->input('users', []));

        return redirect()->route('admin.user-alerts.index');
    }

    public function show(Alert $userAlert)
    {
        dd('not implemented');
        abort_if(Gate::denies('user_alert_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //TODO have a page to preview the alert
        // $userAlert->load('users');
        // return view('admin.userAlerts.show', compact('userAlert'));
    }

    public function destroy(Alert $userAlert)
    {
        abort_if(Gate::denies('user_alert_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $userAlert->delete();

        return back();
    }

    public function read(Request $request)
    {
        $alerts = \Auth::user()->alerts()->where('read', false)->get();
        foreach ($alerts as $alert) {
            $pivot       = $alert->pivot;
            $pivot->read = true;
            $pivot->save();
        }
    }

    public function context(Alert $alert)
    {
        $currentTeamId = session('team_id') ?? auth()->user()?->team_id;
        abort_if($currentTeamId && $alert->team_id !== $currentTeamId, Response::HTTP_FORBIDDEN, '403 Forbidden');

        $alert->load([
            'booking:id,reference,lead_name,due_date',
            'booking_item:id,booking_id,service_id,season_id',
            'booking_item.service:id,name',
            'booking_item.season:id,name',
            'booking_item.booking_inventory:id,booking_item_id,date,inventory_time_slot_id',
            'booking_item.booking_inventory.inventory_time_slot:id,start_time',
            'customer:id,full_name,email_address,contact_number',
            'customer_manifest:id,booking_item_id,customer_id,is_indemnity,attendance_status,notes,type',
            'users:id,name',
        ]);

        $answers = collect();
        if ($alert->booking_item_id && $alert->customer_id) {
            $answers = QuestionAnswer::with('question:id,name,slug,type,options')
                ->where('booking_item_id', $alert->booking_item_id)
                ->where('customer_id', $alert->customer_id)
                ->orderBy('id')
                ->get()
                ->map(function ($answer) {
                    return [
                        'id' => $answer->id,
                        'answer' => $answer->answer,
                        'resolved_answer' => $this->resolveAnswer(
                            $answer->answer,
                            $answer->question?->slug,
                            $answer->question?->options
                        ),
                        'question' => [
                            'id' => $answer->question?->id,
                            'name' => $answer->question?->name,
                            'slug' => $answer->question?->slug,
                            'type' => $answer->question?->type,
                            'options' => $answer->question?->options,
                        ],
                    ];
                });
        }

        return response()->json([
            'alert' => [
                'id' => $alert->id,
                'text' => $alert->alert_text,
                'link' => $alert->alert_link,
                'created_at' => $alert->created_at?->format('Y-m-d H:i:s'),
                'assigned_to' => $alert->users->pluck('name')->values(),
                'can_show_context' => (bool) ($alert->booking_id && $alert->booking_item_id && $alert->customer_id),
            ],
            'booking' => $alert->booking ? [
                'id' => $alert->booking->id,
                'reference' => $alert->booking->reference,
                'lead_name' => $alert->booking->lead_name,
                'view_url' => route('admin.bookings.add', $alert->booking->id, false),
            ] : null,
            'booking_item' => $alert->booking_item ? [
                'id' => $alert->booking_item->id,
                'service' => $alert->booking_item->service?->name,
                'season' => $alert->booking_item->season?->name,
                'date' => $alert->booking_item->booking_inventory?->date,
                'time' => $alert->booking_item->booking_inventory?->inventory_time_slot?->start_time,
            ] : null,
            'customer' => $alert->customer ? [
                'id' => $alert->customer->id,
                'full_name' => $alert->customer->full_name,
                'email_address' => $alert->customer->email_address,
                'contact_number' => $alert->customer->contact_number,
            ] : null,
            'manifest' => $alert->customer_manifest ? [
                'id' => $alert->customer_manifest->id,
                'is_indemnity' => (bool) $alert->customer_manifest->is_indemnity,
                'attendance_status' => $alert->customer_manifest->attendance_status,
                'notes' => $alert->customer_manifest->notes,
                'type' => $alert->customer_manifest->type,
            ] : null,
            'answers' => $answers,
        ]);
    }

    public function resolve(Request $request, Alert $alert)
    {
        $user = auth()->user();
        abort_if(!$user, Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'add_note' => ['sometimes', 'boolean'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $pivot = $user->alerts()->where('alerts.id', $alert->id)->first()?->pivot;
        abort_if(!$pivot, Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (($validated['add_note'] ?? false) && $alert->customer_manifest_id && trim((string) ($validated['note'] ?? '')) !== '') {
            $manifest = $alert->customer_manifest;
            if ($manifest) {
                $existingNotes = trim((string) $manifest->notes);
                $note = trim((string) $validated['note']);
                $stamp = now()->format('Y-m-d H:i');
                $userName = $user->name ?? 'Admin';
                $line = "[{$stamp}] {$userName}: {$note}";

                $manifest->update([
                    'notes' => $existingNotes !== '' ? "{$existingNotes}\n{$line}" : $line,
                ]);
            }
        }

        $pivot->read = true;
        $pivot->complete = true;
        $pivot->save();

        return response()->json(['success' => true]);
    }

    private function resolveAnswer($value, ?string $slug, $options)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($slug, ['medical_id', 'is_fit', 'is_agree', 'pregnant', 'marketing_opt_in'], true)) {
            return match ((string) $value) {
                '1' => 'Yes',
                '2' => 'No',
                default => $value,
            };
        }

        if ($slug === 'birthday_month') {
            $months = [
                1 => 'January',
                2 => 'February',
                3 => 'March',
                4 => 'April',
                5 => 'May',
                6 => 'June',
                7 => 'July',
                8 => 'August',
                9 => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December',
            ];

            return $months[(int) $value] ?? $value;
        }

        $options = is_array($options) ? $options : [];
        foreach ($options as $option) {
            $optionId = $option['id'] ?? null;
            if ($optionId !== null && (string) $optionId === (string) $value) {
                return $option['name'] ?? $value;
            }
        }

        return $value;
    }
}
