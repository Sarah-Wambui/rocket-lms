{{-- CVH Africa: Google Meet API settings inside Settings → General → Features (below Zoom).
     Client ID / secret are saved with the Features form (like Zoom's keys); connecting stores the Google account's
     refresh token separately (App\Support\CvhGoogleMeet). Test/Disconnect use forms outside this one (form="…"). --}}
@php
    $cvhMeet = \App\Support\CvhGoogleMeet::settings();
    $cvhMeetConnected = \App\Support\CvhGoogleMeet::connected();
    $cvhMeetOffered = !empty($itemValue['available_session_apis']) && is_array($itemValue['available_session_apis']) && in_array('google_meet', $itemValue['available_session_apis']);
@endphp

<div class="mb-5" id="cvhGoogleMeetSettings">
    <h5>Google Meet API Settings</h5>

    @if(session('cvh_meet_status'))
        <div class="alert alert-success">{{ session('cvh_meet_status') }}</div>
    @endif
    @if(session('cvh_meet_error'))
        <div class="alert alert-danger">{{ session('cvh_meet_error') }}</div>
    @endif

    <div class="form-group">
        <label>Client ID (Google Cloud OAuth, Web application)</label>
        <input type="text" name="value[google_meet_client_id]" value="{{ $cvhMeet['client_id'] }}" class="form-control" placeholder="…apps.googleusercontent.com"/>
    </div>
    <div class="form-group">
        <label>Client secret</label>
        <input type="password" name="value[google_meet_client_secret]" value="{{ $cvhMeet['client_secret'] }}" class="form-control" placeholder="GOCSPX-…" autocomplete="new-password"/>
    </div>
    <div class="form-group">
        <label>Authorized redirect URI (paste this into the Google Cloud OAuth client)</label>
        <input type="text" readonly value="{{ \App\Support\CvhGoogleMeet::redirectUri() }}" class="form-control" onclick="this.select()"/>
    </div>

    <div class="p-3 rounded" style="background: rgba(1,112,255,.06);">
        @if($cvhMeetConnected)
            <p class="mb-2">Connected to <b>{{ $cvhMeet['account_email'] ?: 'Google account' }}</b>{{ $cvhMeet['connected_at'] ? ' since ' . dateTimeFormat($cvhMeet['connected_at'], 'j M Y') : '' }}. Google Meet sessions get their Meet link on this account.</p>
            <button type="submit" form="cvhMeetTestForm" class="btn btn-sm btn-primary mr-1">Test connection</button>
            <a href="{{ getAdminPanelUrl('/cvh-google-meet/connect') }}" class="btn btn-sm btn-outline-primary mr-1">Reconnect / another account</a>
            <button type="submit" form="cvhMeetDisconnectForm" class="btn btn-sm btn-outline-danger" onclick="return confirm('Disconnect Google Meet? Existing Meet links keep working; new Google Meet sessions cannot be created.')">Disconnect</button>
        @elseif(!empty($cvhMeet['client_id']) and !empty($cvhMeet['client_secret']))
            <p class="mb-1"><b>Keys saved. One last step:</b> sign in to Google once so the site can create meetings on that account.</p>
            <p class="mb-2 text-muted font-12">The Client ID and secret identify the site to Google; Google still needs the hosting account’s permission (sign in → Allow).</p>
            <a href="{{ getAdminPanelUrl('/cvh-google-meet/connect') }}" class="btn btn-primary">Connect Google account</a>
        @else
            <p class="mb-0 text-muted">Enter the Client ID and Client secret, click <b>Save changes</b> below, then come back here to connect the Google account.</p>
        @endif
    </div>

    @if(!$cvhMeetOffered)
        <p class="text-warning font-12 mt-2 mb-0">To offer it in the live session form, also pick <b>Google Meet</b> under <b>Live Class Providers → Select Available Providers</b> above and click <b>Save changes</b>.</p>
    @endif
</div>

@push('scripts_bottom')
    {{-- outside the Features form (forms cannot be nested); the buttons above point here with form="…" --}}
    <form id="cvhMeetTestForm" action="{{ getAdminPanelUrl('/cvh-google-meet/test') }}" method="post" class="d-none">{{ csrf_field() }}</form>
    <form id="cvhMeetDisconnectForm" action="{{ getAdminPanelUrl('/cvh-google-meet/disconnect') }}" method="post" class="d-none">{{ csrf_field() }}</form>
    <script>
        (function ($) {
            "use strict";
            // coming back from Google / a test: open the Features tab at the Google Meet section
            if (window.location.hash === '#google-meet') {
                $('#features-tab').tab('show');
                setTimeout(function () {
                    var box = document.getElementById('cvhGoogleMeetSettings');
                    if (box) box.scrollIntoView({behavior: 'smooth', block: 'start'});
                }, 300);
            }
        })(jQuery);
    </script>
@endpush
