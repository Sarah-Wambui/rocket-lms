<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PaymentChannel;
use App\Support\CvhAdmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * CVH Africa: the learner's "Your admission" page (/admission) and the admission-fee payment through Paystack.
 * The fee uses the Paystack keys from Admin → Settings → Financial → Payment Channels → Paystack, and is accepted only
 * when Paystack reports the transaction as successful for this admission, with the full amount in the right currency.
 */
class CvhAdmissionController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (!CvhAdmission::appliesTo($user)) {
            return view('design_1.web.cvh_admission.index', [
                'pageTitle' => 'Admission',
                'pageRobot' => 'noindex, nofollow',
                'notRequired' => true,
            ]);
        }

        $admission = CvhAdmission::record($user);
        $settings = CvhAdmission::settings();

        return view('design_1.web.cvh_admission.index', [
            'pageTitle' => 'Admission',
            'pageRobot' => 'noindex, nofollow',
            'notRequired' => false,
            'admission' => $admission,
            'form' => CvhAdmission::form(),
            'fee' => CvhAdmission::fee(),
            'currency' => getDefaultCurrency(),
            'instructions' => $settings['instructions'],
            'onlinePayment' => !empty($this->paystack()),
        ]);
    }

    public function pay()
    {
        $user = auth()->user();
        abort_unless(CvhAdmission::appliesTo($user), 404);

        $admission = CvhAdmission::record($user);
        $fee = CvhAdmission::fee();
        $channel = $this->paystack();

        if ($admission->payment_status !== 'unpaid' || $fee <= 0) {
            return redirect('/admission');
        }
        if (!$channel) {
            return redirect('/admission')->with('cvh_admission_status', 'Online payment is not available yet. Please contact CVH Africa to pay your admission fee.');
        }

        $currency = getDefaultCurrency();
        $reference = 'CVH-ADM-' . $admission->id . '-' . strtoupper(Str::random(10));

        DB::table('cvh_admissions')->where('id', $admission->id)->update([
            'payment_reference' => $reference, 'fee_amount' => $fee, 'fee_currency' => $currency, 'updated_at' => time(),
        ]);

        $response = Http::withToken($channel['secretKey'])->acceptJson()->timeout(30)->post($channel['url'] . '/transaction/initialize', [
            'email' => $user->email,
            'amount' => (int)round($fee * 100), // Paystack expects the amount in cents
            'currency' => $currency,
            'reference' => $reference,
            'callback_url' => url('/admission/payment/verify'),
            'metadata' => ['cvh_admission_id' => $admission->id, 'user_id' => $user->id],
        ]);

        $authorizationUrl = $response->successful() ? $response->json('data.authorization_url') : null;
        if (!$authorizationUrl) {
            return redirect('/admission')->with('cvh_admission_status', 'We could not start the payment. Please try again in a moment.');
        }

        return redirect()->away($authorizationUrl);
    }

    public function verify(Request $request)
    {
        $reference = (string)($request->query('reference') ?? $request->query('trxref'));
        $admission = $reference !== '' ? DB::table('cvh_admissions')->where('payment_reference', $reference)->first() : null;
        $channel = $this->paystack();

        if (!$admission || !$channel) {
            return redirect('/admission')->with('cvh_admission_status', 'We could not find that payment.');
        }

        if ($admission->payment_status === 'unpaid') {
            $response = Http::withToken($channel['secretKey'])->acceptJson()->timeout(30)
                ->get($channel['url'] . '/transaction/verify/' . rawurlencode($reference));
            $data = $response->successful() ? ($response->json('data') ?? []) : [];

            $succeeded = ($data['status'] ?? null) === 'success'
                && ($data['reference'] ?? null) === $reference
                && (int)($data['metadata']['cvh_admission_id'] ?? 0) === (int)$admission->id
                && strtoupper($data['currency'] ?? '') === strtoupper((string)$admission->fee_currency)
                && (int)($data['amount'] ?? -1) >= (int)round($admission->fee_amount * 100);

            if (!$succeeded) {
                return redirect('/admission')->with('cvh_admission_status', 'The payment was not completed. You can try again.');
            }

            // Only the first confirmation is recorded (repeated callbacks change nothing)
            DB::table('cvh_admissions')->where('id', $admission->id)->where('payment_status', 'unpaid')->update([
                'payment_status' => 'paid', 'payment_method' => 'paystack', 'paid_at' => time(), 'updated_at' => time(),
            ]);
        }

        CvhAdmission::sync(DB::table('cvh_admissions')->where('id', $admission->id)->first());

        return redirect('/admission')->with('cvh_admission_status', 'Thank you, your admission fee has been received.');
    }

    /** Paystack keys from the active Paystack payment channel, or null when it is not set up. */
    private function paystack(): ?array
    {
        $channel = PaymentChannel::query()->where('class_name', 'Paystack')->where('status', 'active')->first();
        $credentials = $channel ? (is_array($channel->credentials) ? $channel->credentials : json_decode((string)$channel->credentials, true)) : null;

        if (empty($credentials['secretKey'])) {
            return null;
        }

        return [
            'secretKey' => $credentials['secretKey'],
            'url' => rtrim($credentials['paymentUrl'] ?? '', '/') ?: 'https://api.paystack.co',
        ];
    }
}
