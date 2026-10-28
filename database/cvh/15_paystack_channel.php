<?php
/**
 * CVH Africa — register the Paystack payment gateway (the template's database only lists PayPal, PayU and Razorpay,
 * and the admin panel can edit gateways but not add them).
 *
 *   php database/cvh/15_paystack_channel.php                          add Paystack (switched OFF, no keys) if missing
 *   php database/cvh/15_paystack_channel.php --disable-demo-gateways  also switch off PayU and Razorpay (template demo
 *                                                                       gateways with no CVH account behind them)
 *
 * Then: Admin → Settings → Payment Channels → Paystack → enter the Public key, Secret key, payment URL
 * (https://api.paystack.co) and merchant email, choose the currency (KES for M-PESA), set Active and save.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$channel = DB::table('payment_channels')->where('class_name', 'Paystack')->first();

if ($channel) {
    echo "Paystack is already registered (id {$channel->id}, {$channel->status}).\n";
} else {
    $id = DB::table('payment_channels')->insertGetId([
        'title' => 'Paystack',
        'class_name' => 'Paystack',
        'status' => 'inactive',
        'image' => '/store/1/default_images/payment gateways/paystack.png',
        'credentials' => null,
        'currencies' => json_encode(['KES', 'NGN', 'GHS', 'ZAR', 'USD']),
        'created_at' => time(),
    ]);
    echo "Paystack registered (id {$id}, switched off). Add the keys in Admin → Settings → Payment Channels.\n";
}

if (in_array('--disable-demo-gateways', $argv, true)) {
    $count = DB::table('payment_channels')->whereIn('class_name', ['Payu', 'Razorpay'])->where('status', 'active')->update(['status' => 'inactive']);
    echo "Switched off {$count} demo gateway(s) (PayU, Razorpay).\n";
}
