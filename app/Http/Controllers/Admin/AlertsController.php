<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyUserAlertRequest;
use App\Http\Requests\StoreAlertRequest;
use App\Http\Requests\UpdateAlertRequest;
use App\Models\User;
use App\Models\Alert;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Questionnaire;
use App\Models\QuestionAnswer;
use App\Models\QuestionSite;
use App\Models\State;
use App\Models\TypeGroup;
use App\Models\TypeReferral;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            $optionMaps = $this->answerOptionMaps($alert);

            $answers = QuestionAnswer::with('question:id,name,slug,type,options')
                ->where('booking_item_id', $alert->booking_item_id)
                ->where('customer_id', $alert->customer_id)
                ->orderBy('id')
                ->get()
                ->map(function ($answer) use ($optionMaps) {
                    $slug = $answer->question?->slug;
                    $options = $slug ? ($optionMaps[$slug] ?? $answer->question?->options) : $answer->question?->options;

                    return [
                        'id' => $answer->id,
                        'answer' => $answer->answer,
                        'resolved_answer' => $this->resolveAnswer(
                            $answer->answer,
                            $slug,
                            $options
                        ),
                        'question' => [
                            'id' => $answer->question?->id,
                            'name' => $answer->question?->name,
                            'slug' => $answer->question?->slug,
                            'type' => $answer->question?->type,
                            'options' => $options,
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

        $assignedTeamUserIds = User::where('team_id', $alert->team_id)->pluck('id');

        DB::table('alert_user')
            ->where('alert_id', $alert->id)
            ->whereIn('user_id', $assignedTeamUserIds)
            ->update([
                'read' => true,
                'complete' => true,
                'updated_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    private function answerOptionMaps(Alert $alert): array
    {
        $bookingItem = $alert->booking_item;
        $monthNames = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
        ];

        $optionMaps = [
            'type_group_id' => TypeGroup::select('id', 'name')
                ->get()
                ->map(fn($row) => ['id' => $row->id, 'name' => $row->name])
                ->values()
                ->all(),
            'referral_id' => TypeReferral::select('id', 'name')
                ->get()
                ->map(fn($row) => ['id' => $row->id, 'name' => $row->name])
                ->values()
                ->all(),
            'continent_id' => Continent::select('id', 'name')
                ->get()
                ->map(fn($row) => ['id' => $row->id, 'name' => $row->name])
                ->values()
                ->all(),
            'country_id' => Country::select('id', 'name')
                ->get()
                ->map(fn($row) => ['id' => $row->id, 'name' => $row->name])
                ->values()
                ->all(),
            'province_id' => State::select('id', 'name')
                ->get()
                ->map(fn($row) => ['id' => $row->id, 'name' => $row->name])
                ->values()
                ->all(),
            'age_id' => Questionnaire::AGES,
            'gender' => Questionnaire::GENDERS,
            'medical_id' => [
                ['id' => 1, 'name' => 'Yes'],
                ['id' => 2, 'name' => 'No'],
            ],
            'is_fit' => [
                ['id' => 1, 'name' => 'Yes'],
                ['id' => 2, 'name' => 'No'],
            ],
            'is_agree' => [
                ['id' => 1, 'name' => 'Yes'],
                ['id' => 2, 'name' => 'No'],
            ],
            'marketing_opt_in' => [
                ['id' => 1, 'name' => 'Yes'],
                ['id' => 2, 'name' => 'No'],
            ],
            'pregnant' => [
                ['id' => 1, 'name' => 'Yes'],
                ['id' => 2, 'name' => 'No'],
            ],
            'birthday_month' => collect($monthNames)
                ->map(fn($name, $index) => ['id' => $index + 1, 'name' => $name])
                ->values()
                ->all(),
            'birthday_day' => collect(range(1, 31))
                ->map(fn($day) => ['id' => $day, 'name' => (string) $day])
                ->values()
                ->all(),
            'parent_id' => $bookingItem
                ? $bookingItem->customers()
                    ->select('customers.id', 'customers.full_name')
                    ->get()
                    ->map(fn($customer) => ['id' => $customer->id, 'name' => $customer->full_name])
                    ->values()
                    ->all()
                : [],
        ];

        if ($bookingItem?->service_id && $bookingItem?->season_id) {
            $customOptionMaps = QuestionSite::with('question:id,slug,is_custom')
                ->where('service_id', $bookingItem->service_id)
                ->where('season_id', $bookingItem->season_id)
                ->get()
                ->filter(fn($site) => $site->question?->is_custom)
                ->mapWithKeys(function ($site) {
                    $options = $site->custom_options ?? [];
                    return [$site->question->slug => is_array($options) ? $options : []];
                })
                ->all();

            if (!empty($customOptionMaps)) {
                $optionMaps = array_merge($optionMaps, $customOptionMaps);
            }
        }

        return $optionMaps;
    }

    private function resolveAnswer($value, ?string $slug, $options)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $options = is_array($options) ? $options : [];
        $decodedValue = $value;
        if (is_string($value)) {
            $trimmed = trim($value);
            if (
                $trimmed !== '' &&
                ((str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) ||
                    (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')))
            ) {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $decodedValue = $decoded;
                }
            }
        }

        $mapOption = function ($raw) use ($options) {
            if (is_bool($raw)) {
                return $raw ? 'Yes' : 'No';
            }

            if (empty($options)) {
                return $raw;
            }

            foreach ($options as $option) {
                $optionId = $option['id'] ?? $option['value'] ?? $option['slug'] ?? null;
                if ($optionId !== null && (string) $optionId === (string) $raw) {
                    return $option['name'] ?? $option['label'] ?? $option['title'] ?? $raw;
                }
            }

            return $raw;
        };

        if (is_array($decodedValue)) {
            return collect($decodedValue)
                ->map(fn($val) => $mapOption($val))
                ->implode(', ');
        }

        if ($decodedValue === true || $decodedValue === false) {
            return $decodedValue ? 'Yes' : 'No';
        }

        foreach ($options as $option) {
            $optionId = $option['id'] ?? $option['value'] ?? $option['slug'] ?? null;
            if ($optionId !== null && (string) $optionId === (string) $decodedValue) {
                return $option['name'] ?? $option['label'] ?? $option['title'] ?? $decodedValue;
            }
        }

        return $decodedValue;
    }
}
