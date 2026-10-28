<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

/**
 * CVH Africa: GET on the template's POST-only checkout URLs (/course/direct-payment, /cart/checkout, ...).
 * The checkout page is the response to those forms, so when starting a payment fails the template returns "back"
 * to that URL with GET, and its fallback answered with another "back": an endless redirect loop. This sends the
 * learner to the cart instead and keeps the error message for it.
 */
class CvhCheckoutRedirectController extends Controller
{
    public function __invoke()
    {
        session()->reflash();

        // The last page the learner viewed (normally the course page with the Buy now button), else the cart
        $previous = (string)session()->previousUrl();
        if ($previous === '' || preg_match('#/(direct-payment|checkout|payments/)#', $previous)) {
            $previous = url('/cart');
        }

        return redirect($previous);
    }
}
