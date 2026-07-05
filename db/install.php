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
 * Installation code for the secondary email local plugin.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Install the plugin.
 *
 * @return bool
 */
function xmldb_local_secondaryemail_install() {
    local_secondaryemail_ensure_profile_field();
    return true;
}

/**
 * Ensure the secondary email profile field exists.
 */
function local_secondaryemail_ensure_profile_field(): void {
    global $CFG, $DB;

    // Load profile libs so PROFILE_VISIBLE_TEACHERS is defined during install.
    require_once($CFG->dirroot . '/user/profile/lib.php');
    require_once($CFG->dirroot . '/user/profile/definelib.php');
    require_once($CFG->dirroot . '/user/profile/field/text/define.class.php');

    $categoryname = get_string('profilecategory', 'local_secondaryemail');
    $category = $DB->get_record('user_info_category', ['name' => $categoryname]);
    if (!$category) {
        $sortorder = (int) $DB->get_field_sql('SELECT COALESCE(MAX(sortorder), 0) FROM {user_info_category}');
        $categoryid = $DB->insert_record('user_info_category', (object) [
            'name' => $categoryname,
            'sortorder' => $sortorder + 1,
        ]);
    } else {
        $categoryid = $category->id;
    }

    // PROFILE_VISIBLE_TEACHERS (3) = visible to user, teachers/trainers, and admin.
    $requiredvisibility = PROFILE_VISIBLE_TEACHERS;

    $field = $DB->get_record('user_info_field', ['shortname' => 'secondaryemail']);
    if (!$field) {
        $profileclass = new \profile_define_text();
        $data = (object) [
            'shortname' => 'secondaryemail',
            'name' => get_string('secondaryemailfieldname', 'local_secondaryemail'),
            'datatype' => 'text',
            'description' => get_string('secondaryemailfielddesc', 'local_secondaryemail'),
            'descriptionformat' => 1,
            'categoryid' => $categoryid,
            'signup' => 0,
            'forceunique' => 0,
            'visible' => $requiredvisibility,
            'locked' => 0,
            'param1' => 30,
            'param2' => 254,
        ];
        $profileclass->define_save($data);
        return;
    }

    $updates = [];
    if ($field->datatype !== 'text') {
        $updates['datatype'] = 'text';
    }
    if ((int) $field->categoryid !== (int) $categoryid) {
        $updates['categoryid'] = $categoryid;
    }
    if ((int) $field->visible !== $requiredvisibility) {
        $updates['visible'] = $requiredvisibility;
    }
    if (!empty($updates)) {
        $updates['id'] = $field->id;
        $DB->update_record('user_info_field', (object) $updates);
    }
}
