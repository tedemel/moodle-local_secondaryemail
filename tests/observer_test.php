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

namespace local_secondaryemail;

use core_message\tests\helper as message_test_helper;

/**
 * Unit tests for observer callbacks.
 *
 * @package    local_secondaryemail
 * @category   test
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_secondaryemail\observer
 */
final class observer_test extends \advanced_testcase {
    /**
     * A new secondary email value starts a fresh verification.
     *
     * @covers ::user_updated
     */
    public function test_user_updated_triggers_verification_for_new_email(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $email = 'new@example.com';

        $this->set_profile_field_value($field->id, $user->id, $email);

        $event = \core\event\user_updated::create_from_userid($user->id);
        observer::user_updated($event);

        $this->assertSame($email, get_user_preferences(verification::PREF_PENDING, '', $user->id));
        $this->assertNotEmpty(get_user_preferences(verification::PREF_TOKEN, '', $user->id));
    }

    /**
     * Clearing the secondary email removes all verification preferences.
     *
     * @covers ::user_updated
     */
    public function test_user_updated_clears_verification_for_empty_email(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();

        set_user_preference(verification::PREF_VERIFIED, 'old@example.com', $user->id);
        set_user_preference(verification::PREF_TOKEN, 'token', $user->id);
        set_user_preference(verification::PREF_PENDING, 'old@example.com', $user->id);

        $this->set_profile_field_value($field->id, $user->id, '');

        $event = \core\event\user_updated::create_from_userid($user->id);
        observer::user_updated($event);

        $this->assertSame('', (string) get_user_preferences(verification::PREF_VERIFIED, '', $user->id));
        $this->assertSame('', (string) get_user_preferences(verification::PREF_TOKEN, '', $user->id));
        $this->assertSame('', (string) get_user_preferences(verification::PREF_PENDING, '', $user->id));
    }

    /**
     * An already verified address is not re-verified on profile update.
     *
     * @covers ::user_updated
     */
    public function test_user_updated_does_not_reverify_already_verified(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $email = 'verified@example.com';

        $this->set_profile_field_value($field->id, $user->id, $email);
        set_user_preference(verification::PREF_VERIFIED, $email, $user->id);

        $event = \core\event\user_updated::create_from_userid($user->id);
        observer::user_updated($event);

        $this->assertSame('', (string) get_user_preferences(verification::PREF_PENDING, '', $user->id));
    }

    /**
     * An active pending verification is not restarted on profile update.
     *
     * @covers ::user_updated
     */
    public function test_user_updated_does_not_restart_active_pending(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $email = 'pending@example.com';
        $token = random_string(32);

        $this->set_profile_field_value($field->id, $user->id, $email);
        set_user_preference(verification::PREF_PENDING, $email, $user->id);
        set_user_preference(verification::PREF_TOKEN, $token, $user->id);
        set_user_preference(verification::PREF_TOKEN_TIME, time(), $user->id);

        $event = \core\event\user_updated::create_from_userid($user->id);
        observer::user_updated($event);

        $this->assertSame($token, get_user_preferences(verification::PREF_TOKEN, '', $user->id));
    }

    /**
     * A newly created user with a secondary email starts verification.
     *
     * @covers ::user_created
     */
    public function test_user_created_with_secondary_email_starts_verification(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $email = 'created@example.com';

        $this->set_profile_field_value($field->id, $user->id, $email);

        $event = \core\event\user_created::create_from_userid($user->id);
        observer::user_created($event);

        $this->assertSame($email, get_user_preferences(verification::PREF_PENDING, '', $user->id));
    }

    /**
     * Whitelisted notifications are forwarded to the verified address.
     *
     * @covers ::notification_sent
     */
    public function test_notification_sent_forwards_to_verified_address(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $sender = $this->getDataGenerator()->create_user();
        $recipient = $this->getDataGenerator()->create_user();
        $email = 'secondary@example.com';

        $this->set_profile_field_value($field->id, $recipient->id, $email);
        set_user_preference(verification::PREF_VERIFIED, $email, $recipient->id);

        set_config('enabledproviders', 'moodle/instantmessage', 'local_secondaryemail');

        $notificationid = message_test_helper::send_fake_message($sender, $recipient, 'Hello world!', 1);
        $this->update_notification_record($notificationid, 'moodle', 'instantmessage');

        $event = \core\event\notification_sent::create_from_ids($sender->id, $recipient->id, $notificationid, SITEID);

        $sink = $this->redirectEmails();
        observer::notification_sent($event);

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertSame($email, $messages[0]->to);
    }

