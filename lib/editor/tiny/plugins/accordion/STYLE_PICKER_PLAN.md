# tiny_accordion Style Picker Refactor Plan (v2)

## Overview

Replace free-text CSS class inputs in the accordion attributes modal with a
predefined style picker. Admins configure named presets via a custom dynamic
multi-row widget on the plugin settings page. Authors pick from a dropdown in
the editor modal. Free-text inputs remain as a fallback when no presets are
defined or when "Custom…" is chosen.

**Breaking change from v1:** The pipe-delimited `admin_setting_configtextarea`
approach is **scrapped**. The new approach uses a custom `admin_setting`
subclass that renders a repeating-rows widget, stores JSON in `mdl_config`, and
requires a companion AMD module for the admin UI.

---

## Architecture Summary

```
[Admin Settings Page]
  setting_style_presets (PHP class)
    → admin_style_presets.mustache (renders rows + hidden JSON input)
    → tiny_accordion/admin_presets_widget (AMD JS, syncs JSON on change)
    → mdl_config key: tiny_accordion/stylepresets (stored as JSON array)

[Editor page]
  plugininfo::get_plugin_configuration_for_context()
    → json_decode → validate → pass as PHP array
    → Moodle/TinyMCE bridge → editor.options.get('stylepresets') → JS array

[Author modal]
  ui.js → getStylePresets() → template context
  attributes_modal.mustache → <select> with data-* attrs per preset
  ui.js → initPresetSelects() → wires change events
  ui.js → resolveClass() / resolveStyle() → applyAttrs() → editor DOM
```

---

## Stored JSON Shape

One config key: `tiny_accordion/stylepresets`. Value is a JSON array of objects:

```json
[
  {
    "label": "Blue header",
    "detailsclass": "accordion-blue",
    "detailsstyle": "",
    "summaryclass": "accordion-header-blue",
    "summarystyle": ""
  },
  {
    "label": "Custom border",
    "detailsclass": "",
    "detailsstyle": "border: 2px solid navy;",
    "summaryclass": "",
    "summarystyle": "font-weight: bold;"
  }
]
```

Required keys per entry: `label`, `detailsclass`, `detailsstyle`,
`summaryclass`, `summarystyle`. All string values; all optional except `label`.
Empty string is a valid value (means "don't apply anything to this element").

---

## Phase 1 — Custom Admin Setting Widget + Storage

### 1.1 PHP: `classes/admin/setting_style_presets.php`

New class in the `tiny_accordion\admin` namespace, extending `admin_setting`.

**Responsibilities:**

- `get_setting()` — calls `get_config('tiny_accordion', 'stylepresets')`;
  returns empty string if not set.
- `get_defaultsetting()` — returns `''` (no presets by default).
- `write_setting($data)` — receives the POSTed hidden JSON field, decodes,
  validates, re-encodes, and stores via `set_config`. Returns `''` on success
  or a lang string describing the first validation error.
- `output_html($data, $query = '')` — decodes `$data` into an array, passes it
  as template context to `$OUTPUT->render_from_template()`, and wraps the
  result with `format_admin_setting()`.

**POST data key:**

Moodle admin settings POST data uses the naming convention
`s_{pluginname}_{settingname}` for plugin settings. However, `admin_setting`
already computes this via `$this->get_full_name()`, which returns the correct
key. The hidden `<input>` in the template **must** have
`name="{{fullname}}"` to match what Moodle's admin settings framework
expects.

**`write_setting($data)` — validation logic:**

