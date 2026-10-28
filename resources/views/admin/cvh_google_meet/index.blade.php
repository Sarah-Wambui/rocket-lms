{{-- CVH Africa: Admin → Settings → Google Meet (connect CVH's Google account for automatic Meet links) --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ $pageTitle }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item active">{{ $pageTitle }}</div>
            </div>
        </div>

        <div class="section-body">
            @if(session('cvh_meet_status'))
                <div class="alert alert-success">{{ session('cvh_meet_status') }}</div>
            @endif
            @if(session('cvh_meet_error'))
                <div class="alert alert-danger">{{ session('cvh_meet_error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <div class="row">
                <div class="col-12 col-lg-7">
                    <div class="card">
                        <div class="card-header"><h5 class="font-14 mb-0">Connection</h5></div>
                        <div class="card-body">
                            @if($connected)
                                <p class="font-14">Connected to <b>{{ $settings['account_email'] ?: 'Google account' }}</b>{{ $settings['connected_at'] ? ' since ' . dateTimeFormat($settings['connected_at'], 'j M Y') : '' }}.</p>
                                <p class="text-muted font-12">Sessions saved with <b>Google Meet</b> get a Meet link on this account's Google Calendar. Open the session's Join button (or the event in that Google Calendar) to start the class as host.</p>
                                <div class="d-flex flex-wrap mt-3">
                                    <form action="{{ getAdminPanelUrl('/cvh-google-meet/test') }}" method="post" class="mr-2 mb-2">
                                        {{ csrf_field() }}
                                        <button type="submit" class="btn btn-primary">Test connection</button>
                                    </form>
                                    <a href="{{ getAdminPanelUrl('/cvh-google-meet/connect') }}" class="btn btn-outline-primary mr-2 mb-2">Reconnect / use another account</a>
                                    <form action="{{ getAdminPanelUrl('/cvh-google-meet/disconnect') }}" method="post" class="mb-2" onsubmit="return confirm('Disconnect Google Meet? Existing Meet links keep working; new Google Meet sessions cannot be created.')">
                                        {{ csrf_field() }}
                                        <button type="submit" class="btn btn-outline-danger">Disconnect</button>
                                    </form>
                                </div>
                            @else
                                <p class="font-14">Not connected yet.</p>
                                @if(!empty($settings['client_id']))
                                    <a href="{{ getAdminPanelUrl('/cvh-google-meet/connect') }}" class="btn btn-primary">Connect Google account</a>
                                    <p class="text-muted font-12 mt-2">Sign in with the Google account that should host the classes (e.g. CVH's) and allow access to its calendar.</p>
                                @else
                                    <p class="text-muted font-12">Enter the Client ID and Client secret below first.</p>
                                @endif
                            @endif

                            @if(!$enabledForSessions)
                                <div class="alert alert-warning mt-3 mb-0">Google Meet is not offered in the session form yet: tick it in <a href="{{ getAdminPanelUrl('/settings/general?tab=features') }}">Settings → General → Features → Available session APIs</a>.</div>
                            @endif
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h5 class="font-14 mb-0">Google app (OAuth client)</h5></div>
                        <div class="card-body">
                            <form action="{{ getAdminPanelUrl('/cvh-google-meet') }}" method="post">
                                {{ csrf_field() }}
                                <div class="form-group">
                                    <label class="input-label">Client ID</label>
                                    <input type="text" name="client_id" value="{{ $settings['client_id'] }}" class="form-control" placeholder="…apps.googleusercontent.com">
                                </div>
                                <div class="form-group">
                                    <label class="input-label">Client secret</label>
                                    <input type="password" name="client_secret" value="" class="form-control" placeholder="{{ !empty($settings['client_secret']) ? 'Saved (leave empty to keep it)' : 'GOCSPX-…' }}" autocomplete="new-password">
                                </div>
                                <div class="form-group">
                                    <label class="input-label">Authorized redirect URI (copy this into Google Cloud)</label>
                                    <input type="text" readonly value="{{ $redirectUri }}" class="form-control" onclick="this.select()">
                                </div>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="card">
                        <div class="card-header"><h5 class="font-14 mb-0">One-time setup in Google Cloud</h5></div>
                        <div class="card-body font-14">
                            <ol class="pl-3 mb-0">
                                <li class="mb-2">Go to <b>console.cloud.google.com</b>, signed in with CVH's Google account, and create a project (e.g. "CVH LMS").</li>
                                <li class="mb-2"><b>APIs &amp; Services → Library</b>: enable <b>Google Calendar API</b>.</li>
                                <li class="mb-2"><b>OAuth consent screen</b>: app name "CVH Africa LMS", support email; user type <b>Internal</b> on Google Workspace, otherwise <b>External</b> and click <b>Publish app</b> (in "Testing" mode Google drops the connection after 7 days).</li>
                                <li class="mb-2"><b>Credentials → Create credentials → OAuth client ID</b> → type <b>Web application</b>; under <b>Authorized redirect URIs</b> paste the URI shown on the left.</li>
                                <li class="mb-2">Copy the <b>Client ID</b> and <b>Client secret</b> here, Save, then <b>Connect Google account</b>.</li>
                            </ol>
                            <p class="text-muted font-12 mt-3 mb-0">Free Gmail accounts limit Meet calls with 3+ people to 60 minutes; Google Workspace accounts don't. Learners join with the link; people not invited may have to "ask to join" and the host admits them.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
