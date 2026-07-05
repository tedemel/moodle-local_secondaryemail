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

/**
 * Unit tests for the verification class.
 *
 * @package    local_secondaryemail
 * @category   test
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_secondaryemail\verification
 */
final class verification_test extends \advanced_testcase {
    /**
     * Test that starting verification sets the correct preferences.
     *
     * @covers ::start
     */
    public function test_start_verification_sets_preferences(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $email = 'test@example.com';

        $this->set_profile_field_value($field->id, $user->id, $email);

        verification::start($user->id, $email);

        $this->assertNotEmpty(get_user_preferences(verification::PREF_TOKEN, '', $user->id));
        $this->assertNotEmpty(get_user_preferences(verification::PREF_TOKEN_TIME, '', $user->id));
        $this->assertEquals($email, get_user_preferences(verification::PREF_PENDING, '', $user->id));
    }

    /**
     * Test that starting verification with invalid email clears preferences.
     *
     * @covers ::start
     */
    public function test_start_verification_with_invalid_email_clears_state(): void {
        $this->resetAfterTest();

        $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();

        // Set some existing preferences.
        set_user_preference(verification::PREF_VERIFIED, 'old@example.com', $user->id);
        set_user_preference(verification::PREF_TOKEN, 'oldtoken', $user->id);

        verification::start($user->id, 'invalid-email');

        $this->assertEmpty(get_user_preferences(verification::PREF_TOKEN, '', $user->id));
        $this->assertEmpty(get_user_preferences(verification::PREF_VERIFIED, '', $user->id));
    }

    /**
     * Test successful confirmation of a pending email.
     *
     * @covers ::confirm
     */
    public function test_confirm_with_valid_token(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $email = 'pending@example.com';
        $token = random_string(32);

        $this->set_profile_field_value($field->id, $user->id, $email);
        set_user_preference(verification::PREF_PENDING, $email, $user->id);
        set_user_preference(verification::PREF_TOKEN, $token, $user->id);
        set_user_preference(verification::PREF_TOKEN_TIME, time(), $user->id);

        $failure = null;
        $result = verification::confirm($user->id, $token, $failure);

        $this->assertTrue($result);
        $this->assertNull($failure);
        $this->assertEquals($email, get_user_preferences(verification::PREF_VERIFIED, '', $user->id));
        $this->assertEmpty(get_user_preferences(verification::PREF_TOKEN, '', $user->id));
        $this->assertEmpty(get_user_preferences(verification::PREF_PENDING, '', $user->id));
    }

    /**
     * Test confirmation with invalid token fails.
     *
     * @covers ::confirm
     */
    public function test_confirm_with_invalid_token_fails(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $email = 'pending@example.com';

        $this->set_profile_field_value($field->id, $user->id, $email);
        set_user_preference(verification::PREF_PENDING, $email, $user->id);
        set_user_preference(verification::PREF_TOKEN, 'correcttoken', $user->id);
        set_user_preference(verification::PREF_TOKEN_TIME, time(), $user->id);

        $failure = null;
        $result = verification::confirm($user->id, 'wrongtoken', $failure);

        $this->assertFalse($result);
        $this->assertEquals('invalid', $failure);
    }

    /**
     * Test confirmation with expired token fails.
     *
     * @covers ::confirm
     */
    public function test_confirm_with_expired_token_fails(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $email = 'pending@example.com';
        $token = random_string(32);

        $this->set_profile_field_value($field->id, $user->id, $email);
        set_user_preference(verification::PREF_PENDING, $email, $user->id);
        set_user_preference(verification::PREF_TOKEN, $token, $user->id);
        // Set token time to 25 hours ago (default expiry is 24 hours).
        set_user_preference(verification::PREF_TOKEN_TIME, time() - (25 * HOURSECS), $user->id);

        $failure = null;
        $result = verification::confirm($user->id, $token, $failure);

        $this->assertFalse($result);
        $this->assertEquals('expired', $failure);
    }

    /**
     * Test getting verified addresses returns correct data.
     *
     * @covers ::get_verified_addresses
     */
    public function test_get_verified_addresses_returns_verified_email(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $email = 'verified@example.com';

        $this->set_profile_field_value($field->id, $user->id, $email);
        set_user_preference(verification::PREF_VERIFIED, $email, $user->id);

        $addresses = verification::get_verified_addresses($user->id);

        $this->assertCount(1, $addresses);
        $this->assertEquals($email, $addresses[0]);
    }

