<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CvhGoogleMeet;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * CVH Africa: the actions behind the Google Meet block in Settings → General → Features (the Client ID / secret
 * fields there are saved by the Features form itself): connect CVH's Google account (consent screen → callback),
 * test the connection, disconnect. Sessions then use it through App\Support\CvhGoogleMeet.
 */
class CvhGoogleMeetController extends Controller
{
    /** The settings live in Settings → General → Features. */
    private function back()
    {
        return redirect(getAdminPanelUrl('/settings/general') . '#google-meet');
    }

    public function index()
    {
        $this->authorize('admin_settings');

        return $this->back();
    }

    public function connect(Request $request)
    {
        $this->authorize('admin_settings');

        $settings = CvhGoogleMeet::settings();
        if (empty($settings['client_id']) || empty($settings['client_secret'])) {
            return $this->back()->with('cvh_meet_error', 'Enter the Google Meet Client ID and Client secret and click Save changes first.');
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
            return $this->back()->with('cvh_meet_error', 'The Google sign-in expired. Please click "Connect Google account" again.');
        }
        if ($request->get('error')) {
            return $this->back()->with('cvh_meet_error', 'Google access was not granted (' . $request->get('error') . ').');
        }

        try {
            CvhGoogleMeet::connect((string)$request->get('code'));
        } catch (\Throwable $exception) {
            return $this->back()->with('cvh_meet_error', $exception->getMessage());
        }

        return $this->back()->with('cvh_meet_status', 'Google account connected. Click "Test connection" to check it.');
    }

    public function test()
    {
        $this->authorize('admin_settings');

        try {
            $link = CvhGoogleMeet::test();
        } catch (\Throwable $exception) {
            return $this->back()->with('cvh_meet_error', $exception->getMessage());
        }

        return $this->back()->with('cvh_meet_status', "Working: Google created a test meeting ({$link}) and it was removed again.");
    }

    public function disconnect()
    {
        $this->authorize('admin_settings');

        CvhGoogleMeet::disconnect();

        return $this->back()->with('cvh_meet_status', 'Google account disconnected. New Google Meet sessions cannot be created until you connect again.');
    }
}
