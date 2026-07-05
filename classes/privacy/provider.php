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
 * Privacy provider for the secondary email local plugin.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_secondaryemail\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider implementation for GDPR compliance.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Returns meta data about this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference(
            \local_secondaryemail\verification::PREF_VERIFIED,
            'privacy:metadata:prefverified'
        );
        $collection->add_user_preference(
            \local_secondaryemail\verification::PREF_TOKEN,
            'privacy:metadata:preftoken'
        );
        $collection->add_user_preference(
            \local_secondaryemail\verification::PREF_TOKEN_TIME,
            'privacy:metadata:preftokentime'
        );
        $collection->add_user_preference(
            \local_secondaryemail\verification::PREF_PENDING,
            'privacy:metadata:prefpending'
        );
        $collection->add_user_preference(
            \local_secondaryemail\verification::PREF_DISABLED,
            'privacy:metadata:prefdisabled'
        );
        $collection->add_user_preference(
            \local_secondaryemail\verification::PREF_DISABLED_PROVIDERS,
            'privacy:metadata:prefdisabledproviders'
        );
        $collection->add_user_preference(
            \local_secondaryemail\verification::PREF_RELATIONSHIP,
            'privacy:metadata:prefrelationship'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Check if user has any secondary email preferences.
        $hasdata = false;
        $preferences = [
            \local_secondaryemail\verification::PREF_VERIFIED,
            \local_secondaryemail\verification::PREF_TOKEN,
            \local_secondaryemail\verification::PREF_TOKEN_TIME,
            \local_secondaryemail\verification::PREF_PENDING,
            \local_secondaryemail\verification::PREF_DISABLED,
            \local_secondaryemail\verification::PREF_DISABLED_PROVIDERS,
            \local_secondaryemail\verification::PREF_RELATIONSHIP,
        ];

        foreach ($preferences as $pref) {
            if (get_user_preferences($pref, null, $userid) !== null) {
                $hasdata = true;
                break;
            }
        }

        if ($hasdata) {
            $contextlist->add_user_context($userid);
        }

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        // Check if this user has any secondary email preferences.
        $userid = $context->instanceid;
        $preferences = [
            \local_secondaryemail\verification::PREF_VERIFIED,
            \local_secondaryemail\verification::PREF_TOKEN,
            \local_secondaryemail\verification::PREF_TOKEN_TIME,
            \local_secondaryemail\verification::PREF_PENDING,
            \local_secondaryemail\verification::PREF_DISABLED,
            \local_secondaryemail\verification::PREF_DISABLED_PROVIDERS,
            \local_secondaryemail\verification::PREF_RELATIONSHIP,
        ];

        foreach ($preferences as $pref) {
            if (get_user_preferences($pref, null, $userid) !== null) {
                $userlist->add_user($userid);
                return;
            }
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_USER || $context->instanceid !== $userid) {
                continue;
            }

            $data = [];

            $verified = get_user_preferences(\local_secondaryemail\verification::PREF_VERIFIED, null, $userid);
            if ($verified !== null) {
                $data['verified_email'] = $verified;
            }

            $pending = get_user_preferences(\local_secondaryemail\verification::PREF_PENDING, null, $userid);
            if ($pending !== null) {
                $data['pending_email'] = $pending;
            }

            $disabled = get_user_preferences(\local_secondaryemail\verification::PREF_DISABLED, null, $userid);
            if ($disabled !== null) {
                $data['sending_disabled'] = (bool) $disabled;
            }

            $relationship = get_user_preferences(\local_secondaryemail\verification::PREF_RELATIONSHIP, null, $userid);
            if ($relationship !== null) {
                $data['relationship'] = $relationship;
            }

            if (!empty($data)) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_secondaryemail')],
                    (object) $data
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }

        self::delete_user_data($context->instanceid);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_USER && $context->instanceid === $userid) {
                self::delete_user_data($userid);
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();

        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }

        $userids = $userlist->get_userids();
        foreach ($userids as $userid) {
            if ($context->instanceid === $userid) {
                self::delete_user_data($userid);
            }
        }
    }

    /**
     * Export all user preferences for the plugin.
     *
     * @param int $userid
     */
    public static function export_user_preferences(int $userid): void {
        $verified = get_user_preferences(\local_secondaryemail\verification::PREF_VERIFIED, null, $userid);
        if (!empty($verified)) {
            writer::export_user_preference(
                'local_secondaryemail',
                \local_secondaryemail\verification::PREF_VERIFIED,
                $verified,
                get_string('privacy:metadata:prefverified', 'local_secondaryemail')
            );
        }

        $pending = get_user_preferences(\local_secondaryemail\verification::PREF_PENDING, null, $userid);
        if (!empty($pending)) {
            writer::export_user_preference(
                'local_secondaryemail',
                \local_secondaryemail\verification::PREF_PENDING,
                $pending,
                get_string('privacy:metadata:prefpending', 'local_secondaryemail')
            );
        }

        $token = get_user_preferences(\local_secondaryemail\verification::PREF_TOKEN, null, $userid);
        if (!empty($token)) {
            $maskedtoken = substr($token, 0, 4);
            if (strlen($token) > 4) {
                $maskedtoken .= str_repeat('*', strlen($token) - 4);
            }
            writer::export_user_preference(
                'local_secondaryemail',
                \local_secondaryemail\verification::PREF_TOKEN,
                $maskedtoken,
                get_string('privacy:metadata:preftoken', 'local_secondaryemail')
            );
        }

        $tokentime = get_user_preferences(\local_secondaryemail\verification::PREF_TOKEN_TIME, null, $userid);
        if (!empty($tokentime)) {
            writer::export_user_preference(
                'local_secondaryemail',
                \local_secondaryemail\verification::PREF_TOKEN_TIME,
                $tokentime,
                get_string('privacy:metadata:preftokentime', 'local_secondaryemail')
            );
        }

        $disabled = get_user_preferences(\local_secondaryemail\verification::PREF_DISABLED, null, $userid);
        if (!empty($disabled)) {
            writer::export_user_preference(
                'local_secondaryemail',
                \local_secondaryemail\verification::PREF_DISABLED,
                $disabled,
                get_string('privacy:metadata:prefdisabled', 'local_secondaryemail')
            );
        }

        $disabledproviders = get_user_preferences(\local_secondaryemail\verification::PREF_DISABLED_PROVIDERS, null, $userid);
        if (!empty($disabledproviders)) {
            writer::export_user_preference(
                'local_secondaryemail',
                \local_secondaryemail\verification::PREF_DISABLED_PROVIDERS,
                $disabledproviders,
                get_string('privacy:metadata:prefdisabledproviders', 'local_secondaryemail')
            );
        }

        $relationship = get_user_preferences(\local_secondaryemail\verification::PREF_RELATIONSHIP, null, $userid);
        if (!empty($relationship)) {
            writer::export_user_preference(
                'local_secondaryemail',
                \local_secondaryemail\verification::PREF_RELATIONSHIP,
                $relationship,
                get_string('privacy:metadata:prefrelationship', 'local_secondaryemail')
            );
        }
    }

    /**
     * Delete all secondary email data for a user.
     *
     * @param int $userid
     */
    protected static function delete_user_data(int $userid): void {
        // Use the verification class to properly delete all data.
        \local_secondaryemail\verification::delete_secondary_email($userid);
    }
}
