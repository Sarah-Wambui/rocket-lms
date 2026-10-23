# CVH changes to Rocket LMS's own files

A Rocket LMS update can overwrite these. After updating, check each one and re-apply the marked change (search for `CVH`).

| File | Change |
|---|---|
| `routes/web.php` | Last lines: `require __DIR__ . '/cvh.php';` loads the CVH routes (innovations, community forum, certificates). |
| `app/Mixins/Panel/SidebarItems.php` | Learner dashboard menu: the **Certificates** entry points to `/my-certificates` and `/certificates/verify` instead of the plugin's certificate pages (block marked `// CVH:`). |
| `resources/views/design_1/web/courses/learning_page/includes/sidebar/index.blade.php` | Course learning page: certificate box below the progress bar ("Get your certificate" at 100%), marked `{{-- CVH: ... --}}`. |

Everything else CVH-specific lives in new files that updates don't touch: `routes/cvh.php`, `app/Http/Controllers/Web/Cvh*Controller.php`, `resources/views/design_1/web/cvh_*`, `resources/views/landingBuilder/front/components/cvh_partners`, `config/cvh_certificate.php` and `database/cvh/`. Theme colours, homepage content and menus are stored in the database (scripts in `database/cvh/`).
