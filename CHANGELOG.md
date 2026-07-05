# Changelog

All notable changes to `local_secondaryemail` are documented in this file.

## 2.3.6 (2026-07-05)

Pre-release hardening for the first public GitHub release.

- Fixed: external functions `get_status` and `resend_confirmation` rejected
  requests for the **own** user when the userid was passed explicitly
  (strict int/string comparison against `$USER->id`).
- Fixed: `observer::profile_field_updated()` could fail with an undefined
  `PROFILE_VISIBLE_TEACHERS` constant when the event fired outside the
  profile administration pages (CLI, web services).
- Added: required `@template` documentation block in
  `templates/report_email_cell.mustache`.
- Removed: site-specific non-ASCII language key `relationshipgeschäftlich`.
- Coding style: full Moodle CodeSniffer cleanup (0 errors, 0 warnings),
  sorted language files, docblocks for all test helpers.
- Added: README, LICENSE, CHANGELOG and GitHub Actions CI workflow.

## 2.3.5 (2026-02-13)

- Synced AMD build artifacts (`amd/build/fieldprotection.min.js` + map) with
  `amd/src/fieldprotection.js`.
- Consistent Bootstrap badge class usage across source, minified build and
  source map.

## 2.3.4 (2026-02-13)

- Stabilised the secondary email profile-field ID cache in
  `classes/verification.php`; the cache now self-heals when field IDs change
  (e.g. after reset/recreate in long-running processes).
- Updated `tests/verification_test.php` for the self-healing cache behaviour.

## 2.3.3 (2026-02-12)

- Migrated the legacy `before_standard_html_head` callback to the hook
  `\core\hook\output\before_standard_head_html_generation` (registered in
  `db/hooks.php`).
- Hardened the `after_config` callback: sanitised request reads via
  `clean_param()`, Moodle `redirect()` API with fallback, strict
  `in_array(..., true)` checks.

## 2.3.2 (2026-02-12)

- Capability consistency: report actions and the edit wrapper both require
  `local/secondaryemail:manage` **and** `moodle/user:update`.
- Theme compatibility: warning badge colours now use Bootstrap CSS variables
  with fallbacks.
- Removed unused local variables in output/navigation code.

## 2.3.1 (2026-02-12)

- Badge/status colours now work on Moodle 4.5 (`badge-*`) and Moodle 5+
  (`text-bg-*`) alike.

## 2.2 (2026-02-06)

- Refactor to Moodle 4.5+/5+ standards: AMD module for profile field
  protection, output renderable + renderer + Mustache template for the report
  column, moodleform-based preferences page, hook system via `db/hooks.php`.
- Privacy export masks verification tokens.
- Expanded PHPUnit and Behat coverage.

Earlier versions (≤ 2.1) were internal releases without published notes.
