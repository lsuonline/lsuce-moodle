# Bug-004 — Simple Restore admin navigation revamp

**Date:** 2026-05-08
**Plugin:** `blocks/simple_restore`
**Reference implementation:** `blocks/backadel` (see `blocks/backadel/settings.php:36-189`)
**Complexity:** **M** (two PHP entry points to rewire, one of which is dual-use teacher/admin; full AMD + Mustache copy)

---

## 1. Background — what's wrong today

Simple Restore is reachable in two unrelated ways:

1. **Teacher path (course context):** the block's content links into `list.php?id=<courseid>&restore_to=…` from inside a course. This is the original use case (teacher restores a backup into their own course). Already works fine, must not be broken.
2. **Admin path (system context):** an admin opening the plugin from `Site administration → Plugins → Blocks → Simple Restore` lands on the `$settings` page (`?section=blocksettingsimple_restore`). From there… they can't reach the actual restore UI. They have to either know that `/blocks/simple_restore/list.php?id=1` exists or click through a course. There is no breadcrumb / sidebar / tab strip wiring the admin pages together — `list.php` calls `require_login()` and rolls its own `$PAGE` setup (see `blocks/simple_restore/list.php:30-31`, `132-140`), and `restore.php` does the same (see `blocks/simple_restore/restore.php:62-78`).

We want the same admin-nav treatment we just shipped for `block_backadel`:

- Plugin-level `admin_category` under `blocksettings`.
- Each plugin page registered as `admin_externalpage` so the left sidebar + breadcrumbs work.
- A Bootstrap `nav-tabs` strip across all the admin-context pages, rendered by the same lazy AMD module + Mustache template (lifted from `block_backadel`).

But there's a wrinkle: `list.php` is **dual-use** — teachers hit it from a course, admins hit it from the admin tree. The tab strip must only appear on the admin-context view. See §4.

---

## 2. Page inventory

Files that produce admin-visible HTML (the only ones we care about for nav):

| File | Current setup | Audience today | New `admin_externalpage` name |
|---|---|---|---|
| `settings.php` (auto-rendered as `/admin/settings.php?section=blocksettingsimple_restore`) | Auto-wired by block loader; section `blocksettingsimple_restore`. See `blocks/simple_restore/settings.php:24-155`. | Admin only | (kept; re-parented under category) |
| `list.php` (admin entry, with `?id=1` or `?shortname=…`) | `require_login()` + manual `$PAGE->set_url/set_context/navbar/set_title/set_heading` (lines 30, 76-91, 132-140). No admin wiring. | **Dual** (teacher in course, admin from system) | `block_simple_restore_list` |
| `restore.php` (confirm + execute) | `require_login($course)` + manual `$PAGE` setup (lines 62-78). No admin wiring. | **Dual** (called from list.php for both audiences) | not a tab; only used as a target via redirect (see §4.3) |

Files that **do not** need new wiring:

- `block_simple_restore.php` — block class, course-side only.
- `lib.php` / `list_form.php` / `settingslib.php` — helpers, no `$PAGE` of their own.
- `db/access.php`, `version.php`, `lang/…` — declarative.

So the user-visible tab strip will have **two tabs** (Settings + Restore Courses). That's fewer than backadel (which has 7), but it's still worth wiring — the value is breadcrumbs + sidebar parity, not just tab count.

---

## 3. Admin category structure

**Recommendation: yes, give `simple_restore` its own `admin_category`** under `blocksettings`, exactly like backadel. Reasons:

1. We are adding at least one new `admin_externalpage` (`list.php`). Without a category, the sidebar groups the externalpage and the settings page as flat siblings under "Blocks", which is busy — both backadel and simple_restore (when both are wired) would compete for the same flat region. A category keeps each plugin self-contained.
2. The category gives us a stable parent ID (`block_simple_restore_category`) we can hang future pages off (e.g. an "archive admin" page if archive-server mode grows its own UI).
3. Matches backadel for visual consistency. Admins will see two collapsible plugin nodes side-by-side.

**Category label:** reuse `pluginname` (`Simple Restore`) — same pattern as backadel (`new lang_string('pluginname', 'block_backadel')` at `blocks/backadel/settings.php:38`).

```php
$ADMIN->add('blocksettings', new admin_category(
    'block_simple_restore_category',
    new lang_string('pluginname', 'block_simple_restore')
));
```

The auto-created `$settings` object is then re-parented under that category and given a clearer label (block loader sets it to plugin display name, which duplicates the category — same pattern as backadel `settings.php:44-45`):

```php
$settings->visiblename = new lang_string('nav_settings', 'block_simple_restore');
$ADMIN->add('block_simple_restore_category', $settings);
```