```php
public function write_setting($data): string {
    // $data is the raw POST string from the hidden JSON input.
    if (trim($data) === '' || trim($data) === '[]') {
        set_config('stylepresets', '[]', 'tiny_accordion');
        return '';
    }

    $decoded = json_decode($data, true);
    if (!is_array($decoded)) {
        return get_string('setting_stylepresets_invalidjson', 'tiny_accordion');
    }

    $clean = [];
    foreach ($decoded as $i => $row) {
        $label = clean_param($row['label'] ?? '', PARAM_TEXT);
        if ($label === '') {
            // Skip rows where label was blanked out (soft delete).
            continue;
        }
        $clean[] = [
            'label'        => $label,
            'detailsclass' => clean_param($row['detailsclass'] ?? '', PARAM_TEXT),
            'detailsstyle' => clean_param($row['detailsstyle'] ?? '', PARAM_TEXT),
            'summaryclass' => clean_param($row['summaryclass'] ?? '', PARAM_TEXT),
            'summarystyle' => clean_param($row['summarystyle'] ?? '', PARAM_TEXT),
        ];
    }

    set_config('stylepresets', json_encode($clean), 'tiny_accordion');
    return '';
}
```

**`output_html($data, $query = '')` — rendering:**

```php
public function output_html($data, $query = ''): string {
    global $OUTPUT;

    $presets = [];
    if (!empty($data)) {
        $decoded = json_decode($data, true);
        if (is_array($decoded)) {
            foreach ($decoded as $i => $row) {
                $presets[] = [
                    'rowindex'     => $i,
                    'label'        => $row['label']        ?? '',
                    'detailsclass' => $row['detailsclass'] ?? '',
                    'detailsstyle' => $row['detailsstyle'] ?? '',
                    'summaryclass' => $row['summaryclass'] ?? '',
                    'summarystyle' => $row['summarystyle'] ?? '',
                ];
            }
        }
    }

    $context = [
        'fullname'    => $this->get_full_name(),   // s_tiny_accordion_stylepresets
        'id'          => $this->get_id(),
        'presets'     => $presets,
        'haspresets'  => count($presets) > 0,
        'currentjson' => $data ?: '[]',
        'addlabel'    => get_string('setting_stylepresets_addrow', 'tiny_accordion'),
        'columns'     => [
            ['key' => 'label',        'label' => get_string('setting_stylepresets_col_label',        'tiny_accordion')],
            ['key' => 'detailsclass', 'label' => get_string('setting_stylepresets_col_detailsclass', 'tiny_accordion')],
            ['key' => 'detailsstyle', 'label' => get_string('setting_stylepresets_col_detailsstyle', 'tiny_accordion')],
            ['key' => 'summaryclass', 'label' => get_string('setting_stylepresets_col_summaryclass', 'tiny_accordion')],
            ['key' => 'summarystyle', 'label' => get_string('setting_stylepresets_col_summarystyle', 'tiny_accordion')],
        ],
    ];

    $html = $OUTPUT->render_from_template('tiny_accordion/admin_style_presets', $context);
    return format_admin_setting($this, $this->visiblename, $html, $this->description, true, '', null, $query);
}
```

---

### 1.2 AMD JS: `amd/src/admin_presets_widget.js`

Handles all interactivity on the admin settings page.

**Module contract:**

- Export `init()` — called via `js_call_amd`.
- Uses **native DOM only** (no jQuery, no `require`d Moodle modules).

**Responsibilities:**

1. On `DOMContentLoaded` (or immediately if DOM already ready), find all
   `[data-widget="accordion-style-presets"]` containers.
2. Wire up the **"Add preset"** button: clone the hidden template row
   (`[data-template-row]`), replace all `data-rowindex="__idx__"` and
   `name` attribute index placeholders with the next row index, show the
   row, append to the table body.
3. Wire up **delete buttons** via event delegation on the table body —
   clicking a `[data-action="delete-row"]` button removes its `<tr>`.
4. Wire up **input `change`/`input` events** on the table body via delegation
   — any input change triggers re-serialisation.
5. Re-serialisation: iterate all visible (non-template) `<tr>` rows,
   collect the five field values, write `JSON.stringify(rows)` to the hidden
   `<input type="hidden">`.
6. Wire up a **form `submit`** handler that fires one final serialisation
   before the form POSTs.

**Key implementation details:**