    /**
     * Test getting verified addresses returns empty when disabled.
     *
     * @covers ::get_verified_addresses
     */
    public function test_get_verified_addresses_returns_empty_when_disabled(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $email = 'verified@example.com';

        $this->set_profile_field_value($field->id, $user->id, $email);
        set_user_preference(verification::PREF_VERIFIED, $email, $user->id);
        set_user_preference(verification::PREF_DISABLED, 1, $user->id);

        $addresses = verification::get_verified_addresses($user->id);

        $this->assertEmpty($addresses);
    }

    /**
     * Test is_verified returns true for verified email.
     *
     * @covers ::is_verified
     */
    public function test_is_verified_returns_true_for_verified_email(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $email = 'verified@example.com';

        $this->set_profile_field_value($field->id, $user->id, $email);
        set_user_preference(verification::PREF_VERIFIED, $email, $user->id);

        $this->assertTrue(verification::is_verified($user->id, $email));
        $this->assertTrue(verification::is_verified($user->id));
    }

    /**
     * Test is_verified returns false for different email.
     *
     * @covers ::is_verified
     */
    public function test_is_verified_returns_false_for_different_email(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();

        $this->set_profile_field_value($field->id, $user->id, 'current@example.com');
        set_user_preference(verification::PREF_VERIFIED, 'old@example.com', $user->id);

        $this->assertFalse(verification::is_verified($user->id, 'current@example.com'));
        $this->assertFalse(verification::is_verified($user->id));
    }

    /**
     * Test is_pending returns correct status.
     *
     * @covers ::is_pending
     */
    public function test_is_pending_returns_correct_status(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $email = 'pending@example.com';

        set_user_preference(verification::PREF_PENDING, $email, $user->id);

        $this->assertTrue(verification::is_pending($user->id, $email));
        $this->assertFalse(verification::is_pending($user->id, 'other@example.com'));
    }

    /**
     * Test pending token matches current value.
     *
     * @covers ::has_active_token
     */
    public function test_pending_token_matches_current_value(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();

        $this->set_profile_field_value($field->id, $user->id, 'pending@example.com');
        set_user_preference(verification::PREF_PENDING, 'pending@example.com', $user->id);
        set_user_preference(verification::PREF_TOKEN, random_string(32), $user->id);
        set_user_preference(verification::PREF_TOKEN_TIME, time(), $user->id);

        $this->assertTrue(verification::has_active_token($user->id, 'pending@example.com'));

        $this->set_profile_field_value($field->id, $user->id, 'new@example.com');
        $this->assertFalse(verification::has_active_token($user->id, 'new@example.com'));
        $this->assertTrue(verification::has_active_token($user->id, 'pending@example.com'));
    }

    /**
     * Test has_active_token returns false for expired token.
     *
     * @covers ::has_active_token
     */
    public function test_has_active_token_returns_false_for_expired(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $email = 'pending@example.com';

        set_user_preference(verification::PREF_PENDING, $email, $user->id);
        set_user_preference(verification::PREF_TOKEN, random_string(32), $user->id);
        set_user_preference(verification::PREF_TOKEN_TIME, time() - (25 * HOURSECS), $user->id);

        $this->assertFalse(verification::has_active_token($user->id, $email));
    }

    /**
     * Test clear removes all verification preferences.
     *
     * @covers ::clear
     */
    public function test_clear_removes_all_preferences(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        set_user_preference(verification::PREF_VERIFIED, 'test@example.com', $user->id);
        set_user_preference(verification::PREF_TOKEN, 'token', $user->id);
        set_user_preference(verification::PREF_TOKEN_TIME, time(), $user->id);
        set_user_preference(verification::PREF_PENDING, 'test@example.com', $user->id);

        verification::clear($user->id);

        $this->assertEmpty(get_user_preferences(verification::PREF_VERIFIED, '', $user->id));
        $this->assertEmpty(get_user_preferences(verification::PREF_TOKEN, '', $user->id));
        $this->assertEmpty(get_user_preferences(verification::PREF_TOKEN_TIME, '', $user->id));
        $this->assertEmpty(get_user_preferences(verification::PREF_PENDING, '', $user->id));
    }

