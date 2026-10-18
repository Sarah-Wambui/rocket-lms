<?php
/**
 * CVH Africa — make the 8-item main menu fit on laptop screens.
 * The template gives the menu a fixed 5/12 column (with max-width) and 32px gaps, so at 1200–1399px the
 * last links slid under the "Explore Courses" button. On desktop widths the menu now takes the remaining
 * row width; below 1400px the gaps tighten and the "Categories" button hides; at 992–1199px Blog and About
 * also hide (they stay in the footer). Stored in the theme's custom CSS between CVH markers (re-runnable).
 * Run from the project root:  php database/cvh/07_header_menu_fit.php
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$css = <<<'CSS'
/* CVH:header-menu:start */
@media (min-width: 992px) {
  .theme-header-1__main .row > div:nth-child(2),
  .theme-header-1__main .row > div:nth-child(4) { flex: 0 0 auto; width: auto; max-width: none; }
  .theme-header-1__main .row > div:nth-child(3) { flex: 1 1 0; width: auto; max-width: none; min-width: 0; }
  .theme-header-1__main .row > div:nth-child(3) > div { justify-content: center; white-space: nowrap; gap: 24px !important; }
}
@media (min-width: 992px) and (max-width: 1399.98px) {
  .theme-header-1__main .row > div:nth-child(2) { display: none !important; }
  .theme-header-1__main .row > div:nth-child(3) > div { gap: 16px !important; }
}
@media (min-width: 992px) and (max-width: 1199.98px) {
  .theme-header-1__main .row > div:nth-child(3) > div { gap: 14px !important; font-size: 14px; }
  .theme-header-1__main .row > div:nth-child(3) > div > a:nth-child(n+7) { display: none; }
}
/* CVH:header-menu:end */
CSS;

$theme = DB::table('themes')->where('is_default', 1)->first();
$contents = json_decode($theme->contents, true);
$existing = preg_replace('#/\* CVH:header-menu:start \*/.*?/\* CVH:header-menu:end \*/\s*#s', '', $contents['custom_css'] ?? '');
$contents['custom_css'] = trim($existing . "\n" . $css);
DB::table('themes')->where('id', $theme->id)->update(['contents' => json_encode($contents)]);

echo "Header menu CSS applied.\n";
