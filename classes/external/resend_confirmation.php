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

namespace local_secondaryemail\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External function to resend confirmation email.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resend_confirmation extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID (0 for current user)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Resend confirmation email.
     *
     * @param int $userid User ID (0 for current user)
     * @return array Result information
     */
    public static function execute(int $userid = 0): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['userid' => $userid]);

        if ($params['userid'] === 0) {
            $params['userid'] = (int) $USER->id;
        }

        $context = \context_user::instance($params['userid']);
        self::validate_context($context);

        // Check capability. $USER->id may be a string depending on context, so cast before comparing.
        if ($params['userid'] !== (int) $USER->id) {
            require_capability('local/secondaryemail:manage', \context_system::instance());
        }

        $email = \local_secondaryemail\verification::get_secondary_email_value($params['userid']);

        if (empty($email)) {
            return [
                'success' => false,
                'message' => get_string('secondaryemailmissing', 'local_secondaryemail'),
            ];
        }

        if (\local_secondaryemail\verification::is_verified($params['userid'], $email)) {
            return [
                'success' => false,
                'message' => get_string('secondaryemailalreadyverified', 'local_secondaryemail'),
            ];
        }

        if (!\local_secondaryemail\verification::is_email_acceptable($email)) {
            return [
                'success' => false,
                'message' => get_string('secondaryemailinvalid', 'local_secondaryemail'),
            ];
        }

        if (\local_secondaryemail\verification::is_disabled($params['userid'])) {
            return [
                'success' => false,
                'message' => get_string('secondaryemailblocked', 'local_secondaryemail'),
            ];
        }

        \local_secondaryemail\verification::start($params['userid'], $email);

        return [
            'success' => true,
            'message' => get_string('secondaryemailconfirmationsent', 'local_secondaryemail'),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation succeeded'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
        ]);
    }
}
