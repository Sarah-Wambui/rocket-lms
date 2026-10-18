<?php
/**
 * CVH Africa — keep "Learn + rotating word" (e.g. "Learn Food Safety") on one line in the homepage hero.
 * The template sets the headline at 64px in a narrow column, so longer words wrapped inside their box.
 * Sizes below were measured so the longest word ("Learn Agribusiness") fits each breakpoint's column.
 * Stored in the theme's custom CSS (themes.contents.custom_css), between CVH markers, so it is re-runnable.
 * Run from the project root:  php database/cvh/05_hero_headline_one_line.php
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$css = <<<'CSS'
/* CVH:hero-headline:start */
.two-columns-hero-section__content h1 > div { flex-wrap: nowrap; white-space: nowrap; }
.two-columns-hero-section__content h1,
.two-columns-hero-section__content h1 > div { font-size: min(8vw, 44px) !important; }
@media (min-width: 576px) { .two-columns-hero-section__content h1, .two-columns-hero-section__content h1 > div { font-size: 48px !important; } }
@media (min-width: 768px) { .two-columns-hero-section__content h1, .two-columns-hero-section__content h1 > div { font-size: 56px !important; } }
@media (min-width: 992px) { .two-columns-hero-section__content h1, .two-columns-hero-section__content h1 > div { font-size: 38px !important; } }
@media (min-width: 1200px) { .two-columns-hero-section__content h1, .two-columns-hero-section__content h1 > div { font-size: 46px !important; } }
@media (min-width: 1400px) { .two-columns-hero-section__content h1, .two-columns-hero-section__content h1 > div { font-size: 52px !important; } }
/* CVH:hero-headline:end */
CSS;

$theme = DB::table('themes')->where('is_default', 1)->first();
$contents = json_decode($theme->contents, true);
$existing = $contents['custom_css'] ?? '';
$existing = preg_replace('#/\* CVH:hero-headline:start \*/.*?/\* CVH:hero-headline:end \*/\s*#s', '', $existing);
$contents['custom_css'] = trim($existing . "\n" . $css);
DB::table('themes')->where('id', $theme->id)->update(['contents' => json_encode($contents)]);

echo "Hero headline CSS applied.\n";
