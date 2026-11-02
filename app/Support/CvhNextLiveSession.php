<?php

namespace App\Support;

use App\Models\Session;
use App\Models\Webinar;

/**
 * CVH Africa — the next live class on the site (a session that has not ended yet, in a published, public course), for
 * the homepage hero badge. The badge used to be typed text pointing at the HD4A project page, so it named a session
 * that had no date yet and never led to a Join button.
 */
class CvhNextLiveSession
{
    public static function find(): ?Session
    {
        try {
            return Session::query()
                ->where('status', Session::$Active)
                ->whereNotNull('webinar_id')
                ->whereRaw('(`date` + (`duration` * 60)) > ?', [time()])
                ->whereHas('webinar', function ($query) {
                    $query->where('status', Webinar::$active)
                        ->where('private', false);
                })
                ->with(['webinar.teacher'])
                ->orderBy('date')
                ->first();
        } catch (\Throwable $exception) {
            return null; // never break the homepage
        }
    }

    /** "Tue 4 Nov 2026 · 14:00–15:30 EAT" in the site's time zone. */
    public static function timeRange(Session $session): string
    {
        $end = $session->date + ((int)$session->duration * 60);
        $zone = dateTimeFormat($session->date, 'T', false);

        return dateTimeFormat($session->date, 'D j M Y', false) . ' · '
            . dateTimeFormat($session->date, 'H:i', false) . '–' . dateTimeFormat($end, 'H:i', false)
            . (preg_match('/^[A-Z]{2,5}$/', $zone) ? " {$zone}" : '');
    }

    public static function isLive(Session $session): bool
    {
        return $session->date <= time() and !$session->isFinished();
    }
}
