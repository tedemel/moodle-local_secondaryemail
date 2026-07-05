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
 * Upgrade steps for local_secondaryemail.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_local_secondaryemail_upgrade(int $oldversion): bool {
    global $DB;

    if ($oldversion < 2026020601) {
        $field = $DB->get_record('user_info_field', ['shortname' => 'secondaryemail']);
        if ($field) {
            $records = $DB->get_records('user_info_data', ['fieldid' => $field->id]);
            foreach ($records as $record) {
                $value = trim((string) $record->data);
                if ($value === '') {
                    continue;
                }
                if (!validate_email($value) || email_is_not_allowed($value)) {
                    $record->data = '';
                    $DB->update_record('user_info_data', $record);

                    \local_secondaryemail\verification::clear($record->userid);
                    unset_user_preference(\local_secondaryemail\verification::PREF_DISABLED, $record->userid);
                    unset_user_preference(\local_secondaryemail\verification::PREF_RELATIONSHIP, $record->userid);
                }
            }
        }
        upgrade_plugin_savepoint(true, 2026020601, 'local', 'secondaryemail');
    }

    return true;
}
