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
 * Verification helper for secondary email addresses.
 *
 * This class handles verification of secondary email addresses including
 * token generation, confirmation, and status management.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_secondaryemail;

/**
 * Verification helper class for secondary email addresses.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class verification {
    /** @var string preference key for verified email */
    public const PREF_VERIFIED = 'local_secondaryemail_verified';
    /** @var string preference key for verification token */
    public const PREF_TOKEN = 'local_secondaryemail_token';
    /** @var string preference key for token timestamp */
    public const PREF_TOKEN_TIME = 'local_secondaryemail_token_time';
    /** @var string preference key for pending email */
    public const PREF_PENDING = 'local_secondaryemail_pending';
    /** @var string preference key for disabling secondary email */
    public const PREF_DISABLED = 'local_secondaryemail_disabled';
    /** @var string preference key for user-disabled notification providers (JSON array) */
    public const PREF_DISABLED_PROVIDERS = 'local_secondaryemail_disabled_providers';
    /** @var string preference key for relationship tag */
    public const PREF_RELATIONSHIP = 'local_secondaryemail_relationship';

    /** @var int|null cached profile field id */
    private static ?int $fieldidcache = null;

    /**
     * Start verification for a new email address.
     *
     * @param int $userid
     * @param string $email
     */
    public static function start(int $userid, string $email): void {
        if (!self::is_email_acceptable($email)) {
            self::clear($userid);
            return;
        }

        $token = random_string(32);
        $tokentime = time();

        set_user_preference(self::PREF_VERIFIED, '', $userid);
        set_user_preference(self::PREF_TOKEN, $token, $userid);
        set_user_preference(self::PREF_TOKEN_TIME, $tokentime, $userid);
        set_user_preference(self::PREF_PENDING, $email, $userid);

        self::send_confirmation_email($userid, $email, $token);
    }

    /**
     * Confirm a pending email address.
     *
     * @param int $userid
     * @param string $token
     * @param string|null $failure Reference to store failure reason
     * @return bool
     */
    public static function confirm(int $userid, string $token, ?string &$failure = null): bool {
        $storedtoken = (string) get_user_preferences(self::PREF_TOKEN, '', $userid);
        $tokentime = (int) get_user_preferences(self::PREF_TOKEN_TIME, 0, $userid);
        $pendingemail = (string) get_user_preferences(self::PREF_PENDING, '', $userid);

        if ($storedtoken === '' || $pendingemail === '' || $storedtoken !== $token) {
            $failure = 'invalid';
            return false;
        }

        if (self::is_token_expired($tokentime)) {
            $failure = 'expired';
            return false;
        }

        $current = self::get_secondary_email_value($userid);
        if ($current === '' || $current !== $pendingemail) {
            $failure = 'invalid';
            return false;
        }

        set_user_preference(self::PREF_VERIFIED, $pendingemail, $userid);
        unset_user_preference(self::PREF_TOKEN, $userid);
        unset_user_preference(self::PREF_TOKEN_TIME, $userid);
        unset_user_preference(self::PREF_PENDING, $userid);

        self::send_verified_email($userid, $pendingemail);

        return true;
    }

    /**
     * Get verified addresses for the user.
     *
     * @param int $userid
     * @return string[]
     */
    public static function get_verified_addresses(int $userid): array {
        if (self::is_disabled($userid)) {
            return [];
        }

        $verified = (string) get_user_preferences(self::PREF_VERIFIED, '', $userid);
        if ($verified === '' || !validate_email($verified)) {
            return [];
        }

        $current = self::get_secondary_email_value($userid);
        if ($current === '' || $current !== $verified) {
            return [];
        }

        return [$verified];
    }

    /**
     * Check whether a given email is verified for the user.
     *
     * @param int $userid
     * @param string|null $email
     * @return bool
     */
    public static function is_verified(int $userid, ?string $email = null): bool {
        $verified = (string) get_user_preferences(self::PREF_VERIFIED, '', $userid);
        if ($verified === '' || !validate_email($verified)) {
            return false;
        }

        if ($email !== null) {
            return $verified === $email;
        }

        $current = self::get_secondary_email_value($userid);
        return $current !== '' && $current === $verified;
    }

    /**
     * Check whether a pending email is already in verification.
     *
     * @param int $userid
     * @param string $email
     * @return bool
     */
    public static function is_pending(int $userid, string $email): bool {
        $pending = (string) get_user_preferences(self::PREF_PENDING, '', $userid);
        return $pending !== '' && $pending === $email;
    }

    /**
     * Check whether the pending token is still valid.
     *
     * @param int $userid
     * @param string|null $email
     * @return bool
     */
    public static function has_active_token(int $userid, ?string $email = null): bool {
        $token = (string) get_user_preferences(self::PREF_TOKEN, '', $userid);
        $tokentime = (int) get_user_preferences(self::PREF_TOKEN_TIME, 0, $userid);
        $pending = (string) get_user_preferences(self::PREF_PENDING, '', $userid);
        if ($token === '' || $pending === '') {
            return false;
        }
        if ($email !== null && $pending !== $email) {
            return false;
        }

        return !self::is_token_expired($tokentime);
    }

    /**
     * Clear verification preferences for the user.
     *
     * @param int $userid
     */
    public static function clear(int $userid): void {
        unset_user_preference(self::PREF_VERIFIED, $userid);
        unset_user_preference(self::PREF_TOKEN, $userid);
        unset_user_preference(self::PREF_TOKEN_TIME, $userid);
        unset_user_preference(self::PREF_PENDING, $userid);
    }

    /**
     * Delete the secondary email value and clear all related preferences.
     *
     * @param int $userid
     */
    public static function delete_secondary_email(int $userid): void {
        global $DB;

        $field = $DB->get_record('user_info_field', ['shortname' => 'secondaryemail']);
        if ($field) {
            $DB->delete_records('user_info_data', ['userid' => $userid, 'fieldid' => $field->id]);
        }

        self::clear($userid);
        unset_user_preference(self::PREF_DISABLED, $userid);
        unset_user_preference(self::PREF_DISABLED_PROVIDERS, $userid);
        unset_user_preference(self::PREF_RELATIONSHIP, $userid);
    }

    /**
     * Disable sending copies to secondary email for a user.
     *
     * @param int $userid
     */
    public static function disable(int $userid): void {
        set_user_preference(self::PREF_DISABLED, 1, $userid);
    }

    /**
     * Enable sending copies to secondary email for a user.
     *
     * @param int $userid
     */
    public static function enable(int $userid): void {
        unset_user_preference(self::PREF_DISABLED, $userid);
    }

    /**
     * Check whether secondary email sending is disabled for a user.
     *
     * @param int $userid
     * @return bool
     */
    public static function is_disabled(int $userid): bool {
        return (int) get_user_preferences(self::PREF_DISABLED, 0, $userid) === 1;
    }

    /**
     * Fetch the stored secondary email profile field value.
     *
     * @param int $userid
     * @return string
     */
    public static function get_secondary_email_value(int $userid): string {
        global $DB;

        // Cache field ID per request, but refresh when cache is stale.
        // Stale cache can happen in long-running test processes after DB reset.
        if (
            self::$fieldidcache && !$DB->record_exists('user_info_field', [
            'id' => self::$fieldidcache,
            'shortname' => 'secondaryemail',
            ])
        ) {
            self::$fieldidcache = null;
        }

        // Do not permanently cache "not found" (0), because install/upgrade/test setup
        // may create the field later in the same request lifecycle.
        if (self::$fieldidcache === null || self::$fieldidcache === 0) {
            $field = $DB->get_record('user_info_field', ['shortname' => 'secondaryemail']);
            self::$fieldidcache = $field ? (int) $field->id : 0;
        }

        if (self::$fieldidcache === 0) {
            return '';
        }

        $data = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => self::$fieldidcache]);
        if (!$data || empty($data->data)) {
            return '';
        }

        return trim((string) $data->data);
    }

    /**
     * Clear the stored secondary email profile field value.
     *
     * @param int $userid
     */
    public static function clear_secondary_email_value(int $userid): void {
        global $DB;

        $field = $DB->get_record('user_info_field', ['shortname' => 'secondaryemail']);
        if (!$field) {
            return;
        }

        if ($data = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $field->id])) {
            $data->data = '';
            $DB->update_record('user_info_data', $data);
        }
    }

    /**
     * Reset cached values.
     */
    public static function reset_caches(): void {
        self::$fieldidcache = null;
    }

    /**
     * Send a confirmation email to the pending address.
     *
     * @param int $userid
     * @param string $email
     * @param string $token
     */
    protected static function send_confirmation_email(int $userid, string $email, string $token): void {
        $url = new \moodle_url('/local/secondaryemail/confirm.php', [
            'userid' => $userid,
            'token' => $token,
        ]);

        $policyline = '';
        $policymanager = new \core_privacy\local\sitepolicy\manager();
        if ($policymanager->is_defined(false)) {
            $policyurl = $policymanager->get_redirect_url(false);
            if ($policyurl) {
                $policyline = get_string('confirmationemailpolicy', 'local_secondaryemail', $policyurl->out(false));
            }
        }

        self::send_to_secondary_address(
            $userid,
            $email,
            'confirmationemailsubject',
            'confirmationemailbody',
            [
                'link' => $url->out(false),
                'policyline' => $policyline,
            ]
        );
    }

    /**
     * Send a notification email after successful verification.
     *
     * @param int $userid
     * @param string $email
     */
    protected static function send_verified_email(int $userid, string $email): void {
        self::send_to_secondary_address($userid, $email, 'verifiedemailsubject', 'verifiedemailbody');
    }

    /**
     * Send an email to a secondary address.
     *
     * @param int $userid The user ID.
     * @param string $email The secondary email address.
     * @param string $subjectkey The language string key for the subject.
     * @param string $bodykey The language string key for the body.
     * @param array $extrafields Additional fields for the language string placeholder object.
     */
    protected static function send_to_secondary_address(
        int $userid,
        string $email,
        string $subjectkey,
        string $bodykey,
        array $extrafields = []
    ): void {
        global $DB;

        if (!self::is_email_acceptable($email)) {
            return;
        }

        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $supportuser = \core_user::get_support_user();

        $site = get_site();
        $a = (object) array_merge([
            'fullname' => fullname($user),
            'sitename' => format_string($site->fullname),
            'email' => $email,
        ], $extrafields);

        $subject = get_string($subjectkey, 'local_secondaryemail', $a);
        $message = get_string($bodykey, 'local_secondaryemail', $a);

        $recipient = clone $user;
        $recipient->email = $email;

        try {
            email_to_user($recipient, $supportuser, $subject, $message);
        } catch (\Exception $e) {
            debugging('Failed to send to secondary email: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Determine whether the current token is expired.
     *
     * @param int $tokentime
     * @return bool
     */
    protected static function is_token_expired(int $tokentime): bool {
        $expiryhours = self::get_token_expiry_hours();
        if ($expiryhours <= 0 || $tokentime <= 0) {
            return false;
        }

        return (time() - $tokentime) > ($expiryhours * HOURSECS);
    }

    /**
     * Get expiry hours for confirmation tokens.
     *
     * @return int
     */
    protected static function get_token_expiry_hours(): int {
        $expiry = get_config('local_secondaryemail', 'verificationexpiryhours');
        if ($expiry === false || $expiry === null || $expiry === '') {
            return 24;
        }
        return max(0, (int) $expiry);
    }

    /**
     * Validate that the email is acceptable for verification.
     *
     * @param string $email
     * @return bool
     */
    public static function is_email_acceptable(string $email): bool {
        $email = trim($email);
        if ($email === '' || !validate_email($email)) {
            return false;
        }

        $error = email_is_not_allowed($email);
        return $error === false;
    }

    /**
     * Check if relationship tagging is enabled.
     *
     * @return bool
     */
    public static function is_tagging_enabled(): bool {
        return (bool) get_config('local_secondaryemail', 'enablerelationshiptag');
    }

    /**
     * Get the available relationship tags.
     *
     * @return array
     */
    public static function get_available_tags(): array {
        $tagsconfig = get_config('local_secondaryemail', 'relationshiptags');
        if (empty($tagsconfig)) {
            return [];
        }

        $tags = [];
        $lines = explode("\n", $tagsconfig);
        foreach ($lines as $line) {
            $tag = trim($line);
            if ($tag !== '') {
                $tags[$tag] = self::get_tag_display_name($tag);
            }
        }

        return $tags;
    }

    /**
     * Get the display name for a tag.
     *
     * @param string $tag
     * @return string
     */
    public static function get_tag_display_name(string $tag): string {
        $stringkey = 'relationship' . strtolower($tag);
        $stringmanager = get_string_manager();

        if ($stringmanager->string_exists($stringkey, 'local_secondaryemail')) {
            return get_string($stringkey, 'local_secondaryemail');
        }

        return ucfirst($tag);
    }

    /**
     * Get the relationship tag for a user.
     *
     * @param int $userid
     * @return string
     */
    public static function get_relationship(int $userid): string {
        return (string) get_user_preferences(self::PREF_RELATIONSHIP, '', $userid);
    }

    /**
     * Set the relationship tag for a user.
     *
     * @param int $userid
     * @param string $tag
     */
    public static function set_relationship(int $userid, string $tag): void {
        if ($tag === '') {
            unset_user_preference(self::PREF_RELATIONSHIP, $userid);
        } else {
            set_user_preference(self::PREF_RELATIONSHIP, $tag, $userid);
        }
    }

    /**
     * Clear the relationship tag for a user.
     *
     * @param int $userid
     */
    public static function clear_relationship(int $userid): void {
        unset_user_preference(self::PREF_RELATIONSHIP, $userid);
    }

    /**
     * Get the list of admin-enabled provider IDs.
     *
     * Parses the comma-separated 'enabledproviders' config value into an array.
     *
     * @return array List of enabled provider IDs (e.g. ['mod_forum/posts', 'moodle/instantmessage']).
     */
    public static function get_enabled_provider_ids(): array {
        $enabled = get_config('local_secondaryemail', 'enabledproviders');
        if (empty($enabled) || !is_string($enabled)) {
            return [];
        }

        $list = [];
        $parts = explode(',', $enabled);
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $list[] = $part;
            }
        }

        return $list;
    }

    /**
     * Get all message providers grouped by component with resolved display names.
     *
     * Returns an array grouped by component, sorted alphabetically with 'moodle' (core) first.
     * Each entry contains the provider record plus resolved 'componentname' and 'providername'.
     *
     * @return array Associative array keyed by component, values are arrays of provider objects.
     */
    public static function get_grouped_message_providers(): array {
        global $DB;

        $providers = $DB->get_records('message_providers', null, 'component, name');
        $grouped = [];

        foreach ($providers as $provider) {
            $component = $provider->component;
            if (!isset($grouped[$component])) {
                $grouped[$component] = [];
            }

            // Resolve human-readable component name.
            if ($component === 'moodle') {
                $provider->componentname = get_string('coresystem');
            } else {
                try {
                    $provider->componentname = get_string('pluginname', $component);
                } catch (\Exception $e) {
                    $provider->componentname = $component;
                }
            }

            // Resolve human-readable provider name.
            $strname = 'messageprovider:' . $provider->name;
            try {
                if (get_string_manager()->string_exists($strname, $provider->component)) {
                    $provider->providername = get_string($strname, $provider->component);
                } else {
                    $provider->providername = $provider->name;
                }
            } catch (\Exception $e) {
                $provider->providername = $provider->name;
            }

            // Build provider ID.
            $provider->providerid = $provider->component . '/' . $provider->name;

            $grouped[$component][] = $provider;
        }

        // Sort components alphabetically, but put 'moodle' (core) first.
        uksort($grouped, function ($a, $b) {
            if ($a === 'moodle') {
                return -1;
            }
            if ($b === 'moodle') {
                return 1;
            }
            return strcmp($a, $b);
        });

        return $grouped;
    }
}