```js
export const init = () => {
    const containers = document.querySelectorAll('[data-widget="accordion-style-presets"]');
    containers.forEach(initContainer);
};

const initContainer = (container) => {
    const tbody = container.querySelector('[data-presets-tbody]');
    const addBtn = container.querySelector('[data-action="add-row"]');
    const hiddenInput = container.querySelector('[data-presets-json]');
    const templateRow = container.querySelector('[data-template-row]');

    let rowCounter = tbody.querySelectorAll('tr:not([data-template-row])').length;

    addBtn.addEventListener('click', () => {
        const newRow = templateRow.cloneNode(true);
        newRow.removeAttribute('data-template-row');
        newRow.removeAttribute('aria-hidden');
        newRow.style.display = '';
        // Replace placeholder index in input names and ids.
        newRow.innerHTML = newRow.innerHTML.replaceAll('__idx__', rowCounter);
        rowCounter++;
        tbody.appendChild(newRow);
        serialize();
    });

    tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="delete-row"]');
        if (btn) {
            btn.closest('tr').remove();
            serialize();
        }
    });

    tbody.addEventListener('input', serialize);

    container.closest('form')?.addEventListener('submit', serialize);

    const serialize = () => {
        const rows = [...tbody.querySelectorAll('tr:not([data-template-row])')];
        const data = rows.map(tr => ({
            label:        tr.querySelector('[data-field="label"]')?.value        ?? '',
            detailsclass: tr.querySelector('[data-field="detailsclass"]')?.value ?? '',
            detailsstyle: tr.querySelector('[data-field="detailsstyle"]')?.value ?? '',
            summaryclass: tr.querySelector('[data-field="summaryclass"]')?.value ?? '',
            summarystyle: tr.querySelector('[data-field="summarystyle"]')?.value ?? '',
        }));
        hiddenInput.value = JSON.stringify(data);
    };
};
```

---

### 1.3 Mustache Template: `templates/admin_style_presets.mustache`

Renders the admin widget. Context shape matches `output_html()` in §1.1.

**Structure:**

```mustache
{{!
    @template tiny_accordion/admin_style_presets

    Dynamic repeating-rows widget for configuring accordion style presets.
    JS module: tiny_accordion/admin_presets_widget
}}
<div data-widget="accordion-style-presets">
    <table class="table table-sm table-bordered generaltable">
        <thead>
            <tr>
                <th>{{#str}} setting_stylepresets_col_label, tiny_accordion {{/str}} *</th>
                <th>{{#str}} setting_stylepresets_col_detailsclass, tiny_accordion {{/str}}</th>
                <th>{{#str}} setting_stylepresets_col_detailsstyle, tiny_accordion {{/str}}</th>
                <th>{{#str}} setting_stylepresets_col_summaryclass, tiny_accordion {{/str}}</th>
                <th>{{#str}} setting_stylepresets_col_summarystyle, tiny_accordion {{/str}}</th>
                <th><span class="sr-only">{{#str}} delete {{/str}}</span></th>
            </tr>
        </thead>
        <tbody data-presets-tbody>
            {{#presets}}
            <tr>
                <td><input type="text" class="form-control form-control-sm" data-field="label"
                           value="{{label}}" placeholder="e.g. Blue header" required/></td>
                <td><input type="text" class="form-control form-control-sm" data-field="detailsclass"
                           value="{{detailsclass}}" placeholder="accordion-blue"/></td>
                <td><input type="text" class="form-control form-control-sm text-monospace" data-field="detailsstyle"
                           value="{{detailsstyle}}" placeholder="border: 2px solid navy;"/></td>
                <td><input type="text" class="form-control form-control-sm" data-field="summaryclass"
                           value="{{summaryclass}}" placeholder="accordion-header"/></td>
                <td><input type="text" class="form-control form-control-sm text-monospace" data-field="summarystyle"
                           value="{{summarystyle}}" placeholder="font-weight: bold;"/></td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            data-action="delete-row" aria-label="{{#str}} delete {{/str}}">
                        &times;
                    </button>
                </td>
            </tr>
            {{/presets}}

            {{! Hidden template row cloned by JS for new presets }}
            <tr data-template-row aria-hidden="true" style="display:none">
                <td><input type="text" class="form-control form-control-sm" data-field="label"
                           value="" placeholder="e.g. Blue header"/></td>
                <td><input type="text" class="form-control form-control-sm" data-field="detailsclass"
                           value="" placeholder="accordion-blue"/></td>
                <td><input type="text" class="form-control form-control-sm text-monospace" data-field="detailsstyle"
                           value="" placeholder="border: 2px solid navy;"/></td>
                <td><input type="text" class="form-control form-control-sm" data-field="summaryclass"
                           value="" placeholder="accordion-header"/></td>
                <td><input type="text" class="form-control form-control-sm text-monospace" data-field="summarystyle"
                           value="" placeholder="font-weight: bold;"/></td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            data-action="delete-row" aria-label="{{#str}} delete {{/str}}">
                        &times;
                    </button>
                </td>
            </tr>
        </tbody>
    </table>

    <button type="button" class="btn btn-sm btn-secondary mb-2" data-action="add-row">
        + {{addlabel}}
    </button>

    {{! Hidden input holds the serialised JSON; its name matches Moodle's s_pluginname_settingname convention }}
    <input type="hidden" name="{{fullname}}" id="{{id}}" data-presets-json value="{{currentjson}}"/>
</div>
```

