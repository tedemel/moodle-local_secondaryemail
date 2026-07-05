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
 * User edit wrapper for the secondary email report.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/gdlib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/user/editadvanced_form.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/webservice/lib.php');

$id = required_param('id', PARAM_INT);
$courseid = optional_param('course', SITEID, PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

if (empty($returnurl)) {
    $returnurl = (new moodle_url('/local/secondaryemail/report.php'))->out(false);
}

$PAGE->set_url('/local/secondaryemail/edit.php', [
    'id' => $id,
    'course' => $courseid,
    'returnurl' => $returnurl,
]);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => $id], '*', MUST_EXIST);

require_login($course);
require_capability('local/secondaryemail:manage', context_system::instance());
require_capability('moodle/user:update', context_system::instance());

if ($user->deleted) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('userdeleted'));
    echo $OUTPUT->footer();
    exit;
}

if (is_mnet_remote_user($user)) {
    redirect($CFG->wwwroot . "/user/view.php?id={$id}&course={$courseid}");
}

if (isguestuser($user->id)) {
    throw new \moodle_exception('guestnoeditprofileother');
}

if (is_siteadmin($user) && !is_siteadmin($USER)) {
    throw new \moodle_exception('useradmineditadmin');
}

$PAGE->set_pagelayout('admin');
$PAGE->add_body_class('limitedwidth');
$PAGE->set_context(context_user::instance($user->id));

// Load user preferences and custom profile field data.
useredit_load_preferences($user);
profile_load_data($user);
$user->interests = core_tag_tag::get_item_tags_array('core', 'user', $id);

$usercontext = context_user::instance($user->id);
$editoroptions = [
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $CFG->maxbytes,
    'trusttext' => false,
    'forcehttps' => false,
    'context' => $usercontext,
];

$user = file_prepare_standard_editor($user, 'description', $editoroptions, $usercontext, 'user', 'profile', 0);

// Prepare filemanager draft area.
$draftitemid = 0;
$filemanageroptions = [
    'maxbytes' => $CFG->maxbytes,
    'subdirs' => 0,
    'maxfiles' => 1,
    'accepted_types' => 'optimised_image',
];
file_prepare_draft_area($draftitemid, $usercontext->id, 'user', 'newicon', 0, $filemanageroptions);
$user->imagefile = $draftitemid;

$userform = new user_editadvanced_form(new moodle_url($PAGE->url), [
    'editoroptions' => $editoroptions,
    'filemanageroptions' => $filemanageroptions,
    'user' => $user,
]);

if ($userform->is_cancelled()) {
    redirect($returnurl);
} else if ($usernew = $userform->get_data()) {
    $authplugin = !empty($usernew->auth) ? get_auth_plugin($usernew->auth) : get_auth_plugin($user->auth);
    $usernew->timemodified = time();

    $usernew = file_postupdate_standard_editor($usernew, 'description', $editoroptions, $usercontext, 'user', 'profile', 0);
    if (!$authplugin->user_update($user, $usernew)) {
        throw new \moodle_exception('cannotupdateuseronexauth', '', '', $user->auth);
    }
    user_update_user($usernew, false, false);

    // Set new password if specified.
    if (!empty($usernew->newpassword) && $authplugin->can_change_password()) {
        if (!$authplugin->user_update_password($usernew, $usernew->newpassword)) {
            throw new \moodle_exception('cannotupdatepasswordonextauth', '', '', $usernew->auth);
        }
        unset_user_preference('create_password', $usernew);

        if (!empty($CFG->passwordchangelogout)) {
            \core\session\manager::destroy_user_sessions($usernew->id, session_id());
        }
        if (!empty($usernew->signoutofotherservices)) {
            webservice::delete_user_ws_tokens($usernew->id);
        }
    }

    if (isset($usernew->suspended) && $usernew->suspended && !$user->suspended) {
        \core\session\manager::destroy_user_sessions($user->id);
    }

    $usercontext = context_user::instance($usernew->id);

    useredit_update_user_preference($usernew);
    if (isset($usernew->interests)) {
        useredit_update_interests($usernew, $usernew->interests);
    }
    core_user::update_picture($usernew, $filemanageroptions);
    useredit_update_bounces($user, $usernew);
    useredit_update_trackforums($user, $usernew);
    profile_save_data($usernew);

    $usernew = $DB->get_record('user', ['id' => $usernew->id]);
    \core\event\user_updated::create_from_userid($usernew->id)->trigger();

    \core\session\manager::gc();
    redirect($returnurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$userfullname = fullname($user, true);
$PAGE->set_title(get_string('editmyprofile') . ': ' . $userfullname);
$PAGE->set_heading($userfullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($userfullname);
$userform->display();
echo $OUTPUT->footer();
