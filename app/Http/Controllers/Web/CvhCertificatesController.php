<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Webinar;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * CVH Africa: in-house certificate of completion (the template's certificate feature is part of the paid plugin).
 * A learner who is enrolled in a course and has completed 100% of its content can download a CVH-branded PDF;
 * anyone can verify a certificate by its code at /certificates/verify. Data: cvh_certificates.
 */
class CvhCertificatesController extends Controller
{
    /** Learner's enrolled courses with progress and certificate status. */
    public function index()
    {
        $user = auth()->user();

        $courseIds = DB::table('sales')->where('buyer_id', $user->id)->whereNotNull('webinar_id')
            ->whereNull('refund_at')->pluck('webinar_id')->unique();

        $courses = Webinar::query()->whereIn('id', $courseIds)->where('status', 'active')->get();
        $issued = DB::table('cvh_certificates')->where('user_id', $user->id)->get()->keyBy('webinar_id');

        $rows = $courses->map(fn($course) => [
            'course' => $course,
            'progress' => $course->getProgress(true, $user),
            'certificate' => $issued[$course->id] ?? null,
        ])->sortByDesc('progress')->values();

        return view('design_1.web.cvh_certificates.index', [
            'pageTitle' => 'My Certificates',
            'pageRobot' => 'noindex, nofollow',
            'rows' => $rows,
        ]);
    }

    /** Issue (first time) and download the PDF for a completed course. */
    public function download($webinarId)
    {
        $user = auth()->user();
        $course = Webinar::query()->where('id', $webinarId)->where('status', 'active')->first();
        abort_if(empty($course), 404);

        $certificate = DB::table('cvh_certificates')->where('user_id', $user->id)->where('webinar_id', $course->id)->first();

        if (empty($certificate)) {
            $enrolled = DB::table('sales')->where('buyer_id', $user->id)->where('webinar_id', $course->id)->whereNull('refund_at')->exists();
            $progress = $enrolled ? $course->getProgress(true, $user) : 0;

            if (!$enrolled or $progress < 100) {
                return redirect('/my-certificates')->with('cvh_cert_status', 'Complete all lessons of "' . $course->title . '" to unlock your certificate (current progress: ' . round($progress) . '%).');
            }

            do {
                $code = 'CVH-' . date('Y') . '-' . strtoupper(Str::random(8));
            } while (DB::table('cvh_certificates')->where('code', $code)->exists());

            DB::table('cvh_certificates')->insert([
                'code' => $code,
                'user_id' => $user->id,
                'webinar_id' => $course->id,
                'student_name' => $user->full_name,
                'course_title' => $course->title,
                'issued_at' => time(),
            ]);
            $certificate = DB::table('cvh_certificates')->where('code', $code)->first();
        }

        return $this->pdf($certificate)->download('CVH-Certificate-' . Str::slug($certificate->course_title) . '.pdf');
    }

    /** Public verification: form, and result when ?code= is given. */
    public function verify(Request $request)
    {
        $code = strtoupper(trim((string)$request->get('code')));
        $certificate = $code !== '' ? DB::table('cvh_certificates')->where('code', $code)->first() : null;

        return view('design_1.web.cvh_certificates.verify', [
            'pageTitle' => 'Verify a Certificate',
            'pageDescription' => 'Check that a CVH Africa certificate of completion is genuine.',
            'pageRobot' => 'index, follow',
            'code' => $code,
            'certificate' => $certificate,
        ]);
    }

    private function pdf($certificate)
    {
        $config = config('cvh_certificate');
        $verifyUrl = rtrim(config('app.url'), '/') . '/certificates/verify?code=' . urlencode($certificate->code);
        $qrSvg = (string)QrCode::format('svg')->size(220)->margin(0)->generate($verifyUrl);

        $html = view('design_1.web.cvh_certificates.pdf', [
            'certificate' => $certificate,
            'config' => $config,
            'verifyUrl' => $verifyUrl,
            'qr' => 'data:image/svg+xml;base64,' . base64_encode($qrSvg),
            'img' => fn($path) => $path ? public_path(ltrim(rawurldecode($path), '/')) : null,
            'issuedOn' => dateTimeFormat($certificate->issued_at, 'j F Y', false),
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->setOption(['isRemoteEnabled' => false, 'chroot' => public_path(), 'defaultFont' => 'DejaVu Sans']);
    }
}
