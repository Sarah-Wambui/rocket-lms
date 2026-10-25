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

Everything else CVH-specific lives in new files that updates don't touch: `routes/cvh.php`, `app/Http/Controllers/Web/Cvh*Controller.php`, `resources/views/design_1/web/cvh_*`, `resources/views/landingBuilder/front/components/cvh_partners`, `resources/views/admin/cvh_homepage`, `app/Http/Controllers/Admin/CvhHomepageController.php`, `app/Http/Controllers/Admin/CvhCourseQaAdminController.php`, `resources/views/admin/cvh_course_qa`, `config/cvh_certificate.php`, `config/cvh_landing_editor.php` and `database/cvh/`. Theme colours, homepage content and menus are stored in the database (scripts in `database/cvh/`).