    /**
     * Notifications are not forwarded to unverified addresses.
     *
     * @covers ::notification_sent
     */
    public function test_notification_sent_skips_unverified_address(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $sender = $this->getDataGenerator()->create_user();
        $recipient = $this->getDataGenerator()->create_user();
        $email = 'secondary@example.com';

        $this->set_profile_field_value($field->id, $recipient->id, $email);
        set_user_preference(verification::PREF_PENDING, $email, $recipient->id);

        set_config('enabledproviders', 'moodle/instantmessage', 'local_secondaryemail');

        $notificationid = message_test_helper::send_fake_message($sender, $recipient, 'Hello world!', 1);
        $this->update_notification_record($notificationid, 'moodle', 'instantmessage');
        $event = \core\event\notification_sent::create_from_ids($sender->id, $recipient->id, $notificationid, SITEID);

        $sink = $this->redirectEmails();
        observer::notification_sent($event);

        $this->assertCount(0, $sink->get_messages());
    }

    /**
     * Notifications are not forwarded for suspended users.
     *
     * @covers ::notification_sent
     */
    public function test_notification_sent_skips_disabled_user(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $sender = $this->getDataGenerator()->create_user();
        $recipient = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $email = 'secondary@example.com';

        $this->set_profile_field_value($field->id, $recipient->id, $email);
        set_user_preference(verification::PREF_VERIFIED, $email, $recipient->id);

        set_config('enabledproviders', 'moodle/instantmessage', 'local_secondaryemail');

        $notificationid = message_test_helper::send_fake_message($sender, $recipient, 'Hello world!', 1);
        $this->update_notification_record($notificationid, 'moodle', 'instantmessage');
        $event = \core\event\notification_sent::create_from_ids($sender->id, $recipient->id, $notificationid, SITEID);

        $sink = $this->redirectEmails();
        observer::notification_sent($event);

        $this->assertCount(0, $sink->get_messages());
    }

    /**
     * Providers outside the admin whitelist are not forwarded.
     *
     * @covers ::notification_sent
     */
    public function test_notification_sent_respects_admin_whitelist(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $sender = $this->getDataGenerator()->create_user();
        $recipient = $this->getDataGenerator()->create_user();
        $email = 'secondary@example.com';

        $this->set_profile_field_value($field->id, $recipient->id, $email);
        set_user_preference(verification::PREF_VERIFIED, $email, $recipient->id);

        set_config('enabledproviders', 'mod_forum/posts', 'local_secondaryemail');

        $notificationid = message_test_helper::send_fake_message($sender, $recipient, 'Hello world!', 1);
        $this->update_notification_record($notificationid, 'moodle', 'instantmessage');
        $event = \core\event\notification_sent::create_from_ids($sender->id, $recipient->id, $notificationid, SITEID);

        $sink = $this->redirectEmails();
        observer::notification_sent($event);

        $this->assertCount(0, $sink->get_messages());
    }

    /**
     * User-disabled providers are not forwarded.
     *
     * @covers ::notification_sent
     */
    public function test_notification_sent_respects_user_exclusion(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $sender = $this->getDataGenerator()->create_user();
        $recipient = $this->getDataGenerator()->create_user();
        $email = 'secondary@example.com';

        $this->set_profile_field_value($field->id, $recipient->id, $email);
        set_user_preference(verification::PREF_VERIFIED, $email, $recipient->id);

        set_config('enabledproviders', 'moodle/instantmessage', 'local_secondaryemail');
        set_config('allowuserexclusions', 1, 'local_secondaryemail');
        set_user_preference(verification::PREF_DISABLED_PROVIDERS, json_encode(['moodle/instantmessage']), $recipient->id);

        $notificationid = message_test_helper::send_fake_message($sender, $recipient, 'Hello world!', 1);
        $this->update_notification_record($notificationid, 'moodle', 'instantmessage');
        $event = \core\event\notification_sent::create_from_ids($sender->id, $recipient->id, $notificationid, SITEID);

        $sink = $this->redirectEmails();
        observer::notification_sent($event);

        $this->assertCount(0, $sink->get_messages());
    }

    /**
     * No copy is sent when secondary equals the primary address.
     *
     * @covers ::notification_sent
     */
    public function test_notification_sent_skips_same_primary_secondary(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $sender = $this->getDataGenerator()->create_user();
        $recipient = $this->getDataGenerator()->create_user(['email' => 'same@example.com']);

        $this->set_profile_field_value($field->id, $recipient->id, 'same@example.com');
        set_user_preference(verification::PREF_VERIFIED, 'same@example.com', $recipient->id);

        set_config('enabledproviders', 'moodle/instantmessage', 'local_secondaryemail');

        $notificationid = message_test_helper::send_fake_message($sender, $recipient, 'Hello world!', 1);
        $this->update_notification_record($notificationid, 'moodle', 'instantmessage');
        $event = \core\event\notification_sent::create_from_ids($sender->id, $recipient->id, $notificationid, SITEID);

        $sink = $this->redirectEmails();
        observer::notification_sent($event);

        $this->assertCount(0, $sink->get_messages());
    }

