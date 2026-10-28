<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CvhGoogleMeet;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * CVH Africa: Admin → Settings → Google Meet. Save the Google Cloud OAuth client, connect CVH's Google account
 * (consent screen → callback), test the connection, disconnect. Sessions then use it through App\Support\CvhGoogleMeet.
 */
class CvhGoogleMeetController extends Controller
{
    public function index()
    {
        $this->authorize('admin_settings');

        $settings = CvhGoogleMeet::settings();

        return view('admin.cvh_google_meet.index', [
            'pageTitle' => 'Google Meet',
            'settings' => $settings,
            'connected' => CvhGoogleMeet::connected(),
            'redirectUri' => CvhGoogleMeet::redirectUri(),
            'enabledForSessions' => in_array('google_meet', (array)getFeaturesSettings('available_session_apis')),
        ]);
    }

    public function save(Request $request)
    {
        $this->authorize('admin_settings');

        $this->validate($request, [
            'client_id' => 'required|string|max:255',
            'client_secret' => 'nullable|string|max:255',
        ]);

        $current = CvhGoogleMeet::settings();
        $clientId = trim($request->get('client_id'));
        $secret = trim((string)$request->get('client_secret')) ?: $current['client_secret']; // empty = keep the saved secret

        // A different Google app makes the saved connection useless
        $changes = ['client_id' => $clientId, 'client_secret' => $secret];
        if ($clientId !== $current['client_id']) {
            $changes += ['refresh_token' => '', 'account_email' => '', 'connected_at' => null];
        }
        CvhGoogleMeet::saveSettings($changes);

        return redirect(getAdminPanelUrl('/cvh-google-meet'))->with('cvh_meet_status', 'Saved. Now click "Connect Google account".');
    }

    public function connect(Request $request)
    {
        $this->authorize('admin_settings');

        $settings = CvhGoogleMeet::settings();
        if (empty($settings['client_id']) || empty($settings['client_secret'])) {
            return redirect(getAdminPanelUrl('/cvh-google-meet'))->with('cvh_meet_error', 'Enter and save the Client ID and Client secret first.');
        }

        $state = Str::random(40);
        $request->session()->put('cvh_google_meet_state', $state);

        return redirect()->away(CvhGoogleMeet::authorizationUrl($state));
    }

    public function callback(Request $request)
    {
        $this->authorize('admin_settings');

        $expected = $request->session()->pull('cvh_google_meet_state');
        if (!$expected || !hash_equals($expected, (string)$request->get('state'))) {
            return redirect(getAdminPanelUrl('/cvh-google-meet'))->with('cvh_meet_error', 'The Google sign-in expired. Please click "Connect Google account" again.');
        }
        if ($request->get('error')) {
            return redirect(getAdminPanelUrl('/cvh-google-meet'))->with('cvh_meet_error', 'Google access was not granted (' . $request->get('error') . ').');
        }

        try {
            CvhGoogleMeet::connect((string)$request->get('code'));
        } catch (\Throwable $exception) {
            return redirect(getAdminPanelUrl('/cvh-google-meet'))->with('cvh_meet_error', $exception->getMessage());
        }

        return redirect(getAdminPanelUrl('/cvh-google-meet'))->with('cvh_meet_status', 'Google account connected.');
    }

    public function test()
    {
        $this->authorize('admin_settings');

        try {
            $link = CvhGoogleMeet::test();
        } catch (\Throwable $exception) {
            return redirect(getAdminPanelUrl('/cvh-google-meet'))->with('cvh_meet_error', $exception->getMessage());
        }

        return redirect(getAdminPanelUrl('/cvh-google-meet'))->with('cvh_meet_status', "Working: Google created a test meeting ({$link}) and it was removed again.");
    }

    public function disconnect()
    {
        $this->authorize('admin_settings');

        CvhGoogleMeet::disconnect();

        return redirect(getAdminPanelUrl('/cvh-google-meet'))->with('cvh_meet_status', 'Google account disconnected. New Google Meet sessions cannot be created until you connect again.');
    }
}