    /**
     * Test delete_secondary_email clears all state.
     *
     * @covers ::delete_secondary_email
     */
    public function test_delete_secondary_email_clears_state(): void {
        global $DB;

        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();

        $this->set_profile_field_value($field->id, $user->id, 'verified@example.com');
        set_user_preference(verification::PREF_VERIFIED, 'verified@example.com', $user->id);
        set_user_preference(verification::PREF_PENDING, 'verified@example.com', $user->id);
        set_user_preference(verification::PREF_TOKEN, random_string(32), $user->id);
        set_user_preference(verification::PREF_TOKEN_TIME, time(), $user->id);
        set_user_preference(verification::PREF_DISABLED, 1, $user->id);

        verification::delete_secondary_email($user->id);

        $this->assertFalse($DB->record_exists('user_info_data', [
            'userid' => $user->id,
            'fieldid' => $field->id,
        ]));
        $this->assertSame('', (string) get_user_preferences(verification::PREF_VERIFIED, '', $user->id));
        $this->assertSame('', (string) get_user_preferences(verification::PREF_PENDING, '', $user->id));
        $this->assertSame('', (string) get_user_preferences(verification::PREF_TOKEN, '', $user->id));
        $this->assertSame('', (string) get_user_preferences(verification::PREF_TOKEN_TIME, '', $user->id));
        $this->assertSame(0, (int) get_user_preferences(verification::PREF_DISABLED, 0, $user->id));
    }

    /**
     * Test disable and enable functionality.
     *
     * @covers ::disable
     * @covers ::enable
     * @covers ::is_disabled
     */
    public function test_disable_and_enable(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $this->assertFalse(verification::is_disabled($user->id));

        verification::disable($user->id);
        $this->assertTrue(verification::is_disabled($user->id));

        verification::enable($user->id);
        $this->assertFalse(verification::is_disabled($user->id));
    }

    /**
     * Test get_secondary_email_value returns correct value.
     *
     * @covers ::get_secondary_email_value
     */
    public function test_get_secondary_email_value(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();

        $this->assertEquals('', verification::get_secondary_email_value($user->id));

        $this->set_profile_field_value($field->id, $user->id, 'test@example.com');
        $this->assertEquals('test@example.com', verification::get_secondary_email_value($user->id));
    }

    /**
     * Test is_email_acceptable validates emails correctly.
     *
     * @covers ::is_email_acceptable
     */
    public function test_is_email_acceptable(): void {
        $this->resetAfterTest();

        $this->assertTrue(verification::is_email_acceptable('valid@example.com'));
        $this->assertTrue(verification::is_email_acceptable('user.name@domain.org'));

        $this->assertFalse(verification::is_email_acceptable(''));
        $this->assertFalse(verification::is_email_acceptable('invalid'));
        $this->assertFalse(verification::is_email_acceptable('no-at-sign'));
        $this->assertFalse(verification::is_email_acceptable('@nodomain'));
    }

    /**
     * Test confirmation fails when profile field value has changed.
     *
     * @covers ::confirm
     */
    public function test_confirm_fails_when_email_changed(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $originalemail = 'original@example.com';
        $token = random_string(32);

        $this->set_profile_field_value($field->id, $user->id, $originalemail);
        set_user_preference(verification::PREF_PENDING, $originalemail, $user->id);
        set_user_preference(verification::PREF_TOKEN, $token, $user->id);
        set_user_preference(verification::PREF_TOKEN_TIME, time(), $user->id);

        // Change the email in the profile field.
        $this->set_profile_field_value($field->id, $user->id, 'changed@example.com');

        $failure = null;
        $result = verification::confirm($user->id, $token, $failure);

        $this->assertFalse($result);
        $this->assertEquals('invalid', $failure);
    }

    /**
     * Test get_verified_addresses returns empty when email changed.
     *
     * @covers ::get_verified_addresses
     */
    public function test_get_verified_addresses_empty_when_email_changed(): void {
        $this->resetAfterTest();

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();
        $email = 'verified@example.com';

        $this->set_profile_field_value($field->id, $user->id, $email);
        set_user_preference(verification::PREF_VERIFIED, $email, $user->id);

        // Verify it works initially.
        $this->assertCount(1, verification::get_verified_addresses($user->id));

        // Change the email.
        $this->set_profile_field_value($field->id, $user->id, 'newaddress@example.com');

        // Should now be empty since verified email doesn't match current.
        $this->assertEmpty(verification::get_verified_addresses($user->id));
    }

