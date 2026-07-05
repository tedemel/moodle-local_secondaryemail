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
 * Uninstall script for local_secondaryemail.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Uninstall the plugin and clean up data.
 *
 * @return bool
 */
function xmldb_local_secondaryemail_uninstall() {
    global $DB;

    // Remove all user preferences created by this plugin.
    $preferences = [
        \local_secondaryemail\verification::PREF_VERIFIED,
        \local_secondaryemail\verification::PREF_TOKEN,
        \local_secondaryemail\verification::PREF_TOKEN_TIME,
        \local_secondaryemail\verification::PREF_PENDING,
        \local_secondaryemail\verification::PREF_DISABLED,
        \local_secondaryemail\verification::PREF_DISABLED_PROVIDERS,
        \local_secondaryemail\verification::PREF_RELATIONSHIP,
    ];

    foreach ($preferences as $preference) {
        $DB->delete_records('user_preferences', ['name' => $preference]);
    }

    // Note: We do NOT delete the profile field or category here,
    // as the admin may want to keep the data or use it with another plugin.
    // If complete cleanup is desired, admin can manually delete the profile field.

    return true;
}
