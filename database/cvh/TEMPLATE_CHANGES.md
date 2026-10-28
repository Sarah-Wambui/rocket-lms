# CVH changes to Rocket LMS's own files

A Rocket LMS update can overwrite these. After updating, check each one and re-apply the marked change (search for `CVH`).

| File | Change |
|---|---|
| `bootstrap/app.php` | First line after `<?php`: loads `app/Support/finfo_polyfill.php`, a temporary stand-in for the PHP **fileinfo** extension (does nothing once the server has fileinfo). Safe to remove after the host installs `ea-php83-php-fileinfo`. |
| `routes/web.php` | Last lines: `require __DIR__ . '/cvh.php';` loads the CVH routes (innovations, community forum, certificates). |
| `app/Mixins/Panel/SidebarItems.php` | Learner dashboard menu: the **Certificates** entry points to `/my-certificates` and `/certificates/verify` instead of the plugin's certificate pages (block marked `// CVH:`). |
| `routes/custom_admin.php` | Homepage Editor admin routes (`/admin/cvh-homepage...`). This is the template's own file for custom admin routes. |
| `resources/views/admin/includes/sidebar/appearance.blade.php` | Admin menu: **Landing Builder → Homepage Editor** link (marked `{{-- CVH: ... --}}`) and the group highlights on `/admin/cvh-homepage*`. |
| `resources/views/admin/includes/sidebar/education.blade.php` | Admin menu: **Education → Course Forum** points to `/admin/cvh-course-qa` (the in-house Q&A overview) instead of the paid-plugin placeholder page (marked `{{-- CVH: ... --}}`). |
| `resources/views/design_1/web/courses/learning_page/includes/sidebar/index.blade.php` | Course learning page sidebar: certificate box below the progress bar ("Get your certificate" at 100%) and a **Course Q&A** link, both marked `{{-- CVH: ... --}}`. |
| `resources/views/design_1/web/courses/learning_page/includes/modals/learning_status_completed_modal.blade.php` | "Course completed" popup: the **Download certificate** button goes to the in-house `/my-certificates/{id}/download` instead of the plugin page `/panel/certificates/my-achievements/...`, marked `{{-- CVH: ... --}}`. |
| `resources/views/design_1/web/courses/learning_page/includes/contents/certificate/course_certificate.blade.php`, `.../sidebar/tabs/certificates.blade.php`, `.../top_header/course_tools.blade.php` | Learning page "Course Certificate" item, its sidebar download icon and the tools-menu "Certificates" link go to the in-house `/my-certificates` pages instead of the plugin `/panel/certificates/...` pages, marked `{{-- CVH: ... --}}`. |

Everything else CVH-specific lives in new files that updates don't touch: `routes/cvh.php`, `app/Http/Controllers/Web/Cvh*Controller.php`, `resources/views/design_1/web/cvh_*`, `resources/views/landingBuilder/front/components/cvh_partners`, `resources/views/admin/cvh_homepage`, `app/Http/Controllers/Admin/CvhHomepageController.php`, `app/Http/Controllers/Admin/CvhCourseQaAdminController.php`, `resources/views/admin/cvh_course_qa`, `config/cvh_certificate.php`, `config/cvh_landing_editor.php` and `database/cvh/`. Theme colours, homepage content and menus are stored in the database (scripts in `database/cvh/`).

## Payments (Paystack)

| File | Change |
|---|---|
| `app/PaymentChannels/Drivers/Paystack/Channel.php` | Amount sent in cents (x100; the template sent x1000 = 10x the price); reference stored on the order; verification now requires Paystack's transaction status `success`, the same reference, order, amount and currency (the template accepted any answer from Paystack, including abandoned payments); paid orders are never changed again; default API URL `https://api.paystack.co`. Marked `CVH:`. |
| `routes/web.php` | GET `/course/direct-payment`, `/products/direct-payment`, `/bundles/direct-payment` -> `CvhCheckoutRedirectController`, placed before each `/{slug}` route. The checkout page is the answer to those POST forms; when starting a payment failed the template returned "back" to that URL, which `/{slug}` treated as an unknown course and answered with "back" again: ERR_TOO_MANY_REDIRECTS. Now the learner returns to the course page with the error message. Marked `CVH:`. |
| `app/Http/Controllers/Web/PaymentController.php` | `paymentRequest`: the gateway's error (wrong keys, unsupported currency, ...) is written to `storage/logs/laravel.log` ("Payment start failed"). Marked `CVH:`. |
| `app/Http/Controllers/Web/PaymentController.php` | `paymentOrderAfterVerify`: the order is claimed atomically (`paying` -> `paid`) before the sale is recorded, so a repeated or simultaneous gateway callback cannot enrol/charge twice (applies to every gateway). Marked `CVH:`. |

`database/cvh/15_paystack_channel.php` registers the Paystack gateway (the database only listed PayPal, PayU and Razorpay).

## Live sessions

| File | Change |
|---|---|
| `lang/en/update.php` | `session_api_local` label "Custom (Local)" -> "Google Meet (paste link)": CVH runs live classes on Google Meet by pasting the meeting link into the session. |

## Quiz certificates and offline payments

