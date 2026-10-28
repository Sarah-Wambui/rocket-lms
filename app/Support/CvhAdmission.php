<?php

namespace App\Support;

use App\Models\Form;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CVH Africa — admission workflow: a new learner fills the application form (a Form from Admin → Forms), pays the
 * admission fee (Paystack, or marked paid by an admin), and is approved by an admin before they can enrol in or open
 * courses. Learners who registered before admission was switched on are not affected.
 * Tables: cvh_admissions, cvh_settings (database/cvh/16_admissions.php).
 */
class CvhAdmission
{
    public const DEFAULTS = [
        'enabled' => false,
        'enabled_at' => null,      // learners registered before this moment are not asked to apply
        'form_id' => null,         // Admin → Forms; null = no application form step
        'fee' => 0,                // in the site's default currency; 0 = no fee step
        'auto_approve' => false,   // approve as soon as form + fee are done
        'instructions' => '',
    ];

    /** Paths a learner cannot use until admitted (enrolment, checkout and course content). */
    private const GATED_PATHS = [
        'course/learning/*', 'course/*/free', 'course/direct-payment', 'course/*/learningStatus',
        'bundles/*/free', 'bundles/direct-payment',
        'cart/store', 'cart/checkout', 'payments/payment-request',
        'subscribes/apply/*', 'panel/financial/pay-subscribes',
    ];

    private static ?array $settings = null;

    public static function ready(): bool
    {
        static $ready = null;
        return $ready ??= Schema::hasTable('cvh_admissions') && Schema::hasTable('cvh_settings');
    }

    public static function settings(): array
    {
        if (self::$settings === null) {
            $stored = self::ready() ? DB::table('cvh_settings')->where('name', 'admission')->value('value') : null;
            self::$settings = array_merge(self::DEFAULTS, json_decode((string)$stored, true) ?: []);
        }
        return self::$settings;
    }

    public static function saveSettings(array $values): void
    {
        $settings = array_merge(self::settings(), $values);
        if ($settings['enabled'] && empty($settings['enabled_at'])) {
            $settings['enabled_at'] = time();
        }
        DB::table('cvh_settings')->updateOrInsert(['name' => 'admission'], ['value' => json_encode($settings), 'updated_at' => time()]);
        self::$settings = $settings;
    }

    public static function enabled(): bool
    {
        return self::ready() && !empty(self::settings()['enabled']);
    }

    public static function form(): ?Form
    {
        $formId = self::settings()['form_id'];
        return $formId ? Form::query()->find($formId) : null;
    }

    public static function fee(): float
    {
        return max(0, (float)self::settings()['fee']);
    }

    /** Does admission apply to this account? (students who registered after admission was switched on) */
    public static function appliesTo(?User $user): bool
    {
        return self::enabled() && $user
            && $user->role_name === 'user'
            && (int)$user->created_at >= (int)self::settings()['enabled_at'];
    }

    public static function isAdmitted(?User $user): bool
    {
        if (!self::appliesTo($user)) {
            return true;
        }
        return DB::table('cvh_admissions')->where('user_id', $user->id)->where('status', 'approved')->exists();
    }

    /** The learner's admission row, created on first use and kept in step with the form submission and payment. */
    public static function record(User $user): object
    {
        $row = DB::table('cvh_admissions')->where('user_id', $user->id)->first();
        if (!$row) {
            DB::table('cvh_admissions')->insertOrIgnore(['user_id' => $user->id, 'status' => 'applying', 'created_at' => time()]);
            $row = DB::table('cvh_admissions')->where('user_id', $user->id)->first();
        }
        return self::sync($row);
    }

    /** Pick up a new form submission, waive the fee when there is none, and move to review/approval when ready. */
    public static function sync(object $row): object
    {
        $changes = [];
        $form = self::form();

        if ($form && empty($row->form_submission_id)) {
            $submissionId = DB::table('form_submissions')->where('form_id', $form->id)->where('user_id', $row->user_id)->orderByDesc('id')->value('id');
            if ($submissionId) {
                $changes['form_submission_id'] = $submissionId;
            }
        }

        if ($row->payment_status === 'unpaid' && self::fee() <= 0) {
            $changes['payment_status'] = 'waived';
        }

        $formDone = !$form || !empty($changes['form_submission_id'] ?? $row->form_submission_id);
        $feeDone = in_array($changes['payment_status'] ?? $row->payment_status, ['paid', 'waived'], true);

        if ($row->status === 'applying' && $formDone && $feeDone) {
            $changes['status'] = self::settings()['auto_approve'] ? 'approved' : 'under_review';
            if ($changes['status'] === 'approved') {
                $changes['reviewed_at'] = time();
            }
        }

        if ($changes) {
            DB::table('cvh_admissions')->where('id', $row->id)->update($changes + ['updated_at' => time()]);
            $row = DB::table('cvh_admissions')->where('id', $row->id)->first();
            if (($changes['status'] ?? null) === 'approved') {
                self::notify($row, 'approved');
            }
        }

        return $row;
    }

    /** Called from the web/panel middleware: send learners who are not admitted yet to /admission. */
    public static function gate(Request $request)
    {
        if (!self::enabled() || !auth()->check() || !$request->is(...self::GATED_PATHS)) {
            return null;
        }
        if (self::isAdmitted(auth()->user())) {
            return null;
        }

        $message = 'Please complete your admission first. You can enrol in courses once it is approved.';
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['code' => 403, 'message' => $message, 'redirect' => url('/admission')], 403);
        }
        return redirect('/admission')->with('cvh_admission_status', $message);
    }

    public static function notify(object $row, string $decision): void
    {
        $user = User::query()->find($row->user_id);
        if (!$user) {
            return;
        }

        $title = $decision === 'approved' ? 'Your admission has been approved' : 'Update on your admission';
        $message = $decision === 'approved'
            ? '<p>Welcome to CVH Africa! Your admission has been approved, so you can now enrol in courses.</p>'
            : '<p>Your admission application was not approved.' . (!empty($row->admin_note) ? ' ' . e($row->admin_note) : '') . '</p>';

        DB::table('notifications')->insert([
            'user_id' => $user->id, 'title' => $title, 'message' => $message,
            'sender' => 'system', 'type' => 'single', 'created_at' => time(),
        ]);

        // Same rule as the template's notifications: e-mail only on the live (production) site
        if (env('APP_ENV') == 'production' && !empty($user->email)) {
            try {
                \Mail::to($user->email)->send(new \App\Mail\SendNotifications(['title' => $title, 'message' => $message]));
            } catch (\Throwable $exception) {
                // mail problems must not block the admin's decision
            }
        }
    }
}