**Notes on the template:**

- The hidden template row uses `data-template-row` so JS can select it for
  cloning and excludes it from serialisation.
- Inputs in the template row have no `name` attributes — they are never
  submitted directly (only the hidden JSON input is).
- The `{{fullname}}` on the hidden input is the critical link: Moodle's admin
  settings POST handler looks for `s_tiny_accordion_stylepresets` in `$_POST`,
  which is exactly what `$this->get_full_name()` returns and what the hidden
  input must be named.

---

### 1.4 `settings.php` — Replace Textarea with Custom Widget

Remove any existing `stylepresets` setting (textarea approach) and add:

```php
// In the $ADMIN->fulltree block, after the classprefixallowlist setting:

$settings->add(new \tiny_accordion\admin\setting_style_presets(
    'tiny_accordion/stylepresets',
    new lang_string('settings_stylepresets', 'tiny_accordion'),
    new lang_string('settings_stylepresets_desc', 'tiny_accordion'),
    '' // default: empty (no presets)
));
```

**Loading the AMD module on the admin settings page:**

Moodle's admin settings pages do not automatically load plugin AMD modules.
The correct approach is to call `$PAGE->requires->js_call_amd()` inside the
`$hassiteconfig` / `$ADMIN->fulltree` block:

```php
if ($hassiteconfig) {
    // ... other settings ...

    if ($ADMIN->fulltree) {
        // ... add settings ...

        $PAGE->requires->js_call_amd('tiny_accordion/admin_presets_widget', 'init');
    }
}
```

**Gotcha — `$PAGE` availability in `settings.php`:**

`settings.php` runs during the admin settings page render, at which point
`$PAGE` is fully initialised. `$PAGE->requires->js_call_amd()` queues the
module for output in the page footer. This is the same pattern used by
`tool_usertours` and other plugins that need custom admin JS. Do **not** use
`$CFG->wwwroot . '/...'` script tags — use `js_call_amd` exclusively.

The AMD call fires `init()` after the DOM is ready (Moodle's RequireJS
bootstrapper handles DOMContentLoaded). No extra event listener is needed
inside the module itself.

---

### 1.5 `classes/plugininfo.php` — Parse JSON into PHP Array

Add a private static helper and update `get_plugin_configuration_for_context()`.

**Helper:**

