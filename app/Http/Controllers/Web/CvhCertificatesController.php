<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\QuizzesResult;
use App\Models\Webinar;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
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
        $issued = DB::table('cvh_certificates')->where('user_id', $user->id)->where('quiz_id', 0)->get()->keyBy('webinar_id');
        $earnedBefore = DB::table('certificates')->where('type', 'course')->where('student_id', $user->id)->pluck('webinar_id')->flip();

        $rows = $courses->map(fn($course) => [
            'course' => $course,
            'progress' => $course->getProgress(true, $user),
            'certificate' => $issued[$course->id] ?? null,
            'earned' => isset($earnedBefore[$course->id]),
        ])->sortByDesc('progress')->values();

        // Passed quizzes that award a certificate (best passing result per quiz)
        $quizResults = QuizzesResult::query()
            ->where('user_id', $user->id)
            ->where('status', QuizzesResult::$passed)
            ->whereHas('quiz', fn($query) => $query->where('certificate', true))
            ->with(['quiz.webinar'])
            ->orderByDesc('user_grade')
            ->get()
            ->unique('quiz_id')
            ->values();
        $quizIssued = DB::table('cvh_certificates')->where('user_id', $user->id)->where('quiz_id', '>', 0)->get()->keyBy('quiz_id');

        return view('design_1.web.cvh_certificates.index', [
            'pageTitle' => 'My Certificates',
            'pageRobot' => 'noindex, nofollow',
            'rows' => $rows,
            'quizResults' => $quizResults,
            'quizIssued' => $quizIssued,
        ]);
    }

    /** Issue (first time) and download the PDF for a completed course. */
    public function download($webinarId)
    {
        $user = auth()->user();
        $course = Webinar::query()->where('id', $webinarId)->where('status', 'active')->first();
        abort_if(empty($course), 404);

        $certificate = DB::table('cvh_certificates')->where('user_id', $user->id)->where('webinar_id', $course->id)->where('quiz_id', 0)->first();

        if (empty($certificate)) {
            $enrolled = DB::table('sales')->where('buyer_id', $user->id)->where('webinar_id', $course->id)->whereNull('refund_at')->exists();
            $progress = $enrolled ? $course->getProgress(true, $user) : 0;
            // Certificates already earned on the old learn.cvh.africa site (imported by database/cvh/14_import_old_site.php)
            $earnedBefore = DB::table('certificates')->where('type', 'course')->where('student_id', $user->id)->where('webinar_id', $course->id)->exists();

            if (!$earnedBefore and (!$enrolled or $progress < 100)) {
                return redirect('/my-certificates')->with('cvh_cert_status', 'Complete all lessons of "' . $course->title . '" to unlock your certificate (current progress: ' . round($progress) . '%).');
            }

            if (trim((string)$user->full_name) === '') {
                return redirect('/my-certificates')->with('cvh_cert_status', 'Please add your full name in Dashboard > Settings first; it is printed on your certificate.');
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

    /**
     * Issue (first time) and download the certificate for a passed quiz that awards one. Replaces the template's
     * /panel/quizzes/results/{id}/showCertificate, which needs a paid outside image service (htmlcsstoimage.com).
     */
    public function downloadQuiz($quizResultId)
    {
        $user = auth()->user();

        $result = QuizzesResult::query()->where('id', $quizResultId)->where('user_id', $user->id)
            ->where('status', QuizzesResult::$passed)->with(['quiz.webinar'])->first();
        abort_if(empty($result) || empty($result->quiz) || !$result->quiz->certificate, 404);

        $quiz = $result->quiz;
        $courseId = (int)$quiz->webinar_id;
        $certificate = DB::table('cvh_certificates')->where('user_id', $user->id)->where('webinar_id', $courseId)->where('quiz_id', $quiz->id)->first();

        if (empty($certificate)) {
            if (trim((string)$user->full_name) === '') {
                return redirect('/my-certificates')->with('cvh_cert_status', 'Please add your full name in Dashboard > Settings first; it is printed on your certificate.');
            }

            do {
                $code = 'CVH-' . date('Y') . '-' . strtoupper(Str::random(8));
            } while (DB::table('cvh_certificates')->where('code', $code)->exists());

            DB::table('cvh_certificates')->insertOrIgnore([
                'code' => $code,
                'user_id' => $user->id,
                'webinar_id' => $courseId,
                'quiz_id' => $quiz->id,
                'student_name' => $user->full_name,
                'course_title' => $quiz->webinar ? $quiz->webinar->title : '',
                'quiz_title' => $quiz->title,
                'grade' => $result->user_grade . ($quiz->total_mark ? ' / ' . $quiz->total_mark : ''),
                'issued_at' => time(),
            ]);
            $certificate = DB::table('cvh_certificates')->where('user_id', $user->id)->where('webinar_id', $courseId)->where('quiz_id', $quiz->id)->first();
        }

        return $this->pdf($certificate)->download('CVH-Certificate-' . Str::slug($certificate->quiz_title ?: $certificate->course_title) . '.pdf');
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

        $template = $config['template'] ?? [];
        $background = !empty($template['background']) ? public_path(ltrim($template['background'], '/')) : null;
        if ($background and is_file($background)) {
            return $this->templatePdf($certificate, $template, $background, $qrSvg);
        }

        $html = view('design_1.web.cvh_certificates.pdf', [
            'certificate' => $certificate,
            'config' => $config,
            'verifyUrl' => $verifyUrl,
            'verifyDisplay' => preg_replace('#^https?://#', '', rtrim(config('app.url'), '/')) . '/certificates/verify',
            'qr' => 'data:image/svg+xml;base64,' . base64_encode($qrSvg),
            'img' => fn($path) => $path ? public_path(ltrim(rawurldecode($path), '/')) : null,
            'issuedOn' => dateTimeFormat($certificate->issued_at, 'j F Y', false),
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->setOption(['isRemoteEnabled' => false, 'chroot' => public_path(), 'defaultFont' => 'DejaVu Sans']);
    }

    /**
     * The old learn.cvh.africa design: text drawn on the background image the same way the old site did
     * (Intervention Image, Montserrat, left-aligned at position_x/position_y), placed on an A4 page with the QR code.
     */
    private function templatePdf($certificate, array $template, string $background, string $qrSvg)
    {
        $course = Webinar::query()->find($certificate->webinar_id);
        $body = strtr($template['body'], [
            '[student]' => $certificate->student_name,
            '[student_name]' => $certificate->student_name,
            // quiz certificates name the assessment (and its course) where the template says [course]
            '[course]' => !empty($certificate->quiz_id) ? trim($certificate->quiz_title . ($certificate->course_title ? ' (' . $certificate->course_title . ')' : '')) : $certificate->course_title,
            '[course_name]' => $certificate->course_title,
            '[grade]' => $certificate->grade ?? '',
            '[date]' => dateTimeFormat($certificate->issued_at, 'j F Y', false),
            '[certificate_id]' => $certificate->code,
            '[platform_name]' => getGeneralSettings('site_name') ?: config('cvh_certificate.issuer'),
            '[instructor_name]' => $course?->teacher?->full_name ?? '',
            '[duration]' => $course?->duration ?? '',
        ]);

        $image = Image::make($background);
        $image->text($body, (int)$template['position_x'], (int)$template['position_y'], function ($font) use ($template) {
            $font->file(public_path('assets/default/fonts/Montserrat-Medium.ttf'));
            $font->size((int)$template['font_size']);
            $font->color($template['text_color']);
            $font->align('left');
        });

        $landscape = $image->width() >= $image->height();
        [$pageW, $pageH] = $landscape ? [297, 210] : [210, 297];
        $box = $template['verify_box'] ?? ['right' => 4, 'bottom' => 4, 'size_mm' => 22];
        $qrSize = $box['size_mm'];
        $boxW = max($qrSize, 46);
        $boxLeft = $pageW * (1 - $box['right'] / 100) - $boxW;
        $boxTop = $pageH * (1 - $box['bottom'] / 100) - $qrSize - 8; // positioned from the top: dompdf mis-handles bottom-anchored boxes

        $html = '<!doctype html><html><head><meta charset="utf-8"><style>'
            . '@page{margin:0}body{margin:0;font-family:"DejaVu Sans",sans-serif}'
            . ".bg{position:absolute;top:0;left:0;width:{$pageW}mm;height:{$pageH}mm}"
            . ".verify{position:absolute;top:{$boxTop}mm;left:{$boxLeft}mm;width:{$boxW}mm;text-align:center;font-size:7pt;color:#333}"
            . ".verify img{width:{$qrSize}mm;height:{$qrSize}mm;background:#fff;padding:1mm}"
            . '</style></head><body>'
            . '<img class="bg" src="data:image/jpeg;base64,' . base64_encode((string)$image->encode('jpg', 90)) . '">'
            . '<div class="verify"><img src="data:image/svg+xml;base64,' . base64_encode($qrSvg) . '"><div><b>' . e($certificate->code) . '</b></div>'
            . '<div>' . e(preg_replace('#^https?://#', '', rtrim(config('app.url'), '/'))) . '/certificates/verify</div></div>'
            . '</body></html>';
        $image->destroy();

        return Pdf::loadHTML($html)
            ->setPaper('a4', $landscape ? 'landscape' : 'portrait')
            ->setOption(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans']);
    }
}
