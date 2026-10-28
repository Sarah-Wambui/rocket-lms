<?php

namespace App\Support;

use App\Models\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * CVH Africa — Google Meet for live sessions, like the template's Zoom integration: an admin connects CVH's Google
 * account once (Admin → Settings → General → Features → Google Meet); every session saved with the "Google Meet" API then gets a Google
 * Calendar event with a Meet link on that account's calendar, kept in step when the session changes or is deleted.
 * Uses Google's OAuth 2.0 and Calendar API v3 over HTTP (no SDK). Settings live in cvh_settings ('google_meet');
 * the event id is stored in sessions.google_event_id (database/cvh/18_google_meet.php).
 */
class CvhGoogleMeet
{
    public const SCOPE = 'https://www.googleapis.com/auth/calendar.events';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const CALENDAR_URL = 'https://www.googleapis.com/calendar/v3/calendars/primary';

    /**
     * The OAuth client (Client ID / secret) is entered in Settings → General → Features with the Zoom keys; the
     * connection (refresh token, account) is kept in cvh_settings and only counts for the client it was made with.
     */
    public static function settings(): array
    {
        $stored = Schema::hasTable('cvh_settings') ? DB::table('cvh_settings')->where('name', 'google_meet')->value('value') : null;
        $settings = array_merge(['client_id' => '', 'client_secret' => '', 'refresh_token' => '', 'account_email' => '', 'connected_at' => null, 'connected_client_id' => ''],
            json_decode((string)$stored, true) ?: []);

        $featureId = trim((string)getFeaturesSettings('google_meet_client_id'));
        $featureSecret = trim((string)getFeaturesSettings('google_meet_client_secret'));
        if ($featureId !== '') {
            $settings['client_id'] = $featureId;
            $settings['client_secret'] = $featureSecret;
        }

        return $settings;
    }

    public static function saveSettings(array $values): void
    {
        $stored = Schema::hasTable('cvh_settings') ? DB::table('cvh_settings')->where('name', 'google_meet')->value('value') : null;
        $settings = array_merge(json_decode((string)$stored, true) ?: [], $values);
        DB::table('cvh_settings')->updateOrInsert(['name' => 'google_meet'], ['value' => json_encode($settings), 'updated_at' => time()]);
        Cache::forget('cvh_google_meet_token');
    }

    public static function connected(): bool
    {
        $settings = self::settings();
        return !empty($settings['client_id']) && !empty($settings['client_secret']) && !empty($settings['refresh_token'])
            && (empty($settings['connected_client_id']) || $settings['connected_client_id'] === $settings['client_id']);
    }

    public static function redirectUri(): string
    {
        return url(getAdminPanelUrl('/cvh-google-meet/callback'));
    }

