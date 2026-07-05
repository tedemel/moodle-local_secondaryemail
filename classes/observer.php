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
 * Event observers for secondary email plugin.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_secondaryemail;

/**
 * Event observer class for the secondary email plugin.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Trigger verification when a new user is created.
     *
     * This includes accounts created via CSV upload or admin creation.
     *
     * @param \core\event\user_created $event
     */
    public static function user_created(\core\event\user_created $event): void {
        self::handle_user_profile_change((int) $event->objectid);
    }

    /**
     * Trigger verification when the user updates their profile.
     *
     * @param \core\event\user_updated $event
     */
    public static function user_updated(\core\event\user_updated $event): void {
        self::handle_user_profile_change((int) $event->objectid, (int) $event->userid);
    }

    /**
     * Handle profile changes for both created and updated users.
     *
     * @param int $userid
     */
    protected static function handle_user_profile_change(int $userid, ?int $actorid = null): void {
        global $USER;

        $email = verification::get_secondary_email_value($userid);

        if ($email === '') {
            verification::clear($userid);
            return;
        }

        if (!verification::is_email_acceptable($email)) {
            verification::clear_secondary_email_value($userid);
            verification::clear($userid);

            if (!$actorid && isset($USER->id)) {
                $actorid = (int) $USER->id;
            }

            if ($actorid) {
                $editurl = new \moodle_url('/user/editadvanced.php', ['id' => $userid]);
                $link = \html_writer::link(
                    $editurl,
                    get_string('editsecondaryemail', 'local_secondaryemail')
                );
                \core\notification::add(
                    get_string('invalidsecondaryemailnotice', 'local_secondaryemail', $link),
                    \core\output\notification::NOTIFY_ERROR
                );
            }

            return;
        }

        if (verification::is_verified($userid, $email)) {
            return;
        }

        if (verification::is_pending($userid, $email) && verification::has_active_token($userid)) {
            return;
        }

        verification::start($userid, $email);
    }

    /**
     * Send notification copies to verified secondary email addresses.
     *
     * @param \core\event\notification_sent $event
     */
    public static function notification_sent(\core\event\notification_sent $event): void {
        global $DB;

        $data = $event->get_record_snapshot('notifications', $event->objectid);
        if (empty($data)) {
            return;
        }

        $userid = (int) $data->useridto;

        // Check if this notification type is allowed (whitelist approach).
        $component = $data->component ?? '';
        $name = $data->eventtype ?? '';
        if (!self::is_notification_allowed($component, $name, $userid)) {
            return;
        }

        // Get the user record.
        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user || $user->deleted || $user->suspended || $user->auth === 'nologin') {
            return;
        }

        // Get verified secondary addresses.
        $addresses = verification::get_verified_addresses($userid);
        if (empty($addresses)) {
            return;
        }

        // Get the sender.
        $userfrom = \core_user::get_user($data->useridfrom);
        if (!$userfrom) {
            $userfrom = \core_user::get_noreply_user();
        }

        // Prepare the message content.
        $subject = $data->subject;
        $fullmessage = $data->fullmessage;
        $fullmessagehtml = $data->fullmessagehtml;

        // Send to each verified secondary address.
        foreach ($addresses as $address) {
            // Skip if secondary email is the same as primary.
            if ($user->email === $address) {
                continue;
            }

            $recipient = clone $user;
            $recipient->email = $address;

            try {
                email_to_user($recipient, $userfrom, $subject, $fullmessage, $fullmessagehtml);
            } catch (\Exception $e) {
                debugging('Failed to send to secondary email: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
    }

    /**
     * Check if a notification should be sent to the secondary email.
     * Uses whitelist approach: notifications are only sent if explicitly enabled (safer for privacy).
     * Checks admin enablements and optionally user-specific settings.
     *
     * @param string $component The notification component (e.g., 'mod_forum', 'moodle').
     * @param string $name The notification name/type (e.g., 'posts', 'instantmessage').
     * @param int $userid The user ID to check preferences for.
     * @return bool True if the notification should be sent.
     */
    protected static function is_notification_allowed(string $component, string $name, int $userid): bool {
        // Build the provider ID to check.
        $providerid = $component . '/' . $name;

        // Check if admin has enabled this provider.
        if (!self::is_provider_admin_enabled($providerid, $component)) {
            return false; // Not enabled by admin = not sent.
        }

        // If user customization is enabled, check user preferences.
        if (get_config('local_secondaryemail', 'allowuserexclusions')) {
            // User can disable notifications that admin has enabled.
            if (self::is_provider_user_disabled($providerid, $userid)) {
                return false;
            }
        }

        // Admin enabled and user hasn't disabled = allowed.
        return true;
    }

    /**
     * Check if a provider is enabled by admin settings.
     *
     * @param string $providerid The full provider ID (component/name).
     * @param string $component The component name for backwards compatibility.
     * @return bool True if enabled by admin.
     */
    protected static function is_provider_admin_enabled(string $providerid, string $component): bool {
        $enabledlist = verification::get_enabled_provider_ids();

        // If nothing is enabled, nothing is sent (safe default).
        if (empty($enabledlist)) {
            return false;
        }

        // Check if this provider is in the enabled list.
        return in_array($providerid, $enabledlist);
    }

    /**
     * Check if a provider is disabled by user preferences.
     * Users can only disable what admin has enabled.
     *
     * @param string $providerid The full provider ID (component/name).
     * @param int $userid The user ID.
     * @return bool True if disabled by user.
     */
    protected static function is_provider_user_disabled(string $providerid, int $userid): bool {
        $userdisabled = get_user_preferences(verification::PREF_DISABLED_PROVIDERS, '[]', $userid);
        $disabledlist = json_decode($userdisabled, true) ?: [];

        return in_array($providerid, $disabledlist);
    }

    /**
     * Handle profile field deletion - restore if it's our field.
     *
     * @param \core\event\user_info_field_deleted $event
     */
    public static function profile_field_deleted(\core\event\user_info_field_deleted $event): void {
        global $DB;

        $data = $event->get_data();
        $other = $data['other'] ?? [];
        $shortname = $other['shortname'] ?? '';

        if ($shortname === 'secondaryemail') {
            // Our field was deleted - recreate it.
            self::ensure_profile_field_exists();
        }
    }

    /**
     * Handle profile field update - restore settings if our field was modified.
     *
     * @param \core\event\user_info_field_updated $event
     */
    public static function profile_field_updated(\core\event\user_info_field_updated $event): void {
        global $CFG, $DB;

        // PROFILE_VISIBLE_* constants are not loaded in every context this event can fire from.
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $fieldid = $event->objectid;
        $field = $DB->get_record('user_info_field', ['id' => $fieldid]);

        if (!$field || $field->shortname !== 'secondaryemail') {
            return;
        }

        // Check if critical settings were changed.
        $expectedname = get_string('secondaryemailfieldname', 'local_secondaryemail');
        $updates = [];

        // Restore name if changed.
        if ($field->name !== $expectedname) {
            $updates['name'] = $expectedname;
        }

        // Restore visibility if changed (must be PROFILE_VISIBLE_TEACHERS = 3).
        if ((int) $field->visible !== PROFILE_VISIBLE_TEACHERS) {
            $updates['visible'] = PROFILE_VISIBLE_TEACHERS;
        }

        // Restore datatype if changed.
        if ($field->datatype !== 'text') {
            $updates['datatype'] = 'text';
        }

        if (!empty($updates)) {
            $updates['id'] = $field->id;
            $DB->update_record('user_info_field', (object) $updates);
        }
    }

    /**
     * Handle profile category deletion - restore if it's our category.
     *
     * @param \core\event\user_info_category_deleted $event
     */
    public static function profile_category_deleted(\core\event\user_info_category_deleted $event): void {
        $data = $event->get_data();
        $other = $data['other'] ?? [];
        $name = $other['name'] ?? '';

        $expectedname = get_string('profilecategory', 'local_secondaryemail');

        if ($name === $expectedname) {
            // Our category was deleted - recreate field and category.
            self::ensure_profile_field_exists();
        }
    }

    /**
     * Handle profile category update - restore name if our category was renamed.
     *
     * @param \core\event\user_info_category_updated $event
     */
    public static function profile_category_updated(\core\event\user_info_category_updated $event): void {
        global $DB;

        $categoryid = $event->objectid;
        $category = $DB->get_record('user_info_category', ['id' => $categoryid]);

        if (!$category) {
            return;
        }

        // Check if our field belongs to this category.
        $field = $DB->get_record('user_info_field', ['shortname' => 'secondaryemail']);
        if (!$field || (int) $field->categoryid !== (int) $categoryid) {
            return;
        }

        // This is our category - restore the name if changed.
        $expectedname = get_string('profilecategory', 'local_secondaryemail');
        if ($category->name !== $expectedname) {
            $DB->update_record('user_info_category', (object) [
                'id' => $category->id,
                'name' => $expectedname,
            ]);
        }
    }

    /**
     * Ensure the secondary email profile field exists.
     */
    protected static function ensure_profile_field_exists(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/secondaryemail/db/install.php');
        local_secondaryemail_ensure_profile_field();
    }
}
