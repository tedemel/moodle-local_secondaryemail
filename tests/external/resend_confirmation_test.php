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
use externallib_advanced_testcase;
use local_secondaryemail\verification;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for external resend_confirmation.
 *
 * @package    local_secondaryemail
 * @category   external
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_secondaryemail\external\resend_confirmation
 */
final class resend_confirmation_test extends externallib_advanced_testcase {
    public function test_resend_confirmation_missing_email(): void {
        $this->resetAfterTest();

        $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $result = resend_confirmation::execute(0);
        $result = external_api::clean_returnvalue(resend_confirmation::execute_returns(), $result);

        $this->assertFalse($result['success']);
    }

    public function test_resend_confirmation_already_verified(): void {
        $this->resetAfterTest();

        $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $email = 'verified@example.com';
        $this->set_profile_field_value($user->id, $email);
        set_user_preference(verification::PREF_VERIFIED, $email, $user->id);

        $result = resend_confirmation::execute(0);
        $result = external_api::clean_returnvalue(resend_confirmation::execute_returns(), $result);

        $this->assertFalse($result['success']);
    }

    public function test_resend_confirmation_invalid_email(): void {
        $this->resetAfterTest();

        $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->set_profile_field_value($user->id, 'invalid');

        $result = resend_confirmation::execute(0);
        $result = external_api::clean_returnvalue(resend_confirmation::execute_returns(), $result);

        $this->assertFalse($result['success']);
    }

    public function test_resend_confirmation_disabled(): void {
        $this->resetAfterTest();

        $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->set_profile_field_value($user->id, 'disabled@example.com');
        set_user_preference(verification::PREF_DISABLED, 1, $user->id);

        $result = resend_confirmation::execute(0);
        $result = external_api::clean_returnvalue(resend_confirmation::execute_returns(), $result);

        $this->assertFalse($result['success']);
    }

    public function test_resend_confirmation_starts_verification(): void {
        $this->resetAfterTest();

        $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->set_profile_field_value($user->id, 'new@example.com');

        $sink = $this->redirectEmails();
        $result = resend_confirmation::execute(0);
        $result = external_api::clean_returnvalue(resend_confirmation::execute_returns(), $result);

        $this->assertTrue($result['success']);
        $this->assertSame('new@example.com', get_user_preferences(verification::PREF_PENDING, '', $user->id));
        $this->assertNotEmpty($sink->get_messages());
    }

    public function test_resend_confirmation_for_other_user_requires_capability(): void {
        $this->resetAfterTest();

        $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        resend_confirmation::execute($other->id);
    }

    /**
     * Create the secondary email profile field if it does not exist yet.
     */
    private function ensure_secondary_email_field(): void {
        global $CFG, $DB;

        if ($DB->record_exists('user_info_field', ['shortname' => 'secondaryemail'])) {
            return;
        }

        require_once($CFG->dirroot . '/local/secondaryemail/db/install.php');
        local_secondaryemail_ensure_profile_field();
    }

    /**
     * Store a value for the secondary email profile field of a user.
     *
     * @param int $userid
     * @param string $value
     */
    private function set_profile_field_value(int $userid, string $value): void {
        global $DB;

        $field = $DB->get_record('user_info_field', ['shortname' => 'secondaryemail'], '*', MUST_EXIST);
        if ($data = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $field->id])) {
            $data->data = $value;
            $DB->update_record('user_info_data', $data);
            return;
        }

        $DB->insert_record('user_info_data', (object) [
            'userid' => $userid,
            'fieldid' => $field->id,
            'data' => $value,
        ]);
    }
}