(Followed by the `if ($ADMIN->fulltree) { … existing checkboxes … }` body, then `$settings = null;` at the very end so the block loader doesn't double-register.)

---

## 4. Special case: `list.php` is dual-use

This is the load-bearing complexity of this revamp.

### 4.1 What `list.php` does today

`list.php` decides its context dynamically (see `blocks/simple_restore/list.php:42-64`):

```php
$archivemode = $courseid == SITEID && get_config('simple_restore', 'is_archive_server');
…
if ($archivemode) {
    $context = context_system::instance();
    require_capability('block/simple_restore:canrestorearchive', $context);
} else {
    $context = context_course::instance($courseid);
    require_capability('block/simple_restore:canrestore', $context);
}
```

And later (lines 144-172) it branches on whether the user is an admin:

```php
$isadmin = has_capability('moodle/course:create', $system);

if (empty($shortname) and $isadmin) {
    // Show shortname filter form, then die.
}
```

So three modes coexist in one file:

| Mode | Trigger | Tab strip should appear? |
|---|---|---|
| **Teacher in course** | `?id=<realcourseid>`, `$isadmin === false` | **No** |
| **Admin filter form** | `?id=1` (SITEID) or admin context, no `shortname=` yet | **Yes** |
| **Admin filtered list** | `?id=1` (SITEID), `shortname=<value>` set | **Yes** |
| **Archive-server mode** | `?id=1` and `is_archive_server` config on | **Yes** (system context) |

### 4.2 How to disambiguate cleanly

Use the same flag the existing code already computes: `$isadmin = has_capability('moodle/course:create', $system)` (line 144). Extend the meaning slightly: an admin browsing this URL who has system-level course creation cap is in admin mode regardless of `?id=`. Concretely:

```php
$adminmode = $archivemode
    || $courseid == SITEID
    || (empty($shortname) && has_capability('moodle/course:create', context_system::instance()));
```

Branching:

- **`$adminmode === true`** → call `admin_externalpage_setup('block_simple_restore_list')`. This:
  - Invokes `require_login()` internally.
  - Sets `$PAGE->set_url()`, `set_context(system)`, `set_pagelayout('admin')`, page title, breadcrumbs, sidebar highlight.
  - Triggers our tab strip (because settings.php's `js_call_amd` only matches admin-context body IDs — see §5).
- **`$adminmode === false`** → leave the existing teacher path alone: `require_login($course)`, course-level `$PAGE` setup, course breadcrumb. **Skip** `admin_externalpage_setup`.

The capability check works because `has_capability('moodle/course:create', context_system::instance())` is true for site managers and false for editing-teachers in their course. If we ever need it stricter, we can require `moodle/site:config` instead — but `course:create` is the cap the file already trusts.

### 4.3 What about `restore.php`?

`restore.php` is hit only as a redirect target from `list.php` (the file picker) and from `block_backadel/restore.php` (the catalogue Restore button, see `blocks/simple_restore/restore.php:30-48`). It always operates on a real course context — even in admin mode the upstream `list.php` has just created a brand-new course via `restore_dbops::create_new_course()` (line 119). So:

- **Don't** add `admin_externalpage_setup()` to `restore.php`.
- **Don't** include it in the tab strip.
- Leave its current `require_login($course)` + manual `$PAGE` setup untouched.

This avoids two failure modes: (1) trying to set system-context page layout while operating on a real course context, and (2) showing the tab strip on a confirm-and-execute page where it would invite the user to navigate away mid-restore.

### 4.4 What if a teacher has `moodle/course:create`?

`coursecreator` archetype has `moodle/course:create` at the system level. Such a user clicking the in-course block link with `?id=<theircourse>` would hit `$adminmode === true` and we'd switch them to admin layout. To prevent that, narrow the gate so admin mode requires `$courseid == SITEID` **or** archive mode:

```php
$adminmode = $archivemode || $courseid == SITEID;
```

That's cleaner. It maps exactly to the two ways the admin tree links to this page (id=1 from the new externalpage, or archive mode). Teachers landing with `?id=<realcourse>` always get the course path. **Use this version.**

---

## 5. Tab strip

Two tabs. List of links / strings / body-ID selectors:

| Tab | Link | Lang string | Body-ID selector |
|---|---|---|---|
| Settings | `/admin/settings.php?section=blocksettingsimple_restore` | `tab_settings` ("Global settings") | `#page-admin-setting-blocksettingsimple_restore` |
| Restore Courses | `/blocks/simple_restore/list.php?id=1` (SITEID) | `tab_list` ("Restore Courses") | `#page-admin-blocks-simple_restore-list` |

Body-ID derivation (mirrors backadel):

- `admin_externalpage_setup('block_simple_restore_list')` is called from `blocks/simple_restore/list.php`. Moodle's pagetype derivation strips `.php` and replaces `/` with `-`, then prefixes `page-admin-` for admin layout pages → `page-admin-blocks-simple_restore-list`. Underscore is preserved (it's not a separator). Same logic as backadel's `#page-admin-blocks-backadel-migrate` (from `migrate.php`).
- Settings section name is `blocksetting<plugin>` (block-loader convention; see how `blocksettingbackadel` is used at `blocks/backadel/settings.php:152`). For us: `blocksettingsimple_restore`. Body ID: `#page-admin-setting-blocksettingsimple_restore`.

The `?id=1` query string on the Restore Courses link is required so the URL hits SITEID and triggers `$adminmode === true` (per §4.4). Without it, `list.php` won't admin-mode-gate and our `admin_externalpage_setup()` call won't even be reached. Pre-build the URL once in settings.php:

```php
$listurl = new moodle_url('/blocks/simple_restore/list.php', ['id' => SITEID]);
```

---

## 6. AMD + Mustache files to copy

The `admin-tabs-lazy` module is generic — it takes links/strings/validPlaces as args. The only block-specific thing in it is the `Templates.render('block_backadel/admin_tabs', …)` call (line 41 of the AMD source). We have two options:

1. **Lift-and-rename** (recommended): copy the AMD source to `blocks/simple_restore/amd/src/admin-tabs-lazy.js` and the template to `blocks/simple_restore/templates/admin_tabs.mustache`. Change the `Templates.render` call to `'block_simple_restore/admin_tabs'`. Run grunt.
2. **Share via `block_backadel`**: have simple_restore's `js_call_amd` invoke `block_backadel/admin-tabs-lazy`. Cross-plugin coupling — rejected. Backadel could be uninstalled independently; simple_restore should stand alone.

Files to create:

- `blocks/simple_restore/amd/src/admin-tabs-lazy.js` — copy of `blocks/backadel/amd/src/admin-tabs-lazy.js`, only line 41 changed (`block_backadel/admin_tabs` → `block_simple_restore/admin_tabs`). Update the file-level `@module` docblock.
- `blocks/simple_restore/templates/admin_tabs.mustache` — copy of `blocks/backadel/templates/admin_tabs.mustache`. Change the CSS class `block-backadel-admin-tabs` → `block-simple-restore-admin-tabs` so we can style it independently if needed (no current style rules match either, so this is just hygiene). Update template docblock.
- `blocks/simple_restore/templates/` directory must be created (it doesn't exist today; verified by `ls`).

Build command (from repo root, in the docker container per the `moodle-docker-cli` skill):

```bash
./run-docker-exec.sh "cd /var/www/html && npx grunt amd --root=blocks/simple_restore"
```

Or simpler, the standard flow already used elsewhere in this repo:

```bash
./run-docker-exec.sh "cd /var/www/html/blocks/simple_restore && npx grunt amd"
```

That produces `blocks/simple_restore/amd/build/admin-tabs-lazy.min.js` + `.map`. Both must be committed (the existing `restore_actions.min.js` precedent in `amd/build/` confirms the build artifacts are checked in).

---

## 7. Lang strings to add

In `blocks/simple_restore/lang/en/block_simple_restore.php` (currently 97 lines):

```php
// Admin navigation labels.
$string['nav_settings'] = 'Global settings';
$string['nav_list']     = 'Restore Courses';

// Tab strip labels (kept separate from nav_* in case we want shorter strings on tabs).
$string['tab_settings'] = 'Global settings';
$string['tab_list']     = 'Restore Courses';
```

Backadel uses both `nav_*` (for sidebar / externalpage display name) and `tab_*` (for the in-page tab strip) — see `blocks/backadel/lang/en/block_backadel.php:201-214`. Same pattern here. They happen to have the same English text today but the duality lets us tighten the tab text later without affecting the sidebar.

---

## 8. File-by-file change list

### 8.1 `blocks/simple_restore/settings.php`

**Complete replacement file content** (overwrites the existing 156-line file). Preserves every existing setting (`generalsettings`, `highlevelsettings`, modules, `$producer`, `$flatmap`, `is_archive_server`, async settings) inside the `$ADMIN->fulltree` block; adds category, re-parenting, externalpage, AMD tab strip, and `$settings = null` terminator.

```php
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    block_simple_restore
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards Chad Mazilly, Robert Russo, Jason Peak, Dave Elliott, Adam Zapletal, Philip Cali
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $PAGE;

/** @var admin_root $ADMIN */
/** @var bool $hassiteconfig */

if (!$hassiteconfig) {
    return;
}

$pluginname = 'block_simple_restore';

// Plugin-level category under "Block settings".
$ADMIN->add('blocksettings', new admin_category(
    'block_simple_restore_category',
    new lang_string('pluginname', $pluginname)
));

// Place the pre-created $settings page under our category instead of the default
// blocksettings root. Override the display name (block loader sets it to the
// plugin display name "Simple Restore") with a clearer label.
$settings->visiblename = new lang_string('nav_settings', $pluginname);
$ADMIN->add('block_simple_restore_category', $settings);

// Restore general settings.
if ($ADMIN->fulltree) {

    // Course Settings for restore.
    $generalsettings = array(
        'enrol_migratetomanual' => 0,
        'users' => 0,
        'user_files' => 0,
        'role_assignments' => 0,
        'activities' => 1,
        'blocks' => 1,
        'filters' => 1,
        'comments' => 0,
        'userscompletion' => 0,
        'logs' => 0,
        'grade_histories' => 0
    );

    $highlevelsettings = array(
        'keep_roles_and_enrolments' => 0,
        'keep_groups_and_groupings' => 0,
        'overwrite_conf' => 1
    );

    $modules = $DB->get_records_menu('modules', null, 'id, name') +
            array(0 => 'section');

    $producer = function ($type, $default) {
        return function ($module) use ($type, $default) {
            return array("{$module}_{$type}" => $default);
        };
    };

    $flatmap = function ($in, $module) use ($producer) {
        $included = $producer('included', 1);
        $userinfo = $producer('userinfo', 0);
        return $in + $included($module) + $userinfo($module);
    };

    // Flat mapped the producer defaults with original modules.
    $coursesettings = array_reduce($modules, $flatmap, array());

    // Appropriate keys.
    $srk = function ($key) {
        return "simple_restore/{$key}";
    };

    $srs = function ($k, $a=null) {
        return get_string($k, 'block_simple_restore', $a);
    };

    $itersettings = function ($chosensettings) use ($settings, $srk, $srs) {
        foreach ($chosensettings as $name => $default) {
            $str = $srs($name);
            $settings->add(
                new admin_setting_configcheckbox($srk($name), $str, $str, $default)
            );
        }
    };

    // Archive server mode toggle.
    $settings->add(
            new admin_setting_configcheckbox(
                    $srk('is_archive_server'),
                    $srs('is_archive_server'),
                    $srs('is_archive_server_desc'),
                    0,
                    1,
                    0)
            );

    // Start building the Admin screen.
    $settings->add(
        new admin_setting_heading(
            $srk('general'), $srs('general'), $srs('general_desc')
        )
    );

    $itersettings($generalsettings);

    $settings->add(
        new admin_setting_heading(
            $srk('course'), $srs('course'), $srs('course_desc')
        )
    );


    $itersettings($highlevelsettings);

    $settings->add(
        new admin_setting_heading(
            $srk('module'), $srs('module'), $srs('module_desc')
        )
    );

    foreach ($coursesettings as $name => $default) {
        $data = explode('_', $name);
        $type = array_pop($data);
        $module = implode('_', $data);
        if ($module == 'section') {
            $modulename = $srs('section');
        } else {
            $modulename = get_string('pluginname', 'mod_'.$module);
        }
        $str = $srs('restore_'.$type, $modulename);
        $settings->add(
            new admin_setting_configcheckbox($srk($name), $str, $str, $default)
        );
    }

    // ----------------------------------------------------------------
    // Async Settings Title.
    $settings->add(
        new admin_setting_heading(
            'block_simple_restore_async_title',
            get_string('async_title', 'block_simple_restore'),
            ''
        )
    );

    // Remote student role id.
    $settings->add(
        new admin_setting_configcheckbox(
            'simple_restore/async_toggle',
            get_string('async_toggle_title', 'block_simple_restore'),
            get_string('async_toggle_desc', 'block_simple_restore'),
            0 // Default.
        )
    );
}

// External page: Restore Courses (admin-context view of list.php).
$ADMIN->add('block_simple_restore_category', new admin_externalpage(
    'block_simple_restore_list',
    new lang_string('nav_list', $pluginname),
    new moodle_url('/blocks/simple_restore/list.php', ['id' => SITEID])
));

// Cross-page tab strip — must run outside fulltree so it fires on
// admin_externalpage requests (list.php in admin mode) too.
if (!CLI_SCRIPT) {
    $links = [
        (new moodle_url('/admin/settings.php', ['section' => 'blocksettingsimple_restore']))->out(false),
        (new moodle_url('/blocks/simple_restore/list.php', ['id' => SITEID]))->out(false),
    ];

    $strings = [
        get_string('tab_settings', $pluginname),
        get_string('tab_list', $pluginname),
    ];

    $validplaces = [
        '#page-admin-setting-blocksettingsimple_restore',
        '#page-admin-blocks-simple_restore-list',
    ];

    $PAGE->requires->js_call_amd("{$pluginname}/admin-tabs-lazy", 'init', [
        $links,
        $strings,
        $validplaces,
    ]);
}

// Prevent the block manager from double-registering $settings under 'blocksettings'.
$settings = null;
```

### 8.2 `blocks/simple_restore/list.php` — exact diffs

Three change-sites. Apply each independently.

#### 8.2.a Top of file: require + admin-mode gate

```
=== REMOVE (lines 24–40) ===
require_once('../../config.php');
global $CFG;

require_once($CFG->dirroot . '/blocks/simple_restore/lib.php');

// Check permissions.
require_login();

$courseid = required_param('id', PARAM_INT);
$restoreto = optional_param('restore_to', 0, PARAM_INT);

$name = optional_param('name', null, PARAM_ALPHANUMEXT);
$action = optional_param('action', null, PARAM_ALPHANUMEXT);
$file = optional_param('fileid', null, PARAM_FILE);

// Needed for admins, as they need to query the courses.
$shortname = optional_param('shortname', null, PARAM_TEXT);
=== ADD ===
require_once('../../config.php');
global $CFG;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/simple_restore/lib.php');

$courseid = required_param('id', PARAM_INT);
$restoreto = optional_param('restore_to', 0, PARAM_INT);

$name = optional_param('name', null, PARAM_ALPHANUMEXT);
$action = optional_param('action', null, PARAM_ALPHANUMEXT);
$file = optional_param('fileid', null, PARAM_FILE);

// Needed for admins, as they need to query the courses.
$shortname = optional_param('shortname', null, PARAM_TEXT);

// Determine whether archive mode (also recomputed below for capability gating).
$archivemode = $courseid == SITEID && get_config('simple_restore', 'is_archive_server');

// Admin path = SITEID landing or archive-server mode. Teachers always hit a
// real course id, so they never trip this gate even if they happen to hold
// moodle/course:create at the system level.
$adminmode = $archivemode || $courseid == SITEID;

if ($adminmode) {
    // Admin layout: registers the page with the admin tree, wires breadcrumbs,
    // sidebar highlight, page layout, and triggers settings.php's tab strip.
    admin_externalpage_setup('block_simple_restore_list', '', [
        'id' => $courseid,
        'restore_to' => $restoreto,
    ]);
} else {
    // Teacher path: course-context login + permission check.
    require_login();
}
```

Note: the existing `$archivemode = …` assignment that currently lives at line 43 becomes redundant after this insertion. Leave it in place — it's harmless to recompute and minimises the diff footprint of step 8.2.b. (Alternative: remove the line 43 reassignment. Either is fine; the value is identical.)

#### 8.2.b "Have grades" early-return PAGE setup

```
=== REMOVE (lines 75–82) ===
if ($count > 0) {
    $PAGE->set_url($baseurl);
    $warn = $OUTPUT->notification(simple_restore_utils::_s('have_grades'));

    $PAGE->set_context($context);
    $PAGE->navbar->add($course->fullname, new moodle_url('/course/view.php?id='.$course->id));
    $PAGE->set_title($blockname.': '.$heading);

=== ADD ===
if ($count > 0) {
    $warn = $OUTPUT->notification(simple_restore_utils::_s('have_grades'));

    if (!$adminmode) {
        $PAGE->set_url($baseurl);
        $PAGE->set_context($context);
        $PAGE->navbar->add($course->fullname, new moodle_url('/course/view.php?id='.$course->id));
        $PAGE->set_title($blockname.': '.$heading);
    }

```

#### 8.2.c Main `$PAGE` setup block

```
=== REMOVE (lines 132–140) ===
$PAGE->set_context($context);
$PAGE->set_course($course);
if (!$archivemode && $course->id != SITEID) {
    $PAGE->navbar->add($course->fullname, new moodle_url('/course/view.php', ['id' => $course->id]));
}
$PAGE->navbar->add($blockname);
$PAGE->set_title($blockname.': '.$heading);
$PAGE->set_heading($blockname.': '.$heading);
$PAGE->set_url($baseurl);
=== ADD ===
if (!$adminmode) {
    $PAGE->set_context($context);
    $PAGE->set_course($course);
    if (!$archivemode && $course->id != SITEID) {
        $PAGE->navbar->add($course->fullname, new moodle_url('/course/view.php', ['id' => $course->id]));
    }
    $PAGE->navbar->add($blockname);
    $PAGE->set_title($blockname.': '.$heading);
    $PAGE->set_heading($blockname.': '.$heading);
    $PAGE->set_url($baseurl);
}
```

The downstream `$isadmin = has_capability('moodle/course:create', $system)` predicate at line 144 and the admin-filter-form branch (lines 146-172) are untouched — they continue to drive the in-page form rendering. Only page chrome moves under `$adminmode`.

### 8.3 `blocks/simple_restore/restore.php`

**No changes.** See §4.3.

### 8.4 `blocks/simple_restore/lang/en/block_simple_restore.php`

Append the following block to the end of the file (after the existing `$string['async_toggle_desc']` line at line 96; the file currently ends at line 97 with no trailing strings).

Context — the last few lines of the current file:

```php
// Async Settings.
$string['async_title'] = 'Async Restore Settings';
$string['async_toggle_title'] = 'Async Restore';
$string['async_toggle_desc'] = 'Make course restores asyncronous.';
```

Append directly below:

```php

// Admin navigation labels (used by the admin sidebar / externalpage display names).
$string['nav_settings'] = 'Global settings';
$string['nav_list']     = 'Restore Courses';

// Tab strip labels (rendered by amd/src/admin-tabs-lazy.js across admin pages).
$string['tab_settings'] = 'Global settings';
$string['tab_list']     = 'Restore Courses';
```

### 8.5 New files

#### 8.5.a `blocks/simple_restore/amd/src/admin-tabs-lazy.js` (NEW)

Full content. Copied from `blocks/backadel/amd/src/admin-tabs-lazy.js` with `@module` docblock and `Templates.render` template ID adapted to `block_simple_restore`.

```javascript
/**
 * Render a Bootstrap 4 nav-tabs bar across block_simple_restore admin pages.
 *
 * Adapted from block_backadel/admin-tabs-lazy. Self-aborts when the
 * current page is not a block_simple_restore admin page.
 *
 * @module     block_simple_restore/admin-tabs-lazy
 * @copyright  2026 Louisiana State University
 * @author     David Castro <davidcastro00@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import $ from 'jquery';
import Templates from 'core/templates';

const renderTabs = (links, strings, validPlaces) => {
    const activeClasses = [];
    let isValid = false;

    for (let i = 0; i < validPlaces.length; i++) {
        if ($(validPlaces[i]).length > 0) {
            isValid = true;
            activeClasses[i] = 'active';
        } else {
            activeClasses[i] = '';
        }
    }

    if (!isValid) {
        return;
    }

    const nav = [];
    for (let i = 0; i < links.length; i++) {
        nav.push({
            link: links[i],
            string: strings[i],
            activeClass: activeClasses[i],
        });
    }

    Templates.render('block_simple_restore/admin_tabs', {nav: nav}).then((html) => {
        const $anchor = $('#region-main .secondary-navigation').first();
        if ($anchor.length) {
            $anchor.after(html);
        } else {
            $('#region-main').prepend(html);
        }
        return null;
    }).catch((error) => {
        window.console.error(error);
    });
};

export const init = (links, strings, validPlaces) => {
    renderTabs(links, strings, validPlaces);
};
```

#### 8.5.b `blocks/simple_restore/templates/admin_tabs.mustache` (NEW)

Full content. Copied from `blocks/backadel/templates/admin_tabs.mustache` with template name in docblock and CSS class updated to `block-simple-restore-admin-tabs`.

```mustache
{{!
    @template block_simple_restore/admin_tabs

    Tab strip rendered across block_simple_restore admin pages.

    Context schema:
    {
      "nav": [
        { "link": "https://...", "string": "Global settings", "activeClass": "active" },
        { "link": "https://...", "string": "Restore Courses", "activeClass": "" }
      ]
    }

    @copyright 2008 onwards Louisiana State University
    @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
}}
<ul class="nav nav-tabs block-simple-restore-admin-tabs mb-3" role="tablist">
    {{#nav}}
        <li class="nav-item" role="presentation">
            <a class="nav-link {{activeClass}}" href="{{link}}" role="tab"
               {{#activeClass}}aria-current="page"{{/activeClass}}>
                {{string}}
            </a>
        </li>
    {{/nav}}
</ul>
```

#### 8.5.c Build artifacts (generated by grunt, must be committed)

- `blocks/simple_restore/amd/build/admin-tabs-lazy.min.js`
- `blocks/simple_restore/amd/build/admin-tabs-lazy.min.js.map`

Generated by the grunt command in §12 step 6. Do not hand-edit.

### 8.6 `blocks/simple_restore/version.php`

Bump version so the admin tree cache invalidates cleanly on the next admin index hit.

```
=== REMOVE (line 26) ===
$plugin->version = 2018111600;
=== ADD ===
$plugin->version = 2026050800;
```

No `requires` change. No upgrade step needed — the only persisted state is the existing `simple_restore/*` config keys, which are unaffected.

### 8.7 No changes needed

- `db/access.php` — capabilities are already correct (`canrestore` at course level, `canrestorearchive` at system level). Admin users hitting `list.php?id=1` already trigger the system-context capability check.
- `block_simple_restore.php` — block class is course-side only, no impact.
- `lib.php` / `list_form.php` / `settingslib.php` — helpers, untouched.

---

## 9. Risks and edge cases

| Risk | Likelihood | Mitigation |
|---|---|---|
| Teacher in course gets accidentally bumped into admin layout because they happen to have `moodle/course:create` system-wide | Medium | The narrow gate `$adminmode = $archivemode \|\| $courseid == SITEID` (§4.4) is keyed on `$courseid`, not on capability. A teacher viewing a real course always has a real `$courseid`, never SITEID, so they always take the teacher branch. |
| `admin_externalpage_setup()` requires the user to be a logged-in admin and will redirect / 403 a teacher who somehow lands on `?id=1` | Low | This is correct behaviour. The teacher block content never produces an `id=1` link (see `block_simple_restore.php:175-189` where `$course->id` is used). The only `id=1` paths are the new admin externalpage URL and archive-server mode (which gates on `:canrestorearchive`). |
| The "have grades" early-return branch (`list.php:75-91`) currently does manual `$PAGE` setup and dies. After our change, when in admin mode, those manual calls are skipped. Does the page still render? | Low | Yes: `admin_externalpage_setup()` already set URL / context / title / page layout. The branch's `$OUTPUT->header()`, heading, notification, continue button, footer all work. We just lose the redundant navbar add — fine, breadcrumbs come from the admin tree. |
| `restore.php` is reached via redirect from `list.php` and continues to use course context. After admin navigates to `list.php?id=1`, picks an archive backup, gets new course created, gets redirected to `restore.php?contextid=<newcourse>` — does the breadcrumb back to "Restore Courses" still work? | Low | The redirect URL has `contextid=<newcoursectx>`, which puts `restore.php` in course-layout. Breadcrumb will show the new course, not the admin tree. That's acceptable — admin is now operating inside a real course context. The "Cancel" link on the confirm page (`restore.php:97-100`) goes to `list.php?id=<newcourseid>` — also fine (it lands in teacher mode for that course, no tab strip). If desired we could rewrite Cancel to go back to the admin externalpage URL when archive mode, but it's not a blocker. |
| Backadel catalogue Restore button still works (it uses Simple Restore as a passthrough) | Low | The path is `/blocks/simple_restore/restore.php?id=<id>&file=<…>` (see `restore.php:28-48`); doesn't involve `list.php` at all. Untouched by this change. |
| Admin tree cache holds the old (un-categorized) page registration | Low | Bump `version.php` (§8.6) and `purge all caches`. Standard. |
| The grunt build artifact for `admin-tabs-lazy.min.js` has a different hash than backadel's even if the source is byte-identical | Low | Grunt's source-map references the module name in the comment header; they differ between blocks. That's expected. Do not try to share build artifacts. |

---

## 10. Step ordering

Roughly this sequence; each step is a single small commit:

1. **Lang strings** (`§8.4`) — add `nav_*` and `tab_*`. Self-contained, verifiable by `grep`.
2. **AMD + Mustache copy** (`§8.5`). Run grunt. Commit src + build together.
3. **`settings.php` rewrite** (`§8.1`). Verify in browser:
   - `/admin/settings.php?section=blocksettingsimple_restore` shows under a "Simple Restore" category.
   - Sidebar shows "Global settings" + "Restore Courses".
   - Tab strip appears on the settings page only (Restore Courses link works but list.php still does its old thing — that's the next step).
4. **`list.php` admin-mode gate** (`§8.2`). Verify:
   - Teacher in course → no tab strip, course breadcrumb intact, restore flow unchanged.
   - Admin clicking sidebar "Restore Courses" → tab strip visible, `Site administration → Plugins → Blocks → Simple Restore → Restore Courses` breadcrumb, admin filter form renders.
   - Archive-server mode (toggle the setting) → tab strip visible, system-context capability gate.
5. **Version bump + purge caches** (§8.6).
6. **Behat smoke** (optional but cheap): one scenario clicking from the admin tree to `list.php` and back.

---

## 11. Complexity estimate

**M.** Small in absolute LOC (settings.php gets ~40 new lines, list.php gets a ~10-line gate plus three `if (!$adminmode)` wraps, lang/amd/mustache are template work). The thinking happens around the dual-use behaviour of `list.php` — but §4 nails it down to a one-line predicate (`$archivemode || $courseid == SITEID`), which is mechanical to implement and easy to verify.

Time estimate: ~2 hours including grunt, manual smoke testing both audiences, and a cache purge.

---

## 12. Implementation checklist

Ordered, copy-pasteable run-list. Tick boxes as you go. All paths are relative to repo root unless stated otherwise; all shell commands are run from the repo root.

- [ ] **1. Branch from `develop`** (per memory: never branch from `feature/fourOneMerge`):
      ```bash
      git checkout develop && git pull && git checkout -b MD-2189-simple-restore-admin-nav
      ```
- [ ] **2. Add lang strings** — append the block from §8.4 to `blocks/simple_restore/lang/en/block_simple_restore.php`. Verify:
      ```bash
      grep -E "nav_settings|nav_list|tab_settings|tab_list" blocks/simple_restore/lang/en/block_simple_restore.php
      ```
- [ ] **3. Create `templates/` directory**:
      ```bash
      mkdir -p blocks/simple_restore/templates
      ```
- [ ] **4. Create Mustache template** — write the file from §8.5.b at `blocks/simple_restore/templates/admin_tabs.mustache`.
- [ ] **5. Create AMD source** — write the file from §8.5.a at `blocks/simple_restore/amd/src/admin-tabs-lazy.js`.
- [ ] **6. Run grunt to compile AMD + minify**:
      ```bash
      ./run-docker-exec.sh "cd /var/www/html/blocks/simple_restore && npx grunt amd"
      ```
      Verify both build artifacts exist:
      ```bash
      ls blocks/simple_restore/amd/build/admin-tabs-lazy.min.js \
         blocks/simple_restore/amd/build/admin-tabs-lazy.min.js.map
      ```
- [ ] **7. Replace `settings.php`** — overwrite `blocks/simple_restore/settings.php` with the full content from §8.1.
- [ ] **8. Patch `list.php`** — apply diffs §8.2.a, §8.2.b, §8.2.c to `blocks/simple_restore/list.php` in order.
- [ ] **9. Bump version** — apply the line change from §8.6 to `blocks/simple_restore/version.php`.
- [ ] **10. Purge caches**:
      ```bash
      ./run-docker-exec.sh "cd /var/www/html && php admin/cli/purge_caches.php"
      ```
- [ ] **11. Run upgrade (picks up version bump + admin tree refresh)**:
      ```bash
      ./run-docker-exec.sh "cd /var/www/html && php admin/cli/upgrade.php --non-interactive"
      ```
- [ ] **12. Browser smoke test — admin path:**
      - Visit `/admin/search.php?query=simple_restore` (or just expand `Site administration → Plugins → Blocks`); confirm a collapsible **Simple Restore** category with two children: **Global settings**, **Restore Courses**.
      - Click **Global settings** → URL `/admin/settings.php?section=blocksettingsimple_restore`; confirm the Bootstrap nav-tabs strip appears at the top of `#region-main` with **Global settings** marked active.
      - Click the **Restore Courses** tab → URL `/blocks/simple_restore/list.php?id=1`; confirm tab strip persists with **Restore Courses** marked active, breadcrumb is `Site administration → Plugins → Blocks → Simple Restore → Restore Courses`, admin shortname filter form renders.
- [ ] **13. Browser smoke test — teacher path:**
      - Log in as an editing-teacher in a real course.
      - From the course page, open the Simple Restore block menu → click into the list.
      - Confirm URL is `/blocks/simple_restore/list.php?id=<courseid>&restore_to=…`, **no** tab strip appears, course breadcrumb is intact, and the restore flow still loads as before.
- [ ] **14. (If archive mode is in scope)** Toggle `simple_restore/is_archive_server` on, repeat the admin smoke test, confirm system-context capability gate (`block/simple_restore:canrestorearchive`) and tab strip both work.
- [ ] **15. Commit in logical chunks** (per §10 ordering):
      - lang
      - AMD src + mustache + build artifacts
      - settings.php
      - list.php
      - version.php
- [ ] **16. Push branch** and open PR against `develop`.