```php
/**
 * Parse and validate the stylepresets JSON config value.
 *
 * Returns an indexed array of clean preset objects. Invalid or empty JSON
 * returns an empty array. Each entry is sanitised with PARAM_TEXT.
 *
 * @param string $json Raw config value (JSON array string or empty).
 * @return array<int, array{label: string, detailsclass: string, detailsstyle: string,
 *                          summaryclass: string, summarystyle: string}>
 */
private static function parse_style_presets(string $json): array {
    if (trim($json) === '' || trim($json) === '[]') {
        return [];
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return [];
    }

    $presets = [];
    foreach ($decoded as $row) {
        $label = clean_param($row['label'] ?? '', PARAM_TEXT);
        if ($label === '') {
            continue; // label required; skip silently
        }
        $presets[] = [
            'label'        => $label,
            'detailsclass' => clean_param($row['detailsclass'] ?? '', PARAM_TEXT),
            'detailsstyle' => clean_param($row['detailsstyle'] ?? '', PARAM_TEXT),
            'summaryclass' => clean_param($row['summaryclass'] ?? '', PARAM_TEXT),
            'summarystyle' => clean_param($row['summarystyle'] ?? '', PARAM_TEXT),
        ];
    }
    return $presets;
}
```

**In `get_plugin_configuration_for_context()`**, add after `classprefixallowlist`:

```php
'stylepresets' => self::parse_style_presets(
    (string) (get_config('tiny_accordion', 'stylepresets') ?? '')
),
```

The returned PHP indexed array is automatically JSON-encoded by Moodle's
plugin configuration bridge and arrives in JS as a native array of objects.

---

### Phase 1 File Summary

| File | Action |
|---|---|
| `classes/admin/setting_style_presets.php` | **New file.** Custom `admin_setting` subclass |
| `templates/admin_style_presets.mustache` | **New file.** Repeating-rows widget template |
| `amd/src/admin_presets_widget.js` | **New file.** Admin widget JS (native DOM) |
| `settings.php` | Replace textarea with `setting_style_presets`; add `js_call_amd` call |
| `classes/plugininfo.php` | Add `parse_style_presets()`; add `stylepresets` to config array |
| `lang/en/tiny_accordion.php` | Add new strings (see §1.6) |

---

### 1.6 Lang Strings for Phase 1 (`lang/en/tiny_accordion.php`)

```php
$string['settings_stylepresets']                    = 'Accordion style presets';
$string['settings_stylepresets_desc']               = 'Define named style presets for accordion authors to choose from. Each row is one preset. Label is required; all other columns are optional. CSS class columns accept space-separated class tokens. Style columns accept raw CSS property declarations (e.g. "border: 2px solid navy;"). Style values are applied as-is and should be vetted by the admin.';
$string['setting_stylepresets_addrow']              = 'Add preset';
$string['setting_stylepresets_invalidjson']         = 'Invalid preset data. Please re-enter your presets and save again.';
$string['setting_stylepresets_col_label']           = 'Label';
$string['setting_stylepresets_col_detailsclass']    = 'Details CSS class(es)';
$string['setting_stylepresets_col_detailsstyle']    = 'Details inline style';
$string['setting_stylepresets_col_summaryclass']    = 'Summary CSS class(es)';
$string['setting_stylepresets_col_summarystyle']    = 'Summary inline style';
```

Editor-facing strings (used in Phase 2):

```php
$string['accordionstylepreset']      = 'Style preset';
$string['accordionstylepreset_none'] = '— None —';
$string['accordionstylepreset_custom'] = 'Custom (enter classes manually)';
```

---

## Phase 2 — Editor Style Picker Dropdown

**What changes vs. v1 of this plan:**

The JS/template work in Phase 2 is essentially identical to the previous plan.
The only difference is the **data source**: instead of a pipe-delimited
textarea parsed in PHP, the presets array is already clean JSON from
`setting_style_presets`. The data *shape* seen by `options.js`, `ui.js`, and
`attributes_modal.mustache` is identical — `plugininfo.php` normalises it to
the same five-key object array regardless of storage format. All Phase 2
details from the previous plan apply unchanged.

### 2.1 `amd/src/options.js` — Register stylepresets Option

```js
const stylePresetsOption = getPluginOptionName(pluginName, 'stylepresets');

// inside register():
registerOption(stylePresetsOption, {
    processor: 'object',   // TinyMCE treats arrays as objects here
    default: [],
});

// new getter:
export const getStylePresets = (editor) => editor.options.get(stylePresetsOption) ?? [];
```