    /** Google's consent screen; `offline` + `consent` so Google returns a refresh token. */
    public static function authorizationUrl(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'client_id' => self::settings()['client_id'],
                'redirect_uri' => self::redirectUri(),
                'response_type' => 'code',
                'scope' => self::SCOPE,
                'access_type' => 'offline',
                'prompt' => 'consent',
                'include_granted_scopes' => 'true',
                'state' => $state,
            ]);
    }

    /** Exchange the code from the consent screen for a refresh token and remember which account it belongs to. */
    public static function connect(string $code): void
    {
        $settings = self::settings();
        $response = Http::asForm()->timeout(30)->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => $settings['client_id'],
            'client_secret' => $settings['client_secret'],
            'redirect_uri' => self::redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful() || empty($response->json('refresh_token'))) {
            throw new \RuntimeException('Google did not return access: ' . ($response->json('error_description') ?? $response->json('error') ?? $response->status()));
        }

        // The primary calendar's id is the account's email address
        $calendar = Http::withToken($response->json('access_token'))->timeout(30)->get(self::CALENDAR_URL);

        self::saveSettings([
            'refresh_token' => $response->json('refresh_token'),
            'account_email' => (string)$calendar->json('id'),
            'connected_at' => time(),
            'connected_client_id' => $settings['client_id'],
        ]);
        Cache::put('cvh_google_meet_token', $response->json('access_token'), max(60, (int)$response->json('expires_in', 3600) - 120));

        self::offerInSessionForm(); // connected = ready to use: offer it in the live session form
    }

    /**
     * Add "Google Meet" to Settings → General → Features → Live Class Providers (available_session_apis), so the live
     * session form offers it, and drop the cached settings. Returns false when it was already there.
     */
    public static function offerInSessionForm(): bool
    {
        $settingId = DB::table('settings')->where('name', 'features')->value('id');
        if (!$settingId) {
            return false;
        }

        $added = false;
        foreach (DB::table('setting_translations')->where('setting_id', $settingId)->get() as $row) {
            $value = json_decode((string)$row->value, true) ?: [];
            $apis = array_values(array_filter((array)($value['available_session_apis'] ?? [])));
            if (!in_array('google_meet', $apis, true)) {
                $apis[] = 'google_meet';
                $value['available_session_apis'] = $apis;
                DB::table('setting_translations')->where('id', $row->id)->update(['value' => json_encode($value)]);
                $added = true;
            }
        }

        Cache::forget('settings.features');

        return $added;
    }

    public static function disconnect(): void
    {
        $token = self::settings()['refresh_token'];
        if ($token) {
            Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/revoke', ['token' => $token]);
        }
        self::saveSettings(['refresh_token' => '', 'account_email' => '', 'connected_at' => null, 'connected_client_id' => '']);
    }

    private static function accessToken(): string
    {
        return Cache::remember('cvh_google_meet_token', 3000, function () {
            $settings = self::settings();
            $response = Http::asForm()->timeout(30)->post(self::TOKEN_URL, [
                'client_id' => $settings['client_id'],
                'client_secret' => $settings['client_secret'],
                'refresh_token' => $settings['refresh_token'],
                'grant_type' => 'refresh_token',
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException('Google refused the saved connection (' . ($response->json('error') ?? $response->status()) . '). Reconnect the Google account in Settings → General → Features → Google Meet.');
            }
            return $response->json('access_token');
        });
    }

    private static function eventBody(Session $session): array
    {
        $course = $session->webinar;
        $start = (int)$session->date;
        $end = $start + max(15, (int)$session->duration) * 60;

        return [
            'summary' => trim(($course ? $course->title . ': ' : '') . $session->title),
            'description' => 'CVH Africa live class.' . ($course ? ' Course: ' . url('/course/' . $course->slug) : ''),
            'start' => ['dateTime' => gmdate('Y-m-d\TH:i:s\Z', $start), 'timeZone' => 'UTC'],
            'end' => ['dateTime' => gmdate('Y-m-d\TH:i:s\Z', $end), 'timeZone' => 'UTC'],
        ];
    }

    /** Create (or, when it already has one, update) the session's Calendar event and save its Meet link on the session. */
    public static function sync(Session $session): void
    {
        if (!self::connected()) {
            throw new \RuntimeException('Google Meet is not connected yet: in Settings → General → Features → Google Meet API Settings, click “Connect Google account” and allow access.');
        }

        if (!empty($session->google_event_id)) {
            $response = Http::withToken(self::accessToken())->timeout(30)
                ->patch(self::CALENDAR_URL . '/events/' . rawurlencode($session->google_event_id) . '?sendUpdates=none', self::eventBody($session));

            if ($response->status() !== 404 && $response->status() !== 410) {
                self::check($response, 'update');
                if (empty($session->link) && $response->json('hangoutLink')) {
                    DB::table('sessions')->where('id', $session->id)->update(['link' => $response->json('hangoutLink')]);
                    $session->link = $response->json('hangoutLink');
                }
                return;
            }
            // the event was deleted in Google Calendar: make a new one
        }

        $body = self::eventBody($session) + ['conferenceData' => ['createRequest' => [
                'requestId' => 'cvh-session-' . $session->id . '-' . Str::random(8),
                'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
            ]]];
        $response = Http::withToken(self::accessToken())->timeout(30)
            ->post(self::CALENDAR_URL . '/events?conferenceDataVersion=1&sendUpdates=none', $body);
        self::check($response, 'create');

        $eventId = $response->json('id');
        $link = $response->json('hangoutLink');

        // Google sometimes finishes adding the Meet a moment later
        for ($try = 0; !$link && $try < 3; $try++) {
            usleep(800000);
            $link = Http::withToken(self::accessToken())->timeout(30)->get(self::CALENDAR_URL . '/events/' . rawurlencode($eventId))->json('hangoutLink');
        }
        if (!$link) {
            throw new \RuntimeException('Google created the calendar event but no Meet link. Check that Google Meet is turned on for this Google account.');
        }

        DB::table('sessions')->where('id', $session->id)->update(['google_event_id' => $eventId, 'link' => $link, 'updated_at' => time()]);
        $session->google_event_id = $eventId;
        $session->link = $link;
    }

    /** Remove the session's Calendar event (and with it the Meet); failures are only logged. */
    public static function remove(Session $session): void
    {
        if (empty($session->google_event_id) || !self::connected()) {
            return;
        }
        try {
            Http::withToken(self::accessToken())->timeout(30)
                ->delete(self::CALENDAR_URL . '/events/' . rawurlencode($session->google_event_id) . '?sendUpdates=none');
        } catch (\Throwable $exception) {
            Log::warning('Google Meet: could not delete event for session ' . $session->id . ': ' . $exception->getMessage());
        }
    }

    /** Create a short test event, return its Meet link, delete it again. */
    public static function test(): string
    {
        $start = time() + 3600;
        $response = Http::withToken(self::accessToken())->timeout(30)->post(self::CALENDAR_URL . '/events?conferenceDataVersion=1&sendUpdates=none', [
            'summary' => 'CVH Africa connection test',
            'start' => ['dateTime' => gmdate('Y-m-d\TH:i:s\Z', $start)],
            'end' => ['dateTime' => gmdate('Y-m-d\TH:i:s\Z', $start + 900)],
            'conferenceData' => ['createRequest' => ['requestId' => 'cvh-test-' . Str::random(10), 'conferenceSolutionKey' => ['type' => 'hangoutsMeet']]],
        ]);
        self::check($response, 'create');
        Http::withToken(self::accessToken())->timeout(30)->delete(self::CALENDAR_URL . '/events/' . rawurlencode($response->json('id')) . '?sendUpdates=none');

        return (string)$response->json('hangoutLink');
    }

    private static function check($response, string $action): void
    {
        if (!$response->successful()) {
            $message = $response->json('error.message') ?? ('HTTP ' . $response->status());
            Log::warning("Google Meet: {$action} failed: {$message}");
            throw new \RuntimeException("Google Calendar refused to {$action} the meeting: {$message}");
        }
    }
}
