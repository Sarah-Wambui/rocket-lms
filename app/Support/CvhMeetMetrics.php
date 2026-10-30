<?php

namespace App\Support;

use App\Models\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * CVH Africa — engagement metrics for Google Meet live classes (Admin → Education → Live Class Metrics).
 * Reads, through Google's Meet REST API v2 on the account connected for Google Meet (CvhGoogleMeet), each time a
 * session's meeting was held, who took part, when they joined and left, and its recording/transcript links, and keeps
 * them in cvh_meet_conferences / cvh_meet_attendance (database/cvh/26_meet_metrics.php). Google keeps this data for
 * 30 days, so recent sessions are synced every hour (cvh:meet-metrics, run by cvh:reminders).
 * Participants are matched to LMS users by name among the course's learners and instructors (Meet does not share
 * participants' email addresses); unmatched people count as guests.
 */
class CvhMeetMetrics
{
    private const API = 'https://meet.googleapis.com/v2/';

    public static function ready(): bool
    {
        return CvhGoogleMeet::connected() && Schema::hasTable('cvh_meet_conferences');
    }

    /** The Meet code (abc-defg-hij) in a session's link, or null for a link that is not a Google Meet. */
    public static function meetingCode(?string $link): ?string
    {
        return preg_match('#meet\.google\.com/([a-z]{3}-[a-z]{4}-[a-z]{3})#i', (string)$link, $m) ? strtolower($m[1]) : null;
    }

    /** Sessions held in the last $days days whose data may still have changed since they were last synced. */
    public static function syncRecent(int $days = 25): array
    {
        $done = [];
        $sessions = Session::query()
            ->where('link', 'like', '%meet.google.com/%')
            ->whereBetween('date', [time() - $days * 86400, time()])
            ->get();

        foreach ($sessions as $session) {
            $lastSync = (int)DB::table('cvh_meet_conferences')->where('session_id', $session->id)->max('synced_at');
            $settled = (int)$session->date + max(15, (int)$session->duration) * 60 + 3 * 3600; // Google finishes its records a while after the end
            if ($lastSync and $lastSync > $settled) {
                continue;
            }
            $done[$session->id] = self::syncSession($session);
        }

        return $done;
    }

    /** Fetch every meeting held for the session and store it; returns how many meetings were found. */
    public static function syncSession(Session $session): int
    {
        $code = self::meetingCode($session->link);
        if (!$code) {
            return 0;
        }

        $records = self::list('conferenceRecords', ['filter' => 'space.meeting_code = "' . $code . '"'], 'conferenceRecords');
        $people = self::courseUsers($session);

        foreach ($records as $record) {
            $recording = collect(self::list($record['name'] . '/recordings', [], 'recordings'))->first(fn($r) => !empty($r['driveDestination']['exportUri']));
            $transcript = collect(self::list($record['name'] . '/transcripts', [], 'transcripts'))->first(fn($t) => !empty($t['docsDestination']['exportUri']));
            $participants = self::list($record['name'] . '/participants', ['pageSize' => 250], 'participants');

            DB::transaction(function () use ($session, $record, $recording, $transcript, $participants, $people) {
                DB::table('cvh_meet_conferences')->updateOrInsert(['conference_name' => $record['name']], [
                    'session_id' => $session->id,
                    'started_at' => self::time($record['startTime'] ?? null),
                    'ended_at' => self::time($record['endTime'] ?? null),
                    'participants' => count($participants),
                    'recording_url' => $recording['driveDestination']['exportUri'] ?? null,
                    'transcript_url' => $transcript['docsDestination']['exportUri'] ?? null,
                    'synced_at' => time(),
                ]);
                $conferenceId = DB::table('cvh_meet_conferences')->where('conference_name', $record['name'])->value('id');

                foreach ($participants as $participant) {
                    $kind = isset($participant['signedinUser']) ? 'signed_in' : (isset($participant['phoneUser']) ? 'phone' : 'anonymous');
                    $name = $participant['signedinUser']['displayName'] ?? $participant['anonymousUser']['displayName'] ?? $participant['phoneUser']['displayName'] ?? null;

                    $seconds = 0;
                    $sessions = self::list($participant['name'] . '/participantSessions', ['pageSize' => 250], 'participantSessions');
                    foreach ($sessions as $part) {
                        $start = self::time($part['startTime'] ?? null);
                        $end = self::time($part['endTime'] ?? null) ?? time(); // still in the call
                        if ($start) {
                            $seconds += max(0, $end - $start);
                        }
                    }

                    DB::table('cvh_meet_attendance')->updateOrInsert(['participant_name' => $participant['name']], [
                        'conference_id' => $conferenceId,
                        'session_id' => $session->id,
                        'display_name' => $name,
                        'kind' => $kind,
                        'user_id' => $people[self::normalise($name)] ?? null,
                        'first_join' => self::time($participant['earliestStartTime'] ?? null),
                        'last_leave' => self::time($participant['latestEndTime'] ?? null),
                        'seconds' => $seconds,
                        'joins' => max(1, count($sessions)),
                    ]);
                }
            });
        }

        return count($records);
    }