`processor: 'object'` accepts the JS array passed through Moodle's
plugin-configuration bridge without modification.

### 2.2 `templates/attributes_modal.mustache` — Add Preset Selects

When `{{haspresets}}` is true, each fieldset gains a `<select>` above the
class text input. The text input wrapper gets `d-none` by default (shown when
"Custom…" is selected). Each `<option>` carries four data attributes:

- `data-detailsclass`
- `data-detailsstyle`
- `data-summaryclass`
- `data-summarystyle`

The class text input wrapper uses `{{#haspresets}}d-none{{/haspresets}}` so
Mustache toggles it based on whether presets exist.

### 2.3 `amd/src/ui.js` — Pass Presets to Template + Wire Events

1. Import `getStylePresets` from `tiny_accordion/options`.
2. In `handleAttributesAction`, add `haspresets` and `stylepresets` to the
   `templateContext` object.
3. After `modal.getRoot()`, call `initPresetSelects(root, detailsAttrs.class, summaryAttrs.class, presets, allowStyle)`.
4. `initPresetSelects` wires `change` events on both selects:
   - Toggling the "Custom…" option shows/hides the text input wrappers.
   - Selecting a named preset auto-populates the summary select and (when
     `allowinlinestyle` is true) both style inputs from the option's
     `data-*` attributes.
5. Update the `submit` handler to use `resolveClass()` and `resolveStyle()`
   helpers that read from the select when it exists, otherwise from the text
   input.
6. When a preset is selected (not "Custom…"), `resolveClass` returns the
   preset value directly — **`filterClasses` is not called** on preset values
   (they are admin-vetted). Only "Custom…" free-text input goes through
   `filterClasses`.

### 2.4 Phase 2 File Summary

| File | Action |
|---|---|
| `amd/src/options.js` | Register `stylepresets` option + export `getStylePresets` |
| `templates/attributes_modal.mustache` | Add `{{#haspresets}}` selects; wrap class inputs in custom-class div; add `data-*` attrs on options |
| `amd/src/ui.js` | Import `getStylePresets`; build template context; `initPresetSelects()`; `resolveClass()`/`resolveStyle()`; update `submit` |
| `lang/en/tiny_accordion.php` | Already added in Phase 1 §1.6 |

---

## Phase 3 — Live Preview (Optional / Future)

Unchanged from the previous plan. **Approach B** (live DOM preview via
`editor.undoManager.ignore()`) is recommended. Only `amd/src/ui.js` is
touched. Implement after Phase 2 is stable and tested.

---

## Implementation Order

```
Phase 1a:  classes/admin/setting_style_presets.php (PHP class)
Phase 1b:  templates/admin_style_presets.mustache   (widget template)
Phase 1c:  amd/src/admin_presets_widget.js          (admin widget JS)
Phase 1d:  settings.php                             (swap setting + js_call_amd)
Phase 1e:  classes/plugininfo.php                   (parse_style_presets + config key)
Phase 1f:  lang/en/tiny_accordion.php               (all new strings)
Phase 1g:  grunt amd --plugin=tiny_accordion        (build admin_presets_widget)
           → smoke test: open admin settings page, add/delete presets, save

Phase 2a:  amd/src/options.js                       (register stylepresets)
Phase 2b:  templates/attributes_modal.mustache      (preset selects + data-* attrs)
Phase 2c:  amd/src/ui.js                            (pass presets, init, resolve, submit)
Phase 2d:  grunt amd --plugin=tiny_accordion        (build ui)
           → smoke test: configure 2 presets, open editor modal, pick preset, apply

Phase 3:   (optional) live preview — amd/src/ui.js only
```

---

## Risks and Gotchas

### AMD on admin settings pages

Moodle's admin settings pages load RequireJS but do not automatically queue
plugin AMD modules. `$PAGE->requires->js_call_amd()` inside `settings.php` is
the correct mechanism. The call must be inside the `$ADMIN->fulltree` block to
avoid running on every admin page load.

