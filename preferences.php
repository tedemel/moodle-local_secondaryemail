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
 * User preferences page for secondary email notifications.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

// The optional_param() default is returned uncleaned, so normalise to int explicitly.
$userid = optional_param('userid', 0, PARAM_INT);
if (!$userid) {
    $userid = (int) $USER->id;
}

// Check if user customization is enabled.
if (!get_config('local_secondaryemail', 'allowuserexclusions')) {
    throw new moodle_exception('userexclusionsdisabled', 'local_secondaryemail');
}

// Check permissions.
$personalcontext = context_user::instance($userid);
$systemcontext = context_system::instance();

if ($userid === (int) $USER->id) {
    // User editing their own preferences.
    require_capability('local/secondaryemail:configureown', $systemcontext);
} else {
    // Admin editing another user's preferences.
    require_capability('local/secondaryemail:manage', $systemcontext);
}

$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

$PAGE->set_context($personalcontext);
$PAGE->set_url(new moodle_url('/local/secondaryemail/preferences.php', ['userid' => $userid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('userpreferencestitle', 'local_secondaryemail'));
$PAGE->set_heading(fullname($user) . ': ' . get_string('userpreferencestitle', 'local_secondaryemail'));

// Navigation.
$PAGE->navbar->add(get_string('preferences'), new moodle_url('/user/preferences.php', ['userid' => $userid]));
$PAGE->navbar->add(get_string('userpreferencestitle', 'local_secondaryemail'));

// Get current user disabled preferences.
$userdisabled = get_user_preferences(\local_secondaryemail\verification::PREF_DISABLED_PROVIDERS, '[]', $userid);
$userdisabledlist = json_decode($userdisabled, true) ?: [];

// Get admin enabled providers (only these are available to users).
$adminenabledlist = \local_secondaryemail\verification::get_enabled_provider_ids();

// Build provider list - only include admin-enabled providers.
$allgrouped = \local_secondaryemail\verification::get_grouped_message_providers();
$grouped = [];
foreach ($allgrouped as $component => $componentproviders) {
    foreach ($componentproviders as $provider) {
        // Only show providers the admin has enabled.
        if (!in_array($provider->providerid, $adminenabledlist) && !in_array($component, $adminenabledlist)) {
            continue;
        }
        if (!isset($grouped[$component])) {
            $grouped[$component] = [];
        }
        $grouped[$component][] = $provider;
    }
}

$form = new \local_secondaryemail\form\preferences_form($PAGE->url, [
    'grouped' => $grouped,
    'userdisabledlist' => $userdisabledlist,
]);

if ($data = $form->get_data()) {
    $disabled = $data->disabled ?? [];
    $disabledlist = array_keys(array_filter((array) $disabled));
    set_user_preference(\local_secondaryemail\verification::PREF_DISABLED_PROVIDERS, json_encode($disabledlist), $userid);

    \core\notification::success(get_string('preferencessaved', 'local_secondaryemail'));
    redirect($PAGE->url);
}

echo $OUTPUT->header();

if (empty($grouped)) {
    // No providers enabled by admin.
    echo $OUTPUT->notification(get_string('noprovidersenabled', 'local_secondaryemail'), 'warning');
} else {
    // Show info box.
    echo $OUTPUT->notification(get_string('userpreferences_info', 'local_secondaryemail'), 'info');
    $form->display();
}

echo $OUTPUT->footer();
