<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyTeamRequest;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\File;
use App\Models\AuditLog;
use App\Models\Gateway;
use App\Models\GatewayConfig;
use App\Models\LookupItem;
use App\Models\Team;
use App\Models\User;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;


class TeamController extends Controller
{
    private function canPublishTeams(?User $user): bool
    {
        return (bool) ($user?->can('teams_publish') ?? false);
    }

    public function index(Request $request)
    {
        //abort_if(Gate::denies('team_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(Gate::denies('teams_access'), 403);
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        /** @var \App\Models\User $user */
        $teamId = Session::get('team_id');
        $teams = Team::with('owner', 'file_image', 'file_logo') // with(['owner'])
            ->where('name', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);

        $user = $request->user();
        $canEditGateways = $user && Gate::allows('gateways_edit');
        $isAdmin = (bool) ($user?->is_admin ?? false);

        if ($canEditGateways) {
            $payfastGateway = Gateway::where('name', 'PayFast')->first();
            $teamIds = $teams->getCollection()->pluck('id')->all();
            $envConfig = config('payfast');

            $defaultGatewayConfig = null;
            $teamGatewayConfigs = collect();

            if ($payfastGateway) {
                $defaultGatewayConfig = GatewayConfig::where('gateway_id', $payfastGateway->id)
                    ->whereNull('team_id')
                    ->first();

                if (!empty($teamIds)) {
                    $teamGatewayConfigs = GatewayConfig::where('gateway_id', $payfastGateway->id)
                        ->whereIn('team_id', $teamIds)
                        ->get()
                        ->keyBy('team_id');
                }
            }

            $teams->getCollection()->transform(function (Team $team) use ($teamGatewayConfigs, $defaultGatewayConfig, $envConfig, $isAdmin) {
                $teamConfigRow = $teamGatewayConfigs->get($team->id);
                $resolvedRow = $teamConfigRow ?: $defaultGatewayConfig;

                $resolvedConfig = $resolvedRow?->config ?? [];
                if (empty($resolvedConfig) && is_array($envConfig)) {
                    $resolvedConfig = [
                        'merchant_id' => $envConfig['merchant_id'] ?? '',
                        'merchant_key' => $envConfig['merchant_key'] ?? '',
                        'passphrase' => $envConfig['passphrase'] ?? '',
                    ];
                }

                $isEnabled = $teamConfigRow
                    ? (bool) $teamConfigRow->is_enabled
                    : (bool) ($defaultGatewayConfig?->is_enabled ?? (!empty($envConfig['merchant_id']) && !empty($envConfig['merchant_key'])));

                $isSandbox = $teamConfigRow
                    ? (bool) $teamConfigRow->is_sandbox
                    : (bool) ($defaultGatewayConfig?->is_sandbox ?? (bool) ($envConfig['is_sandbox'] ?? true));

                $team->setAttribute('payfast', [
                    'scope' => $teamConfigRow ? 'team' : ($defaultGatewayConfig ? 'default' : 'env'),
                    'has_team_override' => (bool) $teamConfigRow,
                    'is_enabled' => $isEnabled,
                    'is_sandbox' => $isSandbox,
                    'merchant_id' => $resolvedConfig['merchant_id'] ?? '',
                    // Secrets: only expose to Admin users.
                    'merchant_key' => $isAdmin ? ($resolvedConfig['merchant_key'] ?? '') : null,
                    'passphrase' => $isAdmin ? ($resolvedConfig['passphrase'] ?? '') : null,
                    'merchant_key_configured' => !empty($resolvedConfig['merchant_key'] ?? ''),
                    'passphrase_set' => !empty($resolvedConfig['passphrase'] ?? ''),
                ]);

                return $team;
            });
        }

        return Inertia::render('Admin/Teams/Index', [
            'teams' => $teams,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }


    public function store(StoreTeamRequest $request)
    {
        //dd('here');
        //dd($request->all());
        // TODO: add logic to save image and logo
        $data = $request->all();

        $data['owner_id'] = auth()->user()->id;

        if ($request->has('is_published') && !$this->canPublishTeams($request->user())) {
            abort(Response::HTTP_FORBIDDEN, 'Not allowed to publish/unpublish sites.');
        }

        $team = null;
        DB::transaction(function () use ($data, &$team) {
            $team = Team::create($data);
            LookupItem::ensureSettingsForTeam($team->id);
        });

        if ($request->hasFile('logo') && $team) {
            $uploaded = $request->file('logo');
            $path = $uploaded->store("teams/{$team->id}", 'public');

            File::create([
                'team_id' => auth()->user()->team_id,
                'table_name' => 'teams',
                'table_row_id' => $team->id,
                'file_name' => $uploaded->getClientOriginalName(),
                'file_type' => $uploaded->getMimeType(),
                'type_file_id' => 1,
                'path' => $path,
            ]);
        }

        if ($request->hasFile('image') && $team) {
            $uploaded = $request->file('image');
            $path = $uploaded->store("teams/{$team->id}", 'public');

            File::create([
                'team_id' => auth()->user()->team_id,
                'table_name' => 'teams',
                'table_row_id' => $team->id,
                'file_name' => $uploaded->getClientOriginalName(),
                'file_type' => $uploaded->getMimeType(),
                'type_file_id' => 2,
                'path' => $path,
            ]);
        }

        return response()->json([
            'success' => true,
            'error' => 'none',
        ], 200);
    }

    public function update(UpdateTeamRequest $request, Team $team)
    {
        // dd($team->wasChanged());
        // $team->forceFill([
        //     'name' => 'FORCE TEST ' . now(),
        // ])->save();

        // dd($team->wasChanged());
        // dd(
        //     $request->all(),
        //     $request->validated(),
        //     $team->getDirty()
        // );
        $data = $request->all();
        $data['owner_id'] = auth()->id();
        //dd($data);

        if ($request->has('is_published') && !$this->canPublishTeams($request->user())) {
            abort(Response::HTTP_FORBIDDEN, 'Not allowed to publish/unpublish sites.');
        }

        DB::transaction(function () use ($request, $team, $data) {
            $team->update($data);

            if (Gate::allows('gateways_edit')) {
                $this->syncPayfastGatewayConfig($request, $team);
            }
        });

        if ($request->hasFile('logo')) {
            $uploaded = $request->file('logo');
            $path = $uploaded->store("teams/{$team->id}", 'public');

            optional(
                File::where('table_name', 'teams')
                    ->where('table_row_id', $team->id)
                    ->where('type_file_id', 1)
                    ->first()
            )?->delete();

            File::create([
                'team_id' => auth()->user()->team_id,
                'table_name' => 'teams',
                'table_row_id' => $team->id,
                'file_name' => $uploaded->getClientOriginalName(),
                'file_type' => $uploaded->getMimeType(),
                'type_file_id' => 1,
                'path' => $path,
            ]);
        }

        if ($request->hasFile('image')) {
            $uploaded = $request->file('image');
            $path = $uploaded->store("teams/{$team->id}", 'public');

            optional(
                File::where('table_name', 'teams')
                    ->where('table_row_id', $team->id)
                    ->where('type_file_id', 2)
                    ->first()
            )?->delete();

            File::create([
                'team_id' => auth()->user()->team_id,
                'table_name' => 'teams',
                'table_row_id' => $team->id,
                'file_name' => $uploaded->getClientOriginalName(),
                'file_type' => $uploaded->getMimeType(),
                'type_file_id' => 2,
                'path' => $path,
            ]);
        }
        return response()->json([
            'success' => true,
            'error' => 'none',
        ], 200);
    }

    private function syncPayfastGatewayConfig(Request $request, Team $team): void
    {
        $gateway = Gateway::firstOrCreate(['name' => 'PayFast']);

        $teamConfig = GatewayConfig::where('gateway_id', $gateway->id)
            ->where('team_id', $team->id)
            ->first();

        $defaultConfig = GatewayConfig::where('gateway_id', $gateway->id)
            ->whereNull('team_id')
            ->first();

        if (! $teamConfig) {
            $teamConfig = new GatewayConfig();
            $teamConfig->team_id = $team->id;
            $teamConfig->gateway_id = $gateway->id;

            if ($defaultConfig) {
                $teamConfig->is_enabled = (bool) $defaultConfig->is_enabled;
                $teamConfig->is_sandbox = (bool) $defaultConfig->is_sandbox;
                $teamConfig->config = $defaultConfig->config;
            } else {
                $teamConfig->is_enabled = false;
                $teamConfig->is_sandbox = true;
                $teamConfig->config = [];
            }
        }

        if ($request->exists('payfast_is_enabled')) {
            $teamConfig->is_enabled = filter_var($request->input('payfast_is_enabled'), FILTER_VALIDATE_BOOL);
        }

        if ($request->exists('payfast_is_sandbox')) {
            $teamConfig->is_sandbox = filter_var($request->input('payfast_is_sandbox'), FILTER_VALIDATE_BOOL);
        }

        $config = is_array($teamConfig->config) ? $teamConfig->config : [];

        if ($request->exists('payfast_merchant_id')) {
            $config['merchant_id'] = trim((string) $request->input('payfast_merchant_id'));
        }

        if ($request->exists('payfast_merchant_key')) {
            $config['merchant_key'] = trim((string) $request->input('payfast_merchant_key'));
        }

        // Passphrase is optional and may legitimately be an empty string.
        if ($request->exists('payfast_passphrase')) {
            $config['passphrase'] = (string) $request->input('payfast_passphrase');
        }

        // Keep URLs in sync with env defaults (team overrides can still differ if you decide to later).
        $config['sandbox_url'] = $config['sandbox_url'] ?? config('payfast.sandbox_url');
        $config['live_url'] = $config['live_url'] ?? config('payfast.live_url');

        if ($teamConfig->is_enabled) {
            $errors = [];
            if (empty($config['merchant_id'] ?? '')) {
                $errors['payfast_merchant_id'] = 'Merchant ID is required when PayFast is enabled.';
            }
            if (empty($config['merchant_key'] ?? '')) {
                $errors['payfast_merchant_key'] = 'Merchant key is required when PayFast is enabled.';
            }
            if (!empty($errors)) {
                throw ValidationException::withMessages($errors);
            }
        }

        $teamConfig->config = $config;
        $teamConfig->save();

        // Masked audit entry so secrets never end up in logs.
        AuditLog::create([
            'description' => 'PayFast config updated',
            'subject_id' => $teamConfig->id,
            'subject_type' => sprintf('%s#%s', GatewayConfig::class, $teamConfig->id),
            'user_id' => auth()->id(),
            'properties' => [
                'team_id' => $team->id,
                'gateway_id' => $gateway->id,
                'is_enabled' => (bool) $teamConfig->is_enabled,
                'is_sandbox' => (bool) $teamConfig->is_sandbox,
                'merchant_id' => $config['merchant_id'] ?? null,
                'merchant_key' => !empty($config['merchant_key'] ?? '') ? 'configured' : 'blank',
                'passphrase' => !empty($config['passphrase'] ?? '') ? 'configured' : 'blank',
            ],
            'host' => request()->ip(),
        ]);
    }


    public function show(Team $team)
    {
        abort_if(Gate::denies('team_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $team->load('owner');

        return view('Admin.teams.show', compact('team'));
    }

    public function destroy(Team $team)
    {
        abort_if(Gate::denies('team_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $team->delete();

        return back();
    }


    public function change(Request $request)
    {

        $request->validate([
            'team_id' => 'nullable|exists:teams,id',
        ]);
        //dd($request);
        $team_id = $request->team_id;

        //$team = Team::find($team_id);$team &&
        $user = auth()->user();
        $user = User::find($user->id)->with('roles')->first();
        // dd($user);
        if ($user->hasAnyRoles(['Admin', 'Director'])) {
            $user->team_id = $team_id;
            $user->save();
            session(['team_id' => $user->team_id]);

            return redirect()->back()->with('message', 'Team changed.');
            Log::info('site controller site id: ' . $team->id);
        } else {
            Log::info('site controller site: none');
        }

        return redirect()->back()->with('message', 'Site changed.');

        // $team = Team::find($team_id);
        // //dd($team);
        // if ($team) {
        //     session(['team_id' => $team->id]);
        //     Log::info('site controller site id: ' . $team->id);
        // } else {
        //     Log::info('site controller site: none');
        // }
        // return redirect()->back()->with('message', 'Site changed.');
    }
}
