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
 * Hook callbacks for secondary email plugin.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_secondaryemail;

/**
 * Hook callbacks class.
 */
class hook_callbacks {
    /**
     * Block access to protected profile field/category edit pages.
     *
     * @param \core\hook\after_config $hook The hook object.
     */
    public static function block_profile_field_access(\core\hook\after_config $hook): void {
        global $DB, $CFG;

        // Read and sanitise request context explicitly.
        $scriptname = (string) (filter_input(INPUT_SERVER, 'SCRIPT_NAME') ?? '');
        $action = clean_param((string) (filter_input(INPUT_GET, 'action') ?? ''), PARAM_ALPHANUMEXT);
        $id = (int) clean_param((string) (filter_input(INPUT_GET, 'id') ?? 0), PARAM_INT);

        // Only check on profile field management pages.
        if (strpos($scriptname, '/user/profile/index.php') === false) {
            return;
        }

        if (empty($action) || empty($id)) {
            return;
        }

        // Only block specific actions.
        $blockedactions = ['editfield', 'deletefield', 'editcategory', 'deletecategory', 'movecategory'];
        if (!in_array($action, $blockedactions, true)) {
            return;
        }

        // Get our field info.
        $field = $DB->get_record('user_info_field', ['shortname' => 'secondaryemail']);
        if (!$field) {
            return;
        }

        $fieldid = (int) $field->id;
        $categoryid = (int) $field->categoryid;
        $shouldblock = false;

        // Check if trying to edit/delete our protected field.
        if (in_array($action, ['editfield', 'deletefield'], true) && $id === $fieldid) {
            $shouldblock = true;
        }

        // Check if trying to edit/delete our protected category.
        if (in_array($action, ['editcategory', 'deletecategory', 'movecategory'], true) && $id === $categoryid) {
            $shouldblock = true;
        }

        if ($shouldblock) {
            // Use Moodle redirect API when available, with a safe fallback.
            if (function_exists('redirect')) {
                redirect(new \moodle_url('/user/profile/index.php'));
            }

            header('Location: ' . $CFG->wwwroot . '/user/profile/index.php');
            exit;
        }
    }

    /**
     * Inject field protection AMD module on the profile field management list page.
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook
     */
    public static function inject_field_protection_assets(
        \core\hook\output\before_standard_head_html_generation $hook
    ): void {
        global $PAGE, $DB;

        $field = $DB->get_record('user_info_field', ['shortname' => 'secondaryemail']);
        if (!$field) {
            return;
        }

        $fieldid = (int) $field->id;
        $categoryid = (int) $field->categoryid;
        if (!$fieldid) {
            return;
        }

        $pagepath = '';
        try {
            $pagepath = $PAGE->url->get_path();
        } catch (\Throwable $e) {
            return;
        }

        if (strpos($pagepath, '/user/profile/index.php') === false) {
            return;
        }

        // Only add JS on the list page (not on action pages).
        $action = optional_param('action', '', PARAM_ALPHANUMEXT);
        if (!empty($action)) {
            return;
        }

        $params = [
            'fieldId' => $fieldid,
            'categoryId' => $categoryid,
            'strings' => [
                'lockedLabel' => get_string('secondaryemaillockedlabel', 'local_secondaryemail'),
                'fieldLockedMsg' => get_string('secondaryemailfieldlocked', 'local_secondaryemail'),
                'categoryLockedMsg' => get_string('secondaryemailcategorylocked', 'local_secondaryemail'),
                'fieldWarning' => get_string('secondaryemailfieldwarning', 'local_secondaryemail'),
                'categoryWarning' => get_string('secondaryemailcategorywarning', 'local_secondaryemail'),
                'lockedByPlugin' => get_string('fieldlockedbyplugin', 'local_secondaryemail'),
                'categoryName' => get_string('profilecategory', 'local_secondaryemail'),
            ],
        ];

        $PAGE->requires->js_call_amd('local_secondaryemail/fieldprotection', 'init', [$params]);
    }
}
