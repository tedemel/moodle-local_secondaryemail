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

namespace local_secondaryemail\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_secondaryemail\verification;

/**
 * Privacy provider tests for local_secondaryemail.
 *
 * @package    local_secondaryemail
 * @category   test
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_secondaryemail\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_get_metadata_returns_all_preferences(): void {
        $collection = provider::get_metadata(new collection('local_secondaryemail'));
        $this->assertCount(7, $collection->get_collection());
    }

    public function test_get_contexts_for_userid_with_data(): void {
        $user = $this->getDataGenerator()->create_user();
        set_user_preference(verification::PREF_VERIFIED, 'verified@example.com', $user->id);

        $contextlist = provider::get_contexts_for_userid($user->id);
        $contexts = $contextlist->get_contexts();

        $this->assertCount(1, $contexts);
        $this->assertEquals(CONTEXT_USER, $contexts[0]->contextlevel);
        $this->assertEquals($user->id, $contexts[0]->instanceid);
    }

    public function test_get_contexts_for_userid_without_data(): void {
        $user = $this->getDataGenerator()->create_user();

        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(0, $contextlist->get_contexts());
    }

    public function test_get_users_in_context_returns_user(): void {
        $user = $this->getDataGenerator()->create_user();
        set_user_preference(verification::PREF_PENDING, 'pending@example.com', $user->id);

        $context = \context_user::instance($user->id);
        $userlist = new userlist($context, 'local_secondaryemail');
        provider::get_users_in_context($userlist);

        $this->assertEquals([$user->id], $userlist->get_userids());
    }

    public function test_get_users_in_context_returns_other_context_empty(): void {
        $systemcontext = \context_system::instance();
        $userlist = new userlist($systemcontext, 'local_secondaryemail');
        provider::get_users_in_context($userlist);

        $this->assertEmpty($userlist->get_userids());
    }

    public function test_export_user_data_includes_verified_and_pending(): void {
        $user = $this->getDataGenerator()->create_user();
        set_user_preference(verification::PREF_VERIFIED, 'verified@example.com', $user->id);
        set_user_preference(verification::PREF_PENDING, 'pending@example.com', $user->id);

        $contextlist = provider::get_contexts_for_userid($user->id);
        $approved = new approved_contextlist($user, 'local_secondaryemail', $contextlist->get_contextids());
        provider::export_user_data($approved);

        $context = \context_user::instance($user->id);
        $data = writer::with_context($context)->get_data([get_string('pluginname', 'local_secondaryemail')]);
        $this->assertSame('verified@example.com', $data->verified_email);
        $this->assertSame('pending@example.com', $data->pending_email);
    }

    public function test_export_user_preferences_masks_token(): void {
        $user = $this->getDataGenerator()->create_user();
        set_user_preference(verification::PREF_TOKEN, 'abcd1234', $user->id);

        provider::export_user_preferences($user->id);

        $prefs = writer::with_context(\context_system::instance())
            ->get_user_preferences('local_secondaryemail');
        $this->assertSame('abcd****', $prefs->{verification::PREF_TOKEN}->value);
    }

    public function test_delete_data_for_user_clears_all(): void {
        $user = $this->getDataGenerator()->create_user();
        set_user_preference(verification::PREF_VERIFIED, 'verified@example.com', $user->id);
        set_user_preference(verification::PREF_DISABLED, 1, $user->id);

        $contextlist = provider::get_contexts_for_userid($user->id);
        $approved = new approved_contextlist($user, 'local_secondaryemail', $contextlist->get_contextids());
        provider::delete_data_for_user($approved);

        $this->assertSame('', (string) get_user_preferences(verification::PREF_VERIFIED, '', $user->id));
        $this->assertSame(0, (int) get_user_preferences(verification::PREF_DISABLED, 0, $user->id));
    }

    public function test_delete_data_for_all_users_in_context(): void {
        $user = $this->getDataGenerator()->create_user();
        set_user_preference(verification::PREF_VERIFIED, 'verified@example.com', $user->id);

        $context = \context_user::instance($user->id);
        provider::delete_data_for_all_users_in_context($context);

        $this->assertSame('', (string) get_user_preferences(verification::PREF_VERIFIED, '', $user->id));
    }

    public function test_delete_data_for_users(): void {
        $user = $this->getDataGenerator()->create_user();
        set_user_preference(verification::PREF_PENDING, 'pending@example.com', $user->id);

        $context = \context_user::instance($user->id);
        $userlist = new approved_userlist($context, 'local_secondaryemail', [$user->id]);
        provider::delete_data_for_users($userlist);

        $this->assertSame('', (string) get_user_preferences(verification::PREF_PENDING, '', $user->id));
    }
}
