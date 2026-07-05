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
 * External function to get secondary email status.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_status extends external_api {
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
     * Get secondary email status.
     *
     * @param int $userid User ID (0 for current user)
     * @return array Status information
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
        $isverified = \local_secondaryemail\verification::is_verified($params['userid']);
        $ispending = !empty($email) && \local_secondaryemail\verification::is_pending($params['userid'], $email);
        $hasactivetoken = !empty($email) && \local_secondaryemail\verification::has_active_token($params['userid'], $email);
        $isdisabled = \local_secondaryemail\verification::is_disabled($params['userid']);

        return [
            'email' => $email,
            'isverified' => $isverified,
            'ispending' => $ispending,
            'hasactivetoken' => $hasactivetoken,
            'isdisabled' => $isdisabled,
            'canresend' => !empty($email) && !$isverified && !$isdisabled,
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'email' => new external_value(PARAM_EMAIL, 'Secondary email address', VALUE_OPTIONAL),
            'isverified' => new external_value(PARAM_BOOL, 'Whether the email is verified'),
            'ispending' => new external_value(PARAM_BOOL, 'Whether confirmation is pending'),
            'hasactivetoken' => new external_value(PARAM_BOOL, 'Whether there is an active token'),
            'isdisabled' => new external_value(PARAM_BOOL, 'Whether sending is disabled'),
            'canresend' => new external_value(PARAM_BOOL, 'Whether resend is possible'),
        ]);
    }
}
