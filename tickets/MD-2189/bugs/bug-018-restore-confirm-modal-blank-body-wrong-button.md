# BUG-018 — Simple Restore confirm modal: blank body, "Save changes" button label

**Severity:** Medium  
**Found by:** Manual review of e2e screenshot 33-teacher-restore-modal.png (2026-05-12)  
**Status:** Open

---

## Symptom

The restore confirmation modal on `blocks/simple_restore/list.php` (triggered by clicking a Restore button) shows:

- **Title:** "Confirm Restore" ✅
- **Body:** empty — no confirmation message shown ❌
- **Submit button:** "Save changes" ❌ (should say "Restore")
- **Cancel button:** "Cancel" ✅

## Root cause

`blocks/simple_restore/classes/form/restore_confirm_form.php::definition()` adds only hidden fields — no visible static HTML element with the confirmation message. `core_form/modalform` renders the form body from `definition()`, so nothing is shown.

`blocks/simple_restore/amd/src/restore_actions.js` passes `modalConfig: { title }` only — no `saveButtonText`, so Moodle's default "Save changes" is used.

## Fix

### 1. `blocks/simple_restore/classes/form/restore_confirm_form.php::definition()`

Add a static HTML element BEFORE the hidden fields:

```php
$mform->addElement('html', \html_writer::tag('p',
    get_string('restore_confirm_body', 'block_simple_restore'),
    ['class' => 'mt-2']
));
```

The lang string `restore_confirm_body` already exists:
`'You are about to overwrite this course with the selected backup. Proceed?'`

### 2. `blocks/simple_restore/amd/src/restore_actions.js`

In the `modalConfig` object, add `saveButtonText`:

```js
modalConfig: {
    title: title,
    saveButtonText: await getString('restore_confirm_save', 'block_simple_restore'),
},
```

### 3. `blocks/simple_restore/lang/en/block_simple_restore.php`

Add:
```php
$string['restore_confirm_save'] = 'Restore';
```

### 4. Rebuild AMD

```bash
grunt amd --root=blocks/simple_restore
```

## Acceptance criteria

- Modal body shows the confirmation message text
- Submit button label reads "Restore" (not "Save changes")
- Cancel button still reads "Cancel"
- e2e: `test_teacher_simple_restore()` restore modal check still passes