Do not call `js_call_amd` from `output_html()` — that method runs during form
rendering and may be called multiple times or outside the full page context.
Calling it once from `settings.php` is reliable.

### Hidden JSON input naming

Moodle admin settings POST handling finds each setting's value by
`$setting->get_full_name()`, which returns `s_tiny_accordion_stylepresets`
(the `s_` prefix plus `{plugin}/{settingname}` with `/` replaced by `_`).
The hidden `<input type="hidden">` in the mustache template **must** use
`name="{{fullname}}"` where `{{fullname}}` is the value of
`$this->get_full_name()` passed via template context. A mismatch silently
causes the setting to not save.

### Row deletion must update the hidden field immediately

Simply hiding a `<tr>` with `display:none` is insufficient — the row will
still be serialised. The delete handler must call `.remove()` on the row
and then immediately re-run `serialize()`. Soft-deletion (hide + mark) is
explicitly avoided.

### Security: write_setting must re-encode, never store raw POST

`write_setting()` must:
1. `json_decode` the POST string
2. Validate it is an array
3. Run `clean_param($val, PARAM_TEXT)` on every field of every row
4. `json_encode` the cleaned array and store that

**Never** call `set_config` with the raw POST string. An admin could POST
crafted JSON with XSS payloads; re-encoding after clean_param ensures the
stored value is a well-formed JSON string of safe text values.

`clean_param(PARAM_TEXT)` strips HTML tags and encodes special characters.
It does not validate CSS syntax, which is acceptable — style values are
admin-level trusted content, consistent with other `PARAM_TEXT` admin
settings. HTMLPurifier on the author's save provides a second layer.

### Inline styles from presets and `allowinlinestyle`

When `allowinlinestyle` is `false`, `resolveStyle()` returns `''`
unconditionally. Preset style values are silently ignored — the admin chose
not to expose inline styles to authors, and preset styles are no exception.
Admins who want site-wide styling should use CSS classes in the preset instead.

### Existing content compatibility

Authors who previously typed custom classes will match "Custom…" fallback in
`setInitialValue` (no preset matches their class value). Their class is
preserved in the text input. No migration needed.

### Reusability note

`setting_style_presets` and `admin_presets_widget.js` are intentionally
generic (configurable column definitions, JSON storage, native DOM). They
could be extracted into a shared plugin (e.g. `local_adminwidgets`) or
contributed upstream. **For now, implementation is scoped to `tiny_accordion`
only.** Do not premature-abstract.

---

## Moodle API Patterns Reference

| Need | API |
|---|---|
| Custom admin setting widget | Extend `admin_setting`, implement `output_html()` + `write_setting()` |
| Render template in PHP (non-renderer context) | `$OUTPUT->render_from_template($templatename, $context)` |
| Wrap admin setting HTML | `format_admin_setting($setting, $title, $element, $description, ...)` |
| AMD on admin settings page | `$PAGE->requires->js_call_amd('plugin/module', 'init')` in `settings.php` |
| Admin POST field naming | `$setting->get_full_name()` → `s_{plugin}_{name}` |
| Per-context PHP→JS config | `plugin_with_configuration` + `get_plugin_configuration_for_context()` |
| JS option registration | `editor_tiny/options` → `getPluginOptionName` + `registerOption` |
| Reading plugin config in JS | `editor.options.get()` (runtime) |
| Bootstrap 5 show/hide | `d-none` class toggle |
| Moodle modal | `core/modal` subclass with `static TEMPLATE` |
| Undo-safe live DOM changes | `editor.undoManager.ignore(fn)` |
| Class sanitisation | `clean_param($val, PARAM_TEXT)` in PHP; `filterClasses()` in JS |
| Style sanitisation | `clean_param($val, PARAM_TEXT)` in PHP; `editor.dom.setAttrib` in JS (safe against XSS) |
| AMD build | `grunt amd --plugin=tiny_accordion` (or `npx grunt amd`) |
