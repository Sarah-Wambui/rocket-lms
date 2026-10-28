<?php

namespace App\PaymentChannels\Drivers\Paystack;

use App\Models\Order;
use App\Models\PaymentChannel;
use App\PaymentChannels\BasePaymentChannel;
use App\PaymentChannels\IChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Unicodeveloper\Paystack\Paystack;

class Channel extends BasePaymentChannel implements IChannel
{
    protected $currency;
    protected $test_mode;
    protected $publicKey;
    protected $secretKey;
    protected $paymentUrl;
    protected $merchantEmail;


    protected array $credentialItems = [
        'publicKey',
        'secretKey',
        'paymentUrl',
        'merchantEmail',
    ];

    /**
     * Channel constructor.
     * @param PaymentChannel $paymentChannel
     */
    public function __construct(PaymentChannel $paymentChannel)
    {
        $this->currency = currency();
        $this->setCredentialItems($paymentChannel);
    }

    private function handleConfigs()
    {
        $this->paymentUrl = $this->paymentUrl ?: 'https://api.paystack.co'; // CVH: Paystack's API address when the admin field is left empty

        \Config::set('paystack.paymentUrl', $this->paymentUrl);
        \Config::set('paystack.secretKey', $this->secretKey);
        \Config::set('paystack.merchantEmail', $this->merchantEmail);
    }

    public function paymentRequest(Order $order)
    {
        $this->handleConfigs();

        $payStack = new Paystack();
        $reference = $payStack->genTranxRef();

        // CVH: keep the reference on the order so the callback can be matched to it (and traced in reports)
        $order->update(['reference_id' => $reference]);

        $payStack->getAuthorizationResponse([
            "amount" => $this->amountInSubunits($order), // CVH: Paystack expects kobo/cents (x100); the template sent x1000
            "reference" => $reference,
            "email" => $order->user->email,
            "callback_url" => $this->makeCallbackUrl($order),
            'metadata' => json_encode(['transaction' => $order->id]),
            'currency' => $this->currency
        ]);

        return $payStack->url;
    }

    /** Order total in the smallest currency unit (cents/kobo), as Paystack expects. */
    private function amountInSubunits(Order $order): int
    {
        return (int)round($this->makeAmountByCurrency($order->total_amount, $this->currency) * 100);
    }

    private function makeCallbackUrl(Order $order)
    {
        $callbackUrl = route('payment_verify', [
            'gateway' => 'Paystack'
        ]);

        return $callbackUrl;
    }

    /**
     * CVH: the template marked an order paid whenever Paystack *answered* the verify call ("Verification successful"),
     * which is also the answer for abandoned or failed payments. Now the order is paid only when the transaction itself
     * succeeded, belongs to this order, and the amount and currency match. A paid order is never changed again.
     */
    public function verify(Request $request)
    {
        $this->handleConfigs();

        $reference = $request->query('reference') ?? $request->query('trxref');
        if (empty($reference)) {
            return null;
        }

        // Server-side check with Paystack (the library's helper only reads ?trxref= and hides the response)
        $response = Http::withToken($this->secretKey)->acceptJson()->timeout(30)
            ->get(rtrim($this->paymentUrl ?: 'https://api.paystack.co', '/') . '/transaction/verify/' . rawurlencode($reference));
        $data = $response->successful() ? ($response->json('data') ?? []) : [];

        $metadata = $data['metadata'] ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        $order = Order::where('reference_id', $reference)->first()
            ?? Order::where('id', $metadata['transaction'] ?? 0)->whereNull('reference_id')->first(); // orders started before this fix

        if (empty($order)) {
            return null;
        }

        if ($order->status == Order::$paid) {
            return $order; // already processed: nothing to do (repeated callback)
        }

        $succeeded = ($data['status'] ?? null) === 'success'
            && ($data['reference'] ?? null) === $reference
            && (int)($metadata['transaction'] ?? 0) === (int)$order->id
            && strtoupper($data['currency'] ?? '') === strtoupper($this->currency)
            && (int)($data['amount'] ?? -1) >= $this->amountInSubunits($order);

        $order->update([
            'status' => $succeeded ? Order::$paying : Order::$fail,
            'reference_id' => $reference,
        ]);

        return $order;
    }
}