| File | Change |
|---|---|
| `app/Http/Controllers/Web/CvhCertificatesController.php` | `downloadQuiz()`: in-house PDF for passed quizzes that award a certificate (the template's `/panel/quizzes/results/{id}/showCertificate` needs the paid htmlcsstoimage.com service); My Certificates lists them. `cvh_certificates.quiz_id` (0 = course) from `database/cvh/17_quiz_certificates.php`. |
| `design_1/panel/quizzes/holding/status/passed.blade.php`, `design_1/web/courses/learning_page/includes/contents/certificate/quiz_certificate.blade.php`, `.../contents/quiz/result_status.blade.php`, `.../sidebar/tabs/certificates.blade.php`, `design_1/web/courses/show/tabs/contents/quiz_certificate.blade.php` | Quiz-certificate links go to `/my-certificates/quiz/{resultId}/download`. Marked `CVH:`. |
| `app/Http/Controllers/Admin/OfflinePaymentController.php` | `approved()` claims the request atomically (waiting -> approved) so a second approve cannot credit the wallet twice; `reject()` only for waiting requests. Marked `CVH:`. |

## Admissions

New: `app/Support/CvhAdmission.php`, `app/Http/Controllers/Web/CvhAdmissionController.php` (/admission, fee via Paystack), `app/Http/Controllers/Admin/CvhAdmissionsAdminController.php` (Admin → Users → Admissions), views `design_1/web/cvh_admission`, `admin/cvh_admissions`, tables from `database/cvh/16_admissions.php`.

| Template file | Change |
|---|---|
| `app/Http/Middleware/CheckRestriction.php` | Calls `CvhAdmission::gate()`: learners who must complete admission are sent to /admission instead of enrolling, checking out or opening course content. Marked `CVH:`. |
| `app/Http/Middleware/PanelAuthenticate.php` | Same check for buying subscription plans in the dashboard. Marked `CVH:`. |
| `resources/views/admin/includes/sidebar/users.blade.php` | "Admissions" menu item. Marked `CVH:`. |

## Scheduled jobs (reminders)

| File | Change |
|---|---|
| `app/Console/Commands/CvhRunReminders.php` (new) | `php artisan cvh:reminders`, run hourly from cron: live-class, meeting and subscription reminders every N hours (N = Admin → Settings → General → Reminders), subscription renewal daily. Installments/gifts/events/abandoned carts are not scheduled. |
| `app/Http/Controllers/Web/CronJobsController.php` | `index()`: the public `/cron-jobs/{method}` URLs (open to anyone, could call `testSMS`, `makeUsersTableUsername`) now need `?key=` = `CRON_JOBS_KEY` from `.env` and never run those two. Marked `CVH:`. |

## Rebuilt admin pages (template shipped "This is a paid plugin!" placeholders)

Only the top-level page views were placeholders; the controllers, routes, sub-views and JS are the template's own and untouched. Each rebuilt view starts with a `{{-- CVH: rebuilt ... --}}` comment.

| View | Page |
|---|---|
| `resources/views/admin/forms/lists/index.blade.php` | Admin → Forms (list) |
| `resources/views/admin/forms/create/index.blade.php` | Admin → Forms → New / Edit (tabs: basic info, welcome, thank-you, inputs) |
| `resources/views/admin/forms/submissions/index.blade.php` | Admin → Forms → Submissions |
| `resources/views/admin/bundles/lists.blade.php`, `create.blade.php` | Admin → Bundles (list, create/edit with courses, price plans, FAQ) |
| `resources/views/admin/quizzes/lists.blade.php` | Admin → Quizzes |
| `resources/views/admin/certificates/lists.blade.php`, `course_certificates.blade.php` | Admin → Certificates (quiz certificates; completion certificates with the in-house verify code) |
| `resources/views/admin/enrollment/add_student_to_a_class.blade.php`, `history.blade.php` | Admin → Enrollments (add student, history) |
| `resources/views/admin/noticeboards/lists.blade.php`, `send.blade.php` | Admin → Noticeboards and Course noticeboards |
| `resources/views/admin/upcoming_courses/lists.blade.php`, `create/index.blade.php` | Admin → Upcoming courses |
| `resources/views/admin/waitlists/index.blade.php` | Admin → Waitlists |
| `resources/views/admin/attendances/history/index.blade.php`, `details/index.blade.php` | Admin → Attendances |
| `resources/views/admin/webinars/assignments/lists.blade.php` | Admin → Assignments |
| `resources/views/admin/webinars/course_statistics/index.blade.php` | Admin → Course statistics |
| `resources/views/admin/webinars/personal_notes/index.blade.php` | Admin → Course notes (needs the Course notes feature switched on) |
| `resources/views/admin/financial/subscribes/lists.blade.php`, `create/index.blade.php` | Admin → Financial → Subscriptions (plans; sales column counts plan purchases only) |
| `resources/views/admin/financial/offline_payments/lists.blade.php` | Admin → Financial → Offline Payments (waiting / history, approve, reject, export) |
| `resources/views/admin/rewards/history.blade.php`, `create.blade.php`, `settings.blade.php` | Admin → Reward Points (points per user, conditions, settings) |

Not rebuilt on purpose: course forum admin (`admin/webinars/forum/*`, replaced by Admin → Course Forum = `/admin/cvh-course-qa`), site forum admin (`admin/forums/*`, the public forum is `/community`), certificate templates (the PDF design is `config/cvh_certificate.php`), and the selling/payments features.
