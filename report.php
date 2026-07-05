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
 * Secondary email report.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use core_reportbuilder\system_report_factory;
use local_secondaryemail\reportbuilder\local\systemreports\secondaryemail;

admin_externalpage_setup('local_secondaryemail_report');

$action = optional_param('action', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$relationship = optional_param('relationship', '', PARAM_RAW_TRIMMED);

$returnurl = new moodle_url('/local/secondaryemail/report.php');

require_capability('local/secondaryemail:viewreport', context_system::instance());

if (!empty($action) && !empty($userid) && confirm_sesskey()) {
    require_capability('local/secondaryemail:manage', context_system::instance());

    if (
        !$user = $DB->get_record('user', [
        'id' => $userid,
        'mnethostid' => $CFG->mnet_localhost_id,
        'deleted' => 0,
        ])
    ) {
        throw new \moodle_exception('nousers');
    }

    $secondaryemail = \local_secondaryemail\verification::get_secondary_email_value($user->id);

    switch ($action) {
        case 'resend':
            if ($secondaryemail === '') {
                redirect(
                    $returnurl,
                    get_string('secondaryemailmissing', 'local_secondaryemail'),
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }
            if (\local_secondaryemail\verification::is_verified($user->id, $secondaryemail)) {
                redirect(
                    $returnurl,
                    get_string('secondaryemailalreadyverified', 'local_secondaryemail'),
                    null,
                    \core\output\notification::NOTIFY_INFO
                );
            }
            if (!\local_secondaryemail\verification::is_email_acceptable($secondaryemail)) {
                redirect(
                    $returnurl,
                    get_string('secondaryemailinvalid', 'local_secondaryemail'),
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }

            \local_secondaryemail\verification::start($user->id, $secondaryemail);
            redirect(
                $returnurl,
                get_string('secondaryemailconfirmationsent', 'local_secondaryemail'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
            break;

        case 'block':
            \local_secondaryemail\verification::disable($user->id);
            redirect(
                $returnurl,
                get_string('secondaryemailblocked', 'local_secondaryemail'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
            break;

        case 'unblock':
            \local_secondaryemail\verification::enable($user->id);
            redirect(
                $returnurl,
                get_string('secondaryemailunblocked', 'local_secondaryemail'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
            break;

        case 'delete':
            if ($secondaryemail === '') {
                redirect(
                    $returnurl,
                    get_string('secondaryemailmissing', 'local_secondaryemail'),
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }

            if (!$confirm) {
                echo $OUTPUT->header();
                $message = get_string('secondaryemaildeleteconfirm', 'local_secondaryemail', $secondaryemail);
                $deleteurl = new moodle_url($returnurl, [
                    'action' => 'delete',
                    'userid' => $userid,
                    'confirm' => 1,
                    'sesskey' => sesskey(),
                ]);
                $formcontinue = new single_button($deleteurl, get_string('delete'), 'post');
                $formcancel = new single_button($returnurl, get_string('cancel'), 'get');
                echo $OUTPUT->confirm($message, $formcontinue, $formcancel);
                echo $OUTPUT->footer();
                exit;
            }

            \local_secondaryemail\verification::delete_secondary_email($user->id);
            redirect(
                $returnurl,
                get_string('secondaryemaildeleted', 'local_secondaryemail'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
            break;

        case 'setrelationship':
            if ($secondaryemail === '') {
                redirect(
                    $returnurl,
                    get_string('secondaryemailmissing', 'local_secondaryemail'),
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }

            $availabletags = \local_secondaryemail\verification::get_available_tags();
            if (empty($relationship) || !array_key_exists($relationship, $availabletags)) {
                redirect(
                    $returnurl,
                    get_string('invalidtag', 'local_secondaryemail'),
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }

            \local_secondaryemail\verification::set_relationship($user->id, $relationship);
            $tagname = \local_secondaryemail\verification::get_tag_display_name($relationship);
            redirect(
                $returnurl,
                get_string('tagset', 'local_secondaryemail', $tagname),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
            break;

        case 'clearrelationship':
            \local_secondaryemail\verification::clear_relationship($user->id);
            redirect(
                $returnurl,
                get_string('tagremoved', 'local_secondaryemail'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
            break;
    }
}

$PAGE->set_url($returnurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('secondaryemailreport', 'local_secondaryemail'));
$PAGE->set_heading(get_string('secondaryemailreport', 'local_secondaryemail'));

echo $OUTPUT->header();

$report = system_report_factory::create(secondaryemail::class, context_system::instance());
echo $report->output();

echo $OUTPUT->footer();
