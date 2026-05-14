# Bug-004 review — Simple Restore admin nav revamp

**Reviewer:** Claude (Opus 4.7)
**Date:** 2026-05-08
**Verdict:** **PASS** — implementation matches the spec in `bug-004-simple-restore-admin-nav.md`. No fixes applied.

---

## What I checked

### 1. `blocks/simple_restore/settings.php` (full new file)

| Requirement | Result |
|---|---|
| `defined('MOODLE_INTERNAL') \|\| die` guard | OK (line 24) |
| `$hassiteconfig` early return | OK (lines 31–33) |
| `$pluginname` constant | OK (line 35, `'block_simple_restore'`) |
| `admin_category` registered under `blocksettings` with id `block_simple_restore_category` | OK (lines 38–41) |
| `$settings->visiblename` set BEFORE `$ADMIN->add($category, $settings)` | OK (line 46 → 47) |
| All original settings preserved inside `if ($ADMIN->fulltree)`: `generalsettings`, `highlevelsettings`, `$modules`, `$producer`, `$flatmap`, `$coursesettings`, `$srk`, `$srs`, `$itersettings`, `is_archive_server`, headings (`general`, `course`, `module`), per-module checkbox loop, async settings (`async_title`, `async_toggle`) | OK (lines 50–178) |
| `admin_externalpage` `block_simple_restore_list` with URL `/blocks/simple_restore/list.php` and `['id' => SITEID]` | OK (lines 181–185) |
| `js_call_amd` OUTSIDE `$ADMIN->fulltree` and guarded by `!CLI_SCRIPT` | OK (line 189) |
| `$validplaces` selectors match Moodle pagetype derivation: `#page-admin-setting-blocksettingsimple_restore` and `#page-admin-blocks-simple_restore-list` | OK (lines 200–203) |
| `$settings = null` at the very end | OK (line 213) |
| AMD module ref `block_simple_restore/admin-tabs-lazy` | OK (line 205) |

### 2. `blocks/simple_restore/list.php` (three diff sites)

| Requirement | Result |
|---|---|
| `require_once($CFG->libdir . '/adminlib.php')` added | OK (line 27) |
| `$archivemode` computed BEFORE `$adminmode` | OK (line 41 → 46) |
| `$adminmode = $archivemode \|\| $courseid == SITEID` (narrow gate per §4.4) | OK (line 46) |
| Admin branch: `admin_externalpage_setup('block_simple_restore_list', '', ['id' => $courseid, 'restore_to' => $restoreto])` | OK (lines 51–54) |
| Teacher branch still calls `require_login()` | OK (line 57) |
| "Have grades" early-return: manual `$PAGE` calls wrapped in `if (!$adminmode)` | OK (lines 96–101) |
| Main `$PAGE` setup block (set_context / set_course / navbar / set_title / set_heading / set_url) wrapped in `if (!$adminmode)` | OK (lines 152–162) |
| No double-declaration of `$courseid`, `$restoreto`, `$shortname` | OK (each declared once at lines 30, 31, 38) |
| `$archivemode` not used before defined | OK (defined line 41, used line 46+) |
| `$archivemode` reassignment at line 61 is the documented harmless duplicate (per §8.2.a note) | OK — left in place per spec |

### 3. AMD + Mustache

| Requirement | Result |
|---|---|
| `amd/src/admin-tabs-lazy.js` renders `block_simple_restore/admin_tabs` (not backadel) | OK (line 41) |
| `@module` docblock = `block_simple_restore/admin-tabs-lazy` | OK (line 7) |
| `templates/admin_tabs.mustache` CSS class `block-simple-restore-admin-tabs` (not backadel) | OK (line 17) |
| Mustache template name in docblock = `block_simple_restore/admin_tabs` | OK (line 2) |
| Build artifacts present (`amd/build/admin-tabs-lazy.min.js` + `.map`) | OK — verified the minified module references `block_simple_restore/admin_tabs`, not the backadel template |

### 4. Body-ID selectors verified against Moodle pagetype derivation

- Settings: `/admin/settings.php?section=blocksettingsimple_restore` → body id `page-admin-setting-blocksettingsimple_restore` (selector `#page-admin-setting-blocksettingsimple_restore` matches).
- List: `admin_externalpage_setup('block_simple_restore_list')` from `blocks/simple_restore/list.php` → pagetype `admin-blocks-simple_restore-list` → body id `page-admin-blocks-simple_restore-list` (selector `#page-admin-blocks-simple_restore-list` matches). The underscore in `simple_restore` is preserved (not a path separator).

### 5. Lang strings

`blocks/simple_restore/lang/en/block_simple_restore.php` lines 98–104 add exactly:

- `nav_settings` = `Global settings`
- `nav_list` = `Restore Courses`
- `tab_settings` = `Global settings`
- `tab_list` = `Restore Courses`

All four strings are present and the keys match what `settings.php` and the admin externalpage display name reference.

### 6. Version bump

`blocks/simple_restore/version.php` line 26: `$plugin->version = 2026050800;` ✓

### 7. PHP syntax

`php -l` clean across all four PHP files (`settings.php`, `list.php`, `version.php`, `lang/en/block_simple_restore.php`).

---

## Findings / fixes

**None.** No issues found. No edits were made.

The only minor cosmetic observation is that `list.php` line 61 redundantly recomputes `$archivemode` (already set at line 41), but this is explicitly called out as acceptable in §8.2.a of the spec ("harmless to recompute and minimises the diff footprint of step 8.2.b"). Leaving as-is.

---

## Files reviewed

- `/home/homelab/work/lsu/lsuce-moodle/blocks/simple_restore/settings.php`
- `/home/homelab/work/lsu/lsuce-moodle/blocks/simple_restore/list.php`
- `/home/homelab/work/lsu/lsuce-moodle/blocks/simple_restore/lang/en/block_simple_restore.php`
- `/home/homelab/work/lsu/lsuce-moodle/blocks/simple_restore/amd/src/admin-tabs-lazy.js`
- `/home/homelab/work/lsu/lsuce-moodle/blocks/simple_restore/amd/build/admin-tabs-lazy.min.js`
- `/home/homelab/work/lsu/lsuce-moodle/blocks/simple_restore/templates/admin_tabs.mustache`
- `/home/homelab/work/lsu/lsuce-moodle/blocks/simple_restore/version.php`

Reference files compared against:

- `/home/homelab/work/lsu/lsuce-moodle/blocks/backadel/settings.php`
- `/home/homelab/work/lsu/lsuce-moodle/blocks/backadel/amd/src/admin-tabs-lazy.js`
- `/home/homelab/work/lsu/lsuce-moodle/blocks/backadel/templates/admin_tabs.mustache`