    /**
     * Deleting the protected profile field recreates it.
     *
     * @covers ::profile_field_deleted
     */
    public function test_profile_field_deleted_recreates_field(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();

        $event = \core\event\user_info_field_deleted::create_from_field($field);
        observer::profile_field_deleted($event);

        $this->assertNotFalse($this->get_field_record());
    }

    /**
     * Changing protected field settings restores them.
     *
     * @covers ::profile_field_updated
     */
    public function test_profile_field_updated_restores_settings(): void {
        $this->resetAfterTest();

        global $DB;
        $field = $this->ensure_secondary_email_field();

        $DB->update_record('user_info_field', (object) [
            'id' => $field->id,
            'name' => 'Changed',
            'visible' => 0,
            'datatype' => 'textarea',
        ]);

        $updated = $DB->get_record('user_info_field', ['id' => $field->id]);
        $event = \core\event\user_info_field_updated::create_from_field($updated);
        observer::profile_field_updated($event);

        $restored = $DB->get_record('user_info_field', ['id' => $field->id]);
        $this->assertSame(get_string('secondaryemailfieldname', 'local_secondaryemail'), $restored->name);
        $this->assertSame(3, (int) $restored->visible);
        $this->assertSame('text', $restored->datatype);
    }

    /**
     * Deleting the protected category recreates the field setup.
     *
     * @covers ::profile_category_deleted
     */
    public function test_profile_category_deleted_recreates(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $category = $this->get_category_record($field->categoryid);

        $event = \core\event\user_info_category_deleted::create_from_category($category);
        observer::profile_category_deleted($event);

        $this->assertNotFalse($this->get_field_record());
    }

    /**
     * Renaming the protected category restores its name.
     *
     * @covers ::profile_category_updated
     */
    public function test_profile_category_updated_restores_name(): void {
        $this->resetAfterTest();

        global $DB;
        $field = $this->ensure_secondary_email_field();

        $DB->update_record('user_info_category', (object) [
            'id' => $field->categoryid,
            'name' => 'Renamed category',
        ]);

        $category = $DB->get_record('user_info_category', ['id' => $field->categoryid]);
        $event = \core\event\user_info_category_updated::create_from_category($category);
        observer::profile_category_updated($event);

        $updated = $DB->get_record('user_info_category', ['id' => $field->categoryid]);
        $this->assertSame(get_string('profilecategory', 'local_secondaryemail'), $updated->name);
    }

    /**
     * Create the secondary email profile field if it does not exist yet.
     *
     * @return \stdClass The user_info_field record.
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
     * Store a value for the secondary email profile field of a user.
     *
     * @param int $fieldid
     * @param int $userid
     * @param string $value
     */
    private function set_profile_field_value(int $fieldid, int $userid, string $value): void {
        global $DB;

        if ($data = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $fieldid])) {
            $data->data = $value;
            $DB->update_record('user_info_data', $data);
            return;
        }

        $DB->insert_record('user_info_data', (object) [
            'userid' => $userid,
            'fieldid' => $fieldid,
            'data' => $value,
        ]);
    }

    /**
     * Rewrite component and eventtype of a stored notification.
     *
     * @param int $notificationid
     * @param string $component
     * @param string $eventtype
     */
    private function update_notification_record(int $notificationid, string $component, string $eventtype): void {
        global $DB;

        $record = $DB->get_record('notifications', ['id' => $notificationid], '*', MUST_EXIST);
        $record->component = $component;
        $record->eventtype = $eventtype;
        if (!property_exists($record, 'fullmessagehtml')) {
            $record->fullmessagehtml = '';
        }
        $DB->update_record('notifications', $record);
    }

    /**
     * Fetch the secondary email field record.
     *
     * @return \stdClass|false
     */
    private function get_field_record() {
        global $DB;
        return $DB->get_record('user_info_field', ['shortname' => 'secondaryemail']);
    }

    /**
     * Fetch a profile category record.
     *
     * @param int $categoryid
     * @return \stdClass
     */
    private function get_category_record(int $categoryid): \stdClass {
        global $DB;
        return $DB->get_record('user_info_category', ['id' => $categoryid], '*', MUST_EXIST);
    }
}
