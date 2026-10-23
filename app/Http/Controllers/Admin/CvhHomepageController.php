<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mixins\LandingBuilder\StatisticComponentFrontMixins;
use App\Models\TrendCategory;
use App\Models\Webinar;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * CVH Africa: in-house Homepage Editor (admin: Appearance > Homepage Editor).
 * Edits the sections of the active theme's home landing page (landing_components + landing_component_translations),
 * the same data the homepage renders. Section fields are defined in config/cvh_landing_editor.php.
 */
class CvhHomepageController extends Controller
{
    private const UPLOAD_DIR = 'store/cvh/landing/uploads';

    public function index()
    {
        $this->authorize('admin_themes_create');

        $schema = config('cvh_landing_editor');
        $sections = $this->sectionsQuery()->get();

        return view('admin.cvh_homepage.index', [
            'pageTitle' => 'Homepage Editor',
            'sections' => $sections,
            'schema' => $schema,
            'addableTypes' => collect($schema)->map(fn($s) => $s['title'])->all(),
        ]);
    }

    public function edit($id)
    {
        $this->authorize('admin_themes_create');

        $section = $this->sectionsQuery()->where('lc.id', $id)->first();
        abort_if(empty($section), 404);

        $schema = config("cvh_landing_editor.{$section->type}");
        abort_if(empty($schema), 404, 'This section type has no editor fields.');

        return view('admin.cvh_homepage.edit', [
            'pageTitle' => 'Edit section: ' . $schema['title'],
            'section' => $section,
            'schema' => $schema,
            'content' => json_decode($section->content ?? '{}', true) ?: [],
            'sources' => $this->sources(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('admin_themes_create');

        $section = $this->sectionsQuery()->where('lc.id', $id)->first();
        abort_if(empty($section), 404);
        $schema = config("cvh_landing_editor.{$section->type}");
        abort_if(empty($schema), 404);

        $request->validate(['f.*' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif|max:5120']);

        $old = json_decode($section->content ?? '{}', true) ?: [];
        $input = $request->input('c', []);
        $uploads = $this->storeUploads($request->file('f', []));

        $content = $this->buildFields($schema['fields'], $old, is_array($input) ? $input : [], $uploads, '');

        DB::table('landing_component_translations')->updateOrInsert(
            ['landing_component_id' => $section->id, 'locale' => 'en'],
            ['content' => json_encode($content)]
        );
        $this->clearCaches();

        return redirect(getAdminPanelUrl("/cvh-homepage/{$section->id}/edit"))->with('cvh_homepage_status', 'Section saved. Open the homepage to see the change.');
    }

    public function toggle($id)
    {
        $this->authorize('admin_themes_create');
        $section = DB::table('landing_components')->where('id', $id)->where('landing_id', $this->landingId())->first();
        abort_if(empty($section), 404);

        DB::table('landing_components')->where('id', $id)->update(['enable' => $section->enable ? 0 : 1]);
        $this->clearCaches();

        return back()->with('cvh_homepage_status', $section->enable ? 'Section hidden.' : 'Section shown.');
    }

    public function move($id, $direction)
    {
        $this->authorize('admin_themes_create');
        $landingId = $this->landingId();
        $section = DB::table('landing_components')->where('id', $id)->where('landing_id', $landingId)->first();
        abort_if(empty($section), 404);

        $neighbour = DB::table('landing_components')->where('landing_id', $landingId)
            ->where('order', $direction === 'up' ? '<' : '>', $section->order)
            ->orderBy('order', $direction === 'up' ? 'desc' : 'asc')->first();

        if ($neighbour) {
            DB::transaction(function () use ($section, $neighbour) {
                DB::table('landing_components')->where('id', $section->id)->update(['order' => $neighbour->order]);
                DB::table('landing_components')->where('id', $neighbour->id)->update(['order' => $section->order]);
            });
            $this->clearCaches();
        }

        return back();
    }

    public function store(Request $request)
    {
        $this->authorize('admin_themes_create');
        $schema = config('cvh_landing_editor');
        $data = $request->validate(['type' => 'required|string|in:' . implode(',', array_keys($schema))]);

        $typeId = DB::table('landing_builder_components')->where('name', $data['type'])->value('id');
        abort_if(empty($typeId), 422, 'This section type is not installed.');

        $landingId = $this->landingId();
        $id = DB::table('landing_components')->insertGetId([
            'landing_id' => $landingId, 'component_id' => $typeId, 'preview' => null, 'enable' => 0,
            'order' => (int)DB::table('landing_components')->where('landing_id', $landingId)->max('order') + 1,
        ]);
        DB::table('landing_component_translations')->insert([
            'landing_component_id' => $id, 'locale' => 'en',
            'content' => json_encode($data['type'] === 'vertical_spacer' ? ['space_number' => '80'] : []),
        ]);

        return redirect(getAdminPanelUrl("/cvh-homepage/{$id}/edit"))
            ->with('cvh_homepage_status', 'Section added at the bottom of the homepage (hidden). Fill it in, then show it from the sections list.');
    }

    public function destroy($id)
    {
        $this->authorize('admin_themes_create');
        $section = DB::table('landing_components')->where('id', $id)->where('landing_id', $this->landingId())->first();
        abort_if(empty($section), 404);

        DB::transaction(function () use ($id) {
            DB::table('landing_component_translations')->where('landing_component_id', $id)->delete();
            DB::table('landing_components')->where('id', $id)->delete();
        });
        $this->clearCaches();

        return redirect(getAdminPanelUrl('/cvh-homepage'))->with('cvh_homepage_status', 'Section deleted.');
    }

    /* ---------- helpers ---------- */

    private function landingId(): int
    {
        return (int)DB::table('themes')->where('is_default', 1)->value('home_landing_id');
    }

    private function sectionsQuery()
    {
        return DB::table('landing_components as lc')
            ->join('landing_builder_components as b', 'b.id', '=', 'lc.component_id')
            ->leftJoin('landing_component_translations as t', function ($j) {
                $j->on('t.landing_component_id', '=', 'lc.id')->where('t.locale', '=', 'en');
            })
            ->where('lc.landing_id', $this->landingId())
            ->orderBy('lc.order')
            ->select('lc.id', 'lc.order', 'lc.enable', 'b.name as type', 't.content');
    }

    /** Rebuild a content array from the schema: known fields come from the form, unknown keys are kept. */
    private function buildFields(array $fields, array $old, array $input, array $uploads, string $path): array
    {
        $out = $old;

        foreach ($fields as $key => $field) {
            $fieldPath = ltrim("{$path}.{$key}", '.');
            $value = $input[$key] ?? null;

            switch ($field['type']) {
                case 'group':
                    $out[$key] = $this->buildFields($field['fields'], is_array($old[$key] ?? null) ? $old[$key] : [], is_array($value) ? $value : [], $uploads, $fieldPath);
                    break;

                case 'repeater':
                    $items = [];
                    foreach ((is_array($value) ? $value : []) as $itemKey => $itemInput) {
                        if ($itemKey === 'record' or $itemKey === '__KEY__' or !is_array($itemInput)) continue;
                        $oldItem = is_array($old[$key][$itemKey] ?? null) ? $old[$key][$itemKey] : [];
                        $built = $this->buildFields($field['fields'], $oldItem, $itemInput, $uploads, "{$fieldPath}.{$itemKey}");
                        $items[$this->itemKey($itemKey)] = $built;
                    }
                    if (isset($old[$key]['record'])) $items['record'] = $old[$key]['record'];
                    $out[$key] = $items;
                    break;

                case 'list':
                    $items = [];
                    foreach ((is_array($value) ? $value : []) as $itemKey => $itemValue) {
                        if ($itemKey === '__KEY__') continue;
                        $itemValue = $uploads["{$fieldPath}.{$itemKey}"] ?? $this->clean($itemValue);
                        if ($itemValue === null or $itemValue === '') continue;
                        $items[$this->itemKey($itemKey)] = $itemValue;
                    }
                    $out[$key] = $items;
                    break;

                case 'toggle':
                    $out[$key] = !empty($value) ? 'on' : null;
                    break;

                case 'image':
                    $out[$key] = $uploads[$fieldPath] ?? $this->clean($value);
                    break;

                default:
                    $out[$key] = $field['type'] === 'textarea' ? $this->clean($value, true) : $this->clean($value);
            }
        }

        return $out;
    }

    private function clean($value, bool $multiline = false)
    {
        if (is_array($value)) return null;
        $value = trim(strip_tags((string)$value));
        if (!$multiline) $value = preg_replace('/\s+/', ' ', $value);
        return $value === '' ? null : $value;
    }

    /** New items arrive with temporary keys ("new_..."); give them stable random keys like the template uses. */
    private function itemKey($key): string
    {
        return str_starts_with((string)$key, 'new_') ? 'cvh' . Str::random(6) : (string)$key;
    }

    /** Save uploaded images; returns [fieldPath => public URL]. */
    private function storeUploads(array $files): array
    {
        $saved = [];
        $dir = self::UPLOAD_DIR . '/' . date('Ym');
        foreach ($files as $fieldPath => $file) {
            if (!$file or !$file->isValid()) continue;
            $name = Str::random(12) . '.' . strtolower($file->getClientOriginalExtension());
            $file->move(public_path($dir), $name);
            $saved[$fieldPath] = '/' . $dir . '/' . $name;
        }
        return $saved;
    }

    private function sources(): array
    {
        return [
            'courses' => Webinar::query()->where('status', 'active')->get()->mapWithKeys(fn($c) => [(string)$c->id => $c->title])->all(),
            'trend_categories' => TrendCategory::query()->with('category')->get()
                ->filter(fn($t) => !empty($t->category) and $t->category->enable)
                ->mapWithKeys(fn($t) => [(string)$t->id => $t->category->title])->all(),
            'instructors' => User::query()->where('role_name', 'teacher')->where('status', 'active')->orderBy('full_name')
                ->pluck('full_name', 'id')->mapWithKeys(fn($n, $id) => [(string)$id => $n])->all(),
            'stat_sources' => collect((new StatisticComponentFrontMixins())->sources)->mapWithKeys(fn($s) => [$s => ucfirst(str_replace('_', ' ', $s))])->all(),
            'colors' => ['primary' => 'Primary (blue)', 'secondary' => 'Secondary (navy)', 'accent' => 'Accent (green)', 'dark' => 'Dark', 'success' => 'Success', 'info' => 'Info', 'warning' => 'Warning', 'danger' => 'Danger'],
        ];
    }

    private function clearCaches(): void
    {
        Cache::flush();
    }
}
