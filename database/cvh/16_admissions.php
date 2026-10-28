<?php
/**
 * CVH Africa — tables for the admission workflow (application form -> admission fee -> admin approval).
 *
 *   php database/cvh/16_admissions.php
 *
 * Admission starts switched OFF; turn it on in Admin → Users → Admissions. Only learners who register after it is
 * switched on go through admission; existing learners keep full access.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

if (!Schema::hasTable('cvh_settings')) {
    Schema::create('cvh_settings', function (Blueprint $table) {
        $table->string('name', 64)->primary();
        $table->longText('value')->nullable();
        $table->unsignedInteger('updated_at')->nullable();
    });
    echo "Created cvh_settings.\n";
}

if (!Schema::hasTable('cvh_admissions')) {
    Schema::create('cvh_admissions', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('user_id')->unique();
        $table->string('status', 20)->default('applying')->index(); // applying | under_review | approved | rejected
        $table->unsignedInteger('form_submission_id')->nullable();
        $table->string('payment_status', 10)->default('unpaid'); // unpaid | paid | waived
        $table->decimal('fee_amount', 15, 2)->nullable();
        $table->string('fee_currency', 8)->nullable();
        $table->string('payment_reference', 100)->nullable()->unique();
        $table->string('payment_method', 30)->nullable(); // paystack | manual
        $table->unsignedInteger('paid_at')->nullable();
        $table->unsignedInteger('reviewed_by')->nullable();
        $table->unsignedInteger('reviewed_at')->nullable();
        $table->text('admin_note')->nullable();
        $table->unsignedInteger('created_at');
        $table->unsignedInteger('updated_at')->nullable();
    });
    echo "Created cvh_admissions.\n";
}

echo "Admission tables ready. Switch admission on in Admin → Users → Admissions.\n";
