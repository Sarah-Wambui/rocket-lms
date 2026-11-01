<?php

namespace App\Http\Middleware;

use App\Models\AiContentTemplate;
use App\Models\Setting;
use Closure;
use Illuminate\Support\Facades\Auth;

class PanelAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!auth()->user() and !empty(apiAuth())) {
            auth()->setUser(apiAuth());
        }

        if (auth()->check() and !auth()->user()->isAdmin()) {

            $referralSettings = getReferralSettings();
            view()->share('referralSettings', $referralSettings);

            $aiContentTemplates = AiContentTemplate::query()->where('enable', true)->get();
            view()->share('aiContentTemplates', $aiContentTemplates);

            view()->share('panelNavbarLinks', $this->getNavbarLinks());

            // CVH: learners who still have to complete admission cannot buy subscription plans
            if ($admissionRedirect = \App\Support\CvhAdmission::gate($request)) {
                return $admissionRedirect;
            }

            return $next($request);
        }

        // CVH: admins may join a live session (e.g. to host and record it); the rest of the panel stays for users
        if (auth()->check() and $request->is('panel/sessions/*/join')) {
            return $next($request);
        }

        return redirect('/login');
    }

    private function getNavbarLinks()
    {
        return Setting::where('name', Setting::$navbarLinkName)->first();
    }
}
