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
 * Behat steps for secondary email plugin.
 *
 * @package    local_secondaryemail
 * @category   test
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.MoodleInternal.MoodleInternalGlobalState
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat step definitions for the secondary email plugin.
 *
 * @package    local_secondaryemail
 * @category   test
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_secondaryemail extends behat_base {
    /**
     * Set the secondary email value for a user.
     *
     * @Given /^the secondary email for user "(?P<username>[^"]+)" is set to "(?P<email>[^"]+)"$/
     * @param string $username
     * @param string $email
     */
    public function set_secondary_email_for_user(string $username, string $email): void {
        global $DB;

        $field = $this->ensure_secondary_email_field();
        $user = $this->get_user_by_username($username);

        if ($data = $DB->get_record('user_info_data', ['userid' => $user->id, 'fieldid' => $field->id])) {
            $data->data = $email;
            $DB->update_record('user_info_data', $data);
            return;
        }

        $DB->insert_record('user_info_data', (object) [
            'userid' => $user->id,
            'fieldid' => $field->id,
            'data' => $email,
        ]);
    }

    /**
     * Mark a user's secondary email as verified.
     *
     * @Given /^the secondary email for user "(?P<username>[^"]+)" is verified$/
     * @param string $username
     */
    public function mark_secondary_email_verified(string $username): void {
        $user = $this->get_user_by_username($username);
        $email = \local_secondaryemail\verification::get_secondary_email_value($user->id);
        if ($email === '') {
            throw new \moodle_exception('secondaryemailmissing', 'local_secondaryemail');
        }

        set_user_preference(\local_secondaryemail\verification::PREF_VERIFIED, $email, $user->id);
        unset_user_preference(\local_secondaryemail\verification::PREF_PENDING, $user->id);
        unset_user_preference(\local_secondaryemail\verification::PREF_TOKEN, $user->id);
        unset_user_preference(\local_secondaryemail\verification::PREF_TOKEN_TIME, $user->id);
    }

    /**
     * Mark a user's secondary email as pending with an active token.
     *
     * @Given /^the secondary email for user "(?P<username>[^"]+)" has a pending confirmation$/
     * @param string $username
     */
    public function mark_secondary_email_pending(string $username): void {
        $user = $this->get_user_by_username($username);
        $email = \local_secondaryemail\verification::get_secondary_email_value($user->id);
        if ($email === '') {
            throw new \moodle_exception('secondaryemailmissing', 'local_secondaryemail');
        }

        set_user_preference(\local_secondaryemail\verification::PREF_PENDING, $email, $user->id);
        set_user_preference(\local_secondaryemail\verification::PREF_TOKEN, random_string(32), $user->id);
        set_user_preference(\local_secondaryemail\verification::PREF_TOKEN_TIME, time(), $user->id);
        unset_user_preference(\local_secondaryemail\verification::PREF_VERIFIED, $user->id);
    }

    /**
     * Block a user's secondary email sending.
     *
     * @Given /^the secondary email for user "(?P<username>[^"]+)" is blocked$/
     * @param string $username
     */
    public function mark_secondary_email_blocked(string $username): void {
        $user = $this->get_user_by_username($username);
        set_user_preference(\local_secondaryemail\verification::PREF_DISABLED, 1, $user->id);
    }

    /**
     * Ensure the secondary email profile field exists.
     *
     * @return \stdClass The field record.
     */
    private function ensure_secondary_email_field(): \stdClass {
        global $CFG, $DB;

        if ($field = $DB->get_record('user_info_field', ['shortname' => 'secondaryemail'])) {
            return $field;
        }

        require_once($CFG->dirroot . '/local/secondaryemail/db/install.php');
        local_secondaryemail_ensure_profile_field();

        return $DB->get_record('user_info_field', ['shortname' => 'secondaryemail'], '*', MUST_EXIST);
    }

    /**
     * Fetch a user by username.
     *
     * @param string $username The username to look up.
     * @return \stdClass The user record.
     */
    private function get_user_by_username(string $username): \stdClass {
        global $DB;

        return $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
    }
}