    /** Learners (enrolled, not refunded) and instructors of the session's course, as normalised name => user id; ambiguous names are left out. */
    private static function courseUsers(Session $session): array
    {
        $course = $session->webinar;
        if (!$course) {
            return [];
        }

        $ids = DB::table('sales')->where('webinar_id', $course->id)->whereNull('refund_at')->pluck('buyer_id')
            ->merge([$course->teacher_id, $session->creator_id])
            ->merge(DB::table('webinar_partner_teacher')->where('webinar_id', $course->id)->pluck('teacher_id'))
            ->filter()->unique();

        $byName = [];
        foreach (DB::table('users')->whereIn('id', $ids)->get(['id', 'full_name']) as $user) {
            $key = self::normalise($user->full_name);
            if ($key === '') continue;
            $byName[$key] = isset($byName[$key]) ? false : $user->id; // two users with the same name: don't guess
        }

        return array_filter($byName);
    }

    public static function normalise(?string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower((string)$name)));
    }

    /** All pages of a Meet API list call. */
    private static function list(string $path, array $query, string $key): array
    {
        $items = [];
        do {
            $response = Http::withToken(CvhGoogleMeet::accessToken())->timeout(30)->get(self::API . $path, $query);

            if ($response->status() === 403 || $response->status() === 401) {
                throw new \RuntimeException('Google did not allow reading meeting attendance (' . ($response->json('error.message') ?? $response->status()) . '). '
                    . 'Turn on the "Google Meet REST API" in the Google Cloud project, then reconnect the Google account in Settings → General → Features → Google Meet.');
            }
            if (!$response->successful()) {
                throw new \RuntimeException('Google Meet API: ' . ($response->json('error.message') ?? 'HTTP ' . $response->status()));
            }

            $items = array_merge($items, $response->json($key) ?? []);
            $query['pageToken'] = $response->json('nextPageToken');
        } while (!empty($query['pageToken']));

        return $items;
    }

    /** RFC 3339 time from Google (up to nanoseconds) as a Unix timestamp. */
    private static function time(?string $value): ?int
    {
        if (empty($value)) {
            return null;
        }
        $timestamp = strtotime(preg_replace('/\.\d+/', '', $value));
        return $timestamp === false ? null : $timestamp;
    }

    /**
     * Engagement figures for a set of sessions (each with its conferences and attendance):
     * per session and in total — learners enrolled / attended, attendance rate, guests, average time in class
     * (minutes and % of the class), on time (joined within 5 minutes of the scheduled start), stayed to the end.
     */
    public static function summarise($sessions): array
    {
        $rows = [];
        foreach ($sessions as $session) {
            $conferences = DB::table('cvh_meet_conferences')->where('session_id', $session->id)->orderBy('started_at')->get();
            if ($conferences->isEmpty()) {
                $rows[$session->id] = null;
                continue;
            }

            $attendance = DB::table('cvh_meet_attendance')->where('session_id', $session->id)->get();
            $course = $session->webinar;
            $teacherIds = $course ? collect([$course->teacher_id])->merge(DB::table('webinar_partner_teacher')->where('webinar_id', $course->id)->pluck('teacher_id'))->filter()->all() : [];
            $enrolled = $course ? DB::table('sales')->where('webinar_id', $course->id)->whereNull('refund_at')->distinct()->count('buyer_id') : 0;

            $start = (int)$conferences->min('started_at');
            $end = (int)($conferences->max('ended_at') ?: time());
            $length = max(60, $end - $start);

            // one line per person: the same learner joining two meetings of the session counts once
            $people = $attendance->groupBy(fn($a) => $a->user_id ? 'u' . $a->user_id : 'p' . $a->participant_name)->map(fn($rows) => (object)[
                'user_id' => $rows->first()->user_id,
                'seconds' => $rows->sum('seconds'),
                'first_join' => $rows->min('first_join'),
                'last_leave' => $rows->max('last_leave'),
                'joins' => $rows->sum('joins'),
            ]);
            $learners = $people->filter(fn($p) => $p->user_id && !in_array($p->user_id, $teacherIds));
            $audience = $people->filter(fn($p) => !$p->user_id || !in_array($p->user_id, $teacherIds)); // everyone but the instructors

            $rows[$session->id] = [
                'held_at' => $start,
                'length_minutes' => (int)round($length / 60),
                'scheduled_minutes' => (int)$session->duration,
                'participants' => $people->count(),
                'enrolled' => $enrolled,
                'learners' => $learners->count(),
                'guests' => $audience->count() - $learners->count(),
                'attendance_rate' => $enrolled ? round(100 * $learners->count() / $enrolled) : null,
                'avg_minutes' => $audience->count() ? (int)round($audience->avg('seconds') / 60) : 0,
                'avg_percent' => $audience->count() ? min(100, (int)round(100 * $audience->avg('seconds') / $length)) : 0,
                'on_time_percent' => $audience->count() ? (int)round(100 * $audience->filter(fn($p) => $p->first_join && $p->first_join <= (int)$session->date + 300)->count() / $audience->count()) : 0,
                'stayed_percent' => $audience->count() ? (int)round(100 * $audience->filter(fn($p) => $p->last_leave && $p->last_leave >= $end - 300)->count() / $audience->count()) : 0,
                'rejoins' => $audience->sum(fn($p) => max(0, $p->joins - 1)),
                'recording_url' => $conferences->pluck('recording_url')->filter()->first(),
                'transcript_url' => $conferences->pluck('transcript_url')->filter()->first(),
                'synced_at' => (int)$conferences->max('synced_at'),
            ];
        }

        $held = array_filter($rows);
        $withEnrolment = array_filter($held, fn($r) => $r['attendance_rate'] !== null);
        $totals = [
            'classes' => count($held),
            'attendances' => array_sum(array_column($held, 'participants')),
            'learners' => array_sum(array_column($held, 'learners')),
            'attendance_rate' => $withEnrolment ? (int)round(array_sum(array_column($withEnrolment, 'attendance_rate')) / count($withEnrolment)) : null,
            'avg_minutes' => $held ? (int)round(array_sum(array_column($held, 'avg_minutes')) / count($held)) : 0,
            'avg_percent' => $held ? (int)round(array_sum(array_column($held, 'avg_percent')) / count($held)) : 0,
            'on_time_percent' => $held ? (int)round(array_sum(array_column($held, 'on_time_percent')) / count($held)) : 0,
            'recorded' => count(array_filter($held, fn($r) => !empty($r['recording_url']))),
        ];

        return ['rows' => $rows, 'totals' => $totals];
    }
}
