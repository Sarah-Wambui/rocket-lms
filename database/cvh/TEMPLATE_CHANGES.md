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

Not rebuilt on purpose: course forum admin (`admin/webinars/forum/*`, replaced by Admin → Course Forum = `/admin/cvh-course-qa`), site forum admin (`admin/forums/*`, the public forum is `/community`), certificate templates (the PDF design is `config/cvh_certificate.php`), and the selling/payments features.
