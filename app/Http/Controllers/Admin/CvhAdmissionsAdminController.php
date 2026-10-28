<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Support\CvhAdmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CVH Africa: Admin → Users → Admissions. Admission settings (on/off, application form, fee, auto-approve),
 * the list of applicants with their form answers and payment, approve / reject, record an offline payment,
 * and a CSV export for registration reports.
 */
class CvhAdmissionsAdminController extends Controller
{
    private const STATUSES = ['applying' => 'Applying', 'under_review' => 'Under review', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    public function index(Request $request)
    {
        $this->authorize('admin_users_list');
        abort_unless(CvhAdmission::ready(), 404, 'Run database/cvh/16_admissions.php first.');

        // Pick up form submissions/payments made since the last visit
        DB::table('cvh_admissions')->where('status', 'applying')->get()->each(fn($row) => CvhAdmission::sync($row));

        $admissions = $this->query($request)->paginate(20)->withQueryString();

        $counts = DB::table('cvh_admissions')->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $fees = DB::table('cvh_admissions')->where('payment_status', 'paid')->sum('fee_amount');

        return view('admin.cvh_admissions.index', [
            'pageTitle' => 'Admissions',
            'admissions' => $admissions,
            'counts' => $counts,
            'feesCollected' => $fees,
            'statuses' => self::STATUSES,
            'settings' => CvhAdmission::settings(),
            'forms' => Form::query()->get(),
            'currency' => getDefaultCurrency(),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $this->authorize('admin_users_edit');

        $this->validate($request, [
            'fee' => 'nullable|numeric|min:0',
            'form_id' => 'nullable|exists:forms,id',
            'instructions' => 'nullable|string|max:2000',
        ]);

        CvhAdmission::saveSettings([
            'enabled' => $request->get('enabled') === 'on',
            'form_id' => $request->get('form_id') ?: null,
            'fee' => (float)$request->get('fee', 0),
            'auto_approve' => $request->get('auto_approve') === 'on',
            'instructions' => (string)$request->get('instructions', ''),
        ]);

        return back()->with('cvh_admission_admin_status', 'Admission settings saved.');
    }

    public function decide(Request $request, $id, $decision)
    {
        $this->authorize('admin_users_edit');

        $admission = DB::table('cvh_admissions')->where('id', $id)->first();
        abort_unless($admission, 404);

        DB::table('cvh_admissions')->where('id', $id)->update([
            'status' => $decision === 'approve' ? 'approved' : 'rejected',
            'admin_note' => trim((string)$request->get('note')) ?: null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => time(),
            'updated_at' => time(),
        ]);

        CvhAdmission::notify(DB::table('cvh_admissions')->where('id', $id)->first(), $decision === 'approve' ? 'approved' : 'rejected');

        return back()->with('cvh_admission_admin_status', $decision === 'approve' ? 'Applicant approved and notified.' : 'Applicant rejected and notified.');
    }

    /** Record a fee paid outside the site (cash, bank, M-PESA to the CVH number). */
    public function markPaid(Request $request, $id)
    {
        $this->authorize('admin_users_edit');

        $this->validate($request, ['reference' => 'required|string|max:100']);

        $updated = DB::table('cvh_admissions')->where('id', $id)->where('payment_status', 'unpaid')->update([
            'payment_status' => 'paid',
            'payment_method' => 'manual',
            'payment_reference' => 'MANUAL-' . trim($request->get('reference')) . '-' . $id,
            'fee_amount' => CvhAdmission::fee(),
            'fee_currency' => getDefaultCurrency(),
            'paid_at' => time(),
            'updated_at' => time(),
        ]);

        if ($updated) {
            CvhAdmission::sync(DB::table('cvh_admissions')->where('id', $id)->first());
        }

        return back()->with('cvh_admission_admin_status', $updated ? 'Payment recorded.' : 'This fee was already recorded.');
    }

    public function export(Request $request)
    {
        $this->authorize('admin_users_list');

        $rows = $this->query($request)->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Email', 'Mobile', 'Registered', 'Status', 'Application form', 'Fee', 'Currency', 'Payment', 'Payment method', 'Reference', 'Paid on', 'Reviewed on', 'Note']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->full_name, $row->email, $row->mobile, date('Y-m-d', (int)$row->user_created_at),
                    self::STATUSES[$row->status] ?? $row->status, $row->form_submission_id ? 'Submitted' : 'Not yet',
                    $row->fee_amount, $row->fee_currency, $row->payment_status, $row->payment_method, $row->payment_reference,
                    $row->paid_at ? date('Y-m-d', $row->paid_at) : '', $row->reviewed_at ? date('Y-m-d', $row->reviewed_at) : '', $row->admin_note,
                ]);
            }
            fclose($out);
        }, 'cvh-admissions-' . date('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function query(Request $request)
    {
        $search = trim((string)$request->get('search'));

        return DB::table('cvh_admissions as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->when($request->get('status'), fn($q, $status) => $q->where('a.status', $status))
            ->when($search !== '', fn($q) => $q->where(fn($q) => $q->where('u.full_name', 'like', "%{$search}%")->orWhere('u.email', 'like', "%{$search}%")->orWhere('a.payment_reference', 'like', "%{$search}%")))
            ->orderByRaw("field(a.status, 'under_review', 'applying', 'approved', 'rejected')")
            ->orderByDesc('a.created_at')
            ->select('a.*', 'u.full_name', 'u.email', 'u.mobile', 'u.created_at as user_created_at');
    }
}