    /**
     * Test cached field id is refreshed when profile field is recreated.
     *
     * @covers ::reset_caches
     * @covers ::get_secondary_email_value
     */
    public function test_reset_caches(): void {
        $this->resetAfterTest();

        global $CFG, $DB;

        $field = $this->ensure_secondary_email_field();
        $user = $this->getDataGenerator()->create_user();

        $this->set_profile_field_value($field->id, $user->id, 'first@example.com');
        $this->assertSame('first@example.com', verification::get_secondary_email_value($user->id));

        $DB->delete_records('user_info_data', ['userid' => $user->id, 'fieldid' => $field->id]);
        $DB->delete_records('user_info_field', ['id' => $field->id]);

        require_once($CFG->dirroot . '/local/secondaryemail/db/install.php');
        local_secondaryemail_ensure_profile_field();

        $newfield = $DB->get_record('user_info_field', ['shortname' => 'secondaryemail'], '*', MUST_EXIST);
        $this->set_profile_field_value($newfield->id, $user->id, 'second@example.com');

        // Cache should self-heal after field recreation.
        $this->assertSame('second@example.com', verification::get_secondary_email_value($user->id));

        // Explicit cache reset must also keep behaviour correct.
        verification::reset_caches();
        $this->assertSame('second@example.com', verification::get_secondary_email_value($user->id));
    }

    /**
     * Test relationship tag get/set/clear.
     *
     * @covers ::set_relationship
     * @covers ::get_relationship
     * @covers ::clear_relationship
     */
    public function test_relationship_tags(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $this->assertSame('', verification::get_relationship($user->id));

        verification::set_relationship($user->id, 'mother');
        $this->assertSame('mother', verification::get_relationship($user->id));

        verification::clear_relationship($user->id);
        $this->assertSame('', verification::get_relationship($user->id));
    }

    /**
     * Test get_available_tags returns parsed tags.
     *
     * @covers ::get_available_tags
     */
    public function test_get_available_tags(): void {
        $this->resetAfterTest();

        set_config('relationshiptags', "mother\nfather\ncustom", 'local_secondaryemail');
        $tags = verification::get_available_tags();

        $this->assertArrayHasKey('mother', $tags);
        $this->assertArrayHasKey('father', $tags);
        $this->assertArrayHasKey('custom', $tags);
    }

    /**
     * Test get_tag_display_name uses lang strings.
     *
     * @covers ::get_tag_display_name
     */
    public function test_get_tag_display_name_with_lang_string(): void {
        $this->resetAfterTest();

        $this->assertSame('Mother', verification::get_tag_display_name('mother'));
    }

    /**
     * Test get_tag_display_name falls back to ucfirst.
     *
     * @covers ::get_tag_display_name
     */
    public function test_get_tag_display_name_fallback(): void {
        $this->resetAfterTest();

        $this->assertSame('Customtag', verification::get_tag_display_name('customtag'));
    }

    /**
     * Test get_enabled_provider_ids returns empty for unset config.
     *
     * @covers ::get_enabled_provider_ids
     */
    public function test_get_enabled_provider_ids_empty(): void {
        $this->resetAfterTest();

        unset_config('enabledproviders', 'local_secondaryemail');
        $this->assertSame([], verification::get_enabled_provider_ids());
    }

    /**
     * Test get_enabled_provider_ids parses config list.
     *
     * @covers ::get_enabled_provider_ids
     */
    public function test_get_enabled_provider_ids_parsed(): void {
        $this->resetAfterTest();

        set_config('enabledproviders', 'mod_forum/posts, moodle/instantmessage ,', 'local_secondaryemail');
        $this->assertSame(['mod_forum/posts', 'moodle/instantmessage'], verification::get_enabled_provider_ids());
    }

    /**
     * Test get_grouped_message_providers returns grouped data.
     *
     * @covers ::get_grouped_message_providers
     */
    public function test_get_grouped_message_providers(): void {
        $this->resetAfterTest();

        $grouped = verification::get_grouped_message_providers();
        $this->assertNotEmpty($grouped);
        $this->assertIsArray($grouped);
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
     * Set a profile field value for a user.
     *
     * @param int $fieldid The field ID.
     * @param int $userid The user ID.
     * @param string $value The value to set.
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
}
