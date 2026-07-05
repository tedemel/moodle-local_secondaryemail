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
 * Confirmation endpoint for secondary email verification.
 *
 * This page intentionally does not require login so recipients can confirm
 * via the emailed token link without an active session.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- Recipients confirm via emailed token link without a session.
require_once(__DIR__ . '/../../config.php');

$userid = required_param('userid', PARAM_INT);
$token = required_param('token', PARAM_ALPHANUMEXT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/secondaryemail/confirm.php', [
    'userid' => $userid,
    'token' => $token,
]));
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('confirmationpagetitle', 'local_secondaryemail'));
$PAGE->set_heading(get_string('confirmationpagetitle', 'local_secondaryemail'));

$failure = null;
$success = \local_secondaryemail\verification::confirm($userid, $token, $failure);

echo $OUTPUT->header();
if ($success) {
    echo $OUTPUT->notification(get_string('confirmationsuccess', 'local_secondaryemail'), 'notifysuccess');
    echo $OUTPUT->continue_button(new moodle_url('/user/profile.php', ['id' => $userid]));
} else {
    $messagekey = $failure === 'expired' ? 'confirmationexpired' : 'confirmationinvalid';
    echo $OUTPUT->notification(get_string($messagekey, 'local_secondaryemail'), 'notifyproblem');
    echo $OUTPUT->continue_button(new moodle_url('/'));
}
echo $OUTPUT->footer();
