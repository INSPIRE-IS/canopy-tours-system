<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Everlytic API documentation</title>
    <style>
        :root { --ink:#172019; --muted:#647067; --line:#d9dfda; --paper:#fff; --wash:#f1f4ef; --green:#24751f; --red:#a22d24; }
        * { box-sizing:border-box; }
        body { margin:0; color:var(--ink); background:linear-gradient(140deg,#edf2eb,#faf7ef 58%,#eef3ed); font:15px/1.5 "Segoe UI",sans-serif; }
        header { display:flex; justify-content:space-between; gap:20px; align-items:center; padding:22px 5vw; background:#17251b; color:#fff; }
        header h1 { margin:0; font:600 24px Georgia,serif; }
        header p { margin:3px 0 0; color:#cad5cc; }
        main { width:min(1160px,92vw); margin:32px auto 60px; display:grid; grid-template-columns:minmax(0,1fr) minmax(330px,.82fr); gap:22px; }
        .card { padding:24px; border:1px solid var(--line); border-radius:14px; background:rgba(255,255,255,.96); box-shadow:0 12px 28px rgba(24,44,30,.06); }
        .wide { grid-column:1/-1; }
        h2 { margin:0 0 14px; font:600 21px Georgia,serif; }
        h3 { margin:22px 0 8px; font-size:16px; }
        code,pre,input { font-family:Consolas,monospace; }
        .endpoint { display:flex; gap:10px; align-items:center; overflow:auto; margin:12px 0; padding:12px; border:1px solid var(--line); border-radius:9px; background:var(--wash); }
        .verb { padding:3px 8px; border-radius:5px; background:var(--green); color:#fff; font:700 12px Consolas,monospace; }
        label { display:block; margin:14px 0 5px; font-weight:700; }
        input { width:100%; padding:10px 11px; border:1px solid #aeb8b0; border-radius:7px; background:#fff; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .hint { color:var(--muted); font-size:13px; }
        .warning { padding:10px 13px; border-left:4px solid #b27616; background:#fff8e9; }
        .success { padding:10px 13px; border-left:4px solid var(--green); background:#eef8ec; }
        .token-secret { display:block; overflow-wrap:anywhere; margin:10px 0; padding:12px; border:1px dashed #779276; border-radius:7px; background:#f4f8f3; }
        .actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:12px; }
        .hidden { display:none; }
        button { padding:10px 15px; border:1px solid var(--ink); border-radius:7px; background:#fff; color:var(--ink); font-weight:700; cursor:pointer; }
        button.primary { border-color:var(--green); background:var(--green); color:#fff; }
        pre { min-height:260px; max-height:580px; overflow:auto; padding:16px; border-radius:9px; background:#111914; color:#dce8df; white-space:pre-wrap; word-break:break-word; }
        .status { min-height:22px; margin:12px 0 0; font-weight:700; }
        .error { color:var(--red); }
        @media (max-width:850px) { main { grid-template-columns:1fr; } .wide { grid-column:auto; } .grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<header>
    <div>
        <h1>Everlytic API documentation</h1>
        <p>{{ $siteName ?: 'Canopy Tours' }} - private, read-only integration</p>
    </div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Sign out</button>
    </form>
</header>
<main>
    <section class="card">
        <h2>Attended guest feed</h2>
        <p>The endpoint returns guests who completed check-in, are marked <strong>arrived</strong>, and currently have an explicit marketing opt-in for this site.</p>
        <div class="endpoint"><span class="verb">GET</span><code>/api/v1/attended-guests?from=YYYY-MM-DD&amp;to=YYYY-MM-DD</code></div>
        <h3>Rules</h3>
        <ul>
            <li>The activity date range is inclusive.</li>
            <li>Each page returns at most {{ $pageSize }} records.</li>
            <li>Follow <code>links.next</code> while <code>meta.has_more</code> is true.</li>
            <li>Use <code>contact_id</code> as the stable source identifier.</li>
            <li>The token is limited to the site assigned to this login.</li>
        </ul>
        <p class="warning">This page never stores the bearer token. Keep it in approved secret storage and paste it here only while testing.</p>
        <p><a href="{{ $openApiUrl }}" target="_blank" rel="noopener">Open the protected OpenAPI JSON</a></p>
    </section>

    <section class="card">
        <h2>Bearer token</h2>
        <div id="token-active" class="{{ $activeToken ? '' : 'hidden' }}">
            <p class="success">An active Everlytic token exists for this login.</p>
            <p><strong>Name:</strong> <span id="active-token-name">{{ $activeToken?->name }}</span></p>
            <p><strong>Created:</strong> <span id="active-token-created">{{ $activeToken?->created_at?->toIso8601String() }}</span></p>
            <p><strong>Last used:</strong> <span id="active-token-used">{{ $activeToken?->last_used_at?->toIso8601String() ?: 'Never' }}</span></p>
            <p class="hint">The secret cannot be displayed again. If it was lost, ask an administrator to revoke it, then return here to create a replacement.</p>
        </div>

        @if($canSelfIssueToken)
            <div id="token-create" class="{{ $activeToken ? 'hidden' : '' }}">
                <p>No active bearer token exists. Confirm your password to create one.</p>
                <label for="token-password">Your login password</label>
                <input id="token-password" type="password" autocomplete="current-password">
                <button id="create-token" class="primary" type="button">Create bearer token</button>
                <div id="token-create-status" class="status" aria-live="polite"></div>
            </div>

            <div id="token-once" class="hidden">
                <p class="warning"><strong>Copy this token now.</strong> It cannot be displayed again after this page is refreshed or closed.</p>
                <code id="new-token" class="token-secret"></code>
                <div class="actions">
                    <button id="copy-token" class="primary" type="button">Copy token</button>
                    <button id="stored-token" type="button">I have stored it securely</button>
                </div>
                <div id="copy-status" class="status" aria-live="polite"></div>
            </div>
        @elseif(!$activeToken)
            <p class="hint">Admin can preview this documentation. Bearer-token creation is available only when the restricted Integration API user logs in.</p>
        @endif
    </section>

    <section class="card wide">
        <h2>Try a request</h2>
        <label for="token">Bearer token</label>
        <input id="token" type="password" autocomplete="off" placeholder="Paste the issued Sanctum token">
        <div class="grid">
            <div><label for="from">Activity from</label><input id="from" type="date"></div>
            <div><label for="to">Activity to</label><input id="to" type="date"></div>
        </div>
        <p class="hint">The fixed server page size is {{ $pageSize }}. Use the returned next link for additional pages.</p>
        <button id="send" class="primary" type="button">Send test request</button>
        <div id="status" class="status" aria-live="polite"></div>
    </section>

    <section class="card wide">
        <h2>Response</h2>
        <pre id="response">No request sent yet.</pre>
    </section>
</main>
<script>
    const status = document.getElementById('status');
    const output = document.getElementById('response');
    const csrfToken = @json(csrf_token());
    const today = new Date().toISOString().slice(0, 10);
    document.getElementById('from').value = today;
    document.getElementById('to').value = today;

    const createTokenButton = document.getElementById('create-token');
    createTokenButton?.addEventListener('click', async () => {
        const passwordInput = document.getElementById('token-password');
        const createStatus = document.getElementById('token-create-status');
        if (!passwordInput.value) {
            createStatus.className = 'status error';
            createStatus.textContent = 'Your login password is required.';
            return;
        }

        createTokenButton.disabled = true;
        createStatus.className = 'status';
        createStatus.textContent = 'Creating token...';

        try {
            const response = await fetch(@json(route('integration.everlytic.token.store')), {
                method:'POST',
                credentials:'same-origin',
                headers:{
                    Accept:'application/json',
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN':csrfToken,
                },
                body:JSON.stringify({ password:passwordInput.value }),
            });
            const body = await response.json().catch(() => ({}));
            if (response.status === 419 && body.redirect) {
                window.location.assign(body.redirect);
                return;
            }
            if (!response.ok) {
                createStatus.className = 'status error';
                createStatus.textContent = body?.errors?.password?.[0] || body?.message || 'The token could not be created.';
                return;
            }

            passwordInput.value = '';
            document.getElementById('new-token').textContent = body.token;
            document.getElementById('token').value = body.token;
            document.getElementById('active-token-name').textContent = body.metadata.name;
            document.getElementById('active-token-created').textContent = body.metadata.created_at;
            document.getElementById('active-token-used').textContent = 'Never';
            document.getElementById('token-create').classList.add('hidden');
            document.getElementById('token-active').classList.remove('hidden');
            document.getElementById('token-once').classList.remove('hidden');
        } catch (error) {
            createStatus.className = 'status error';
            createStatus.textContent = 'The token could not be created. Please try again.';
        } finally {
            createTokenButton.disabled = false;
        }
    });

    document.getElementById('copy-token')?.addEventListener('click', async () => {
        const token = document.getElementById('new-token').textContent;
        const copyStatus = document.getElementById('copy-status');
        try {
            await navigator.clipboard.writeText(token);
            copyStatus.className = 'status';
            copyStatus.textContent = 'Token copied.';
        } catch (error) {
            copyStatus.className = 'status error';
            copyStatus.textContent = 'Copy failed. Select and copy the token manually.';
        }
    });

    document.getElementById('stored-token')?.addEventListener('click', () => {
        document.getElementById('new-token').textContent = '';
        document.getElementById('token').value = '';
        document.getElementById('token-once').classList.add('hidden');
    });

    document.getElementById('send').addEventListener('click', async () => {
        const token = document.getElementById('token').value.trim();
        const from = document.getElementById('from').value;
        const to = document.getElementById('to').value;
        if (!token || !from || !to) {
            status.className = 'status error';
            status.textContent = 'Bearer token, from date, and to date are required.';
            return;
        }

        status.className = 'status';
        status.textContent = 'Sending...';
        output.textContent = '';

        try {
            const params = new URLSearchParams({ from, to });
            const response = await fetch(`/api/v1/attended-guests?${params.toString()}`, {
                headers: { Accept:'application/json', Authorization:`Bearer ${token}` },
                credentials: 'same-origin',
            });
            const body = await response.json().catch(() => ({ message:'Non-JSON response received.' }));
            status.className = response.ok ? 'status' : 'status error';
            status.textContent = `${response.status} ${response.statusText}`;
            output.textContent = JSON.stringify(body, null, 2);
        } catch (error) {
            status.className = 'status error';
            status.textContent = 'The request could not be completed.';
            output.textContent = String(error);
        }
    });
</script>
</body>
</html>
