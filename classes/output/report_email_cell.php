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

namespace local_secondaryemail\output;

use action_menu_link_secondary;
use core\output\action_menu as core_action_menu;
use renderable;
use renderer_base;
use templatable;
use html_writer;
use moodle_url;

/**
 * Renderable for secondary email report cell.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_email_cell implements renderable, templatable {
    /** @var int */
    private int $userid;

    /** @var string */
    private string $email;

    /** @var bool */
    private bool $canmanage;

    /** @var bool */
    private bool $isremoteuser;

    /**
     * Constructor.
     *
     * @param int $userid
     * @param string $email
     * @param bool $canmanage
     * @param bool $isremoteuser
     */
    public function __construct(int $userid, string $email, bool $canmanage, bool $isremoteuser) {
        $this->userid = $userid;
        $this->email = $email;
        $this->canmanage = $canmanage;
        $this->isremoteuser = $isremoteuser;
    }

    /**
     * Export data for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $email = trim($this->email);
        $isempty = ($email === '');
        $canmanage = $this->canmanage && !$this->isremoteuser;

        $isdisabled = false;
        $isverified = false;
        $ispending = false;
        $relationshiptag = '';
        $disabledtag = '';
        $statusclass = '';
        $statuslabel = '';
        $hasstatus = false;

        if (!$isempty) {
            $isdisabled = \local_secondaryemail\verification::is_disabled($this->userid);

            if ($isdisabled) {
                $disabledtag = get_string('secondaryemailblockedtag', 'local_secondaryemail');
            }

            if (\local_secondaryemail\verification::is_tagging_enabled()) {
                $relationship = \local_secondaryemail\verification::get_relationship($this->userid);
                if ($relationship !== '') {
                    $relationshiptag = \local_secondaryemail\verification::get_tag_display_name($relationship);
                }
            }

            if (!$isdisabled) {
                $isverified = \local_secondaryemail\verification::is_verified($this->userid, $email);
                if ($isverified) {
                    $statuslabel = get_string('secondaryemailverifiedtag', 'local_secondaryemail');
                    $statusclass = 'badge-success text-bg-success';
                    $hasstatus = true;
                } else if (\local_secondaryemail\verification::has_active_token($this->userid, $email)) {
                    $ispending = true;
                    $statuslabel = get_string('secondaryemailpendingtag', 'local_secondaryemail');
                    $statusclass = 'badge-danger text-bg-danger';
                    $hasstatus = true;
                } else {
                    $statuslabel = get_string('secondaryemailnotverified', 'local_secondaryemail');
                    $statusclass = 'badge-warning text-bg-warning';
                    $hasstatus = true;
                }
            }
        }

        $addurl = '';
        if ($isempty && $canmanage) {
            $returnurl = (new moodle_url('/local/secondaryemail/report.php'))->out(false);
            $addurl = (new moodle_url('/local/secondaryemail/edit.php', [
                'id' => $this->userid,
                'course' => get_site()->id,
                'returnurl' => $returnurl,
            ]))->out(false);
        }

        return [
            'email' => $email,
            'isempty' => $isempty,
            'isverified' => $isverified,
            'ispending' => $ispending,
            'isdisabled' => $isdisabled,
            'statuslabel' => $statuslabel,
            'statusclass' => $statusclass,
            'hasstatus' => $hasstatus,
            'relationshiptag' => $relationshiptag,
            'disabledlabel' => $disabledtag,
            'addurl' => $addurl,
        ];
    }

    /**
     * Build the action menu HTML for this cell.
     *
     * @param renderer_base $output
     * @return string
     */
    public function build_action_menu(renderer_base $output): string {
        $email = trim($this->email);
        $isempty = ($email === '');
        $canmanage = $this->canmanage && !$this->isremoteuser;

        if ($isempty || !$canmanage) {
            return '';
        }

        $isdisabled = \local_secondaryemail\verification::is_disabled($this->userid);
        $isverified = \local_secondaryemail\verification::is_verified($this->userid, $email);

        $menu = new core_action_menu();
        $menu->set_kebab_trigger(get_string('actions'));

        if (!$isverified && !$isdisabled) {
            $url = new moodle_url('/local/secondaryemail/report.php', [
                'action' => 'resend',
                'userid' => $this->userid,
                'sesskey' => sesskey(),
            ]);
            $menu->add(new action_menu_link_secondary(
                $url,
                null,
                get_string('secondaryemailresendaction', 'local_secondaryemail')
            ));
        }

        $returnurl = (new moodle_url('/local/secondaryemail/report.php'))->out(false);
        $editurl = new moodle_url('/local/secondaryemail/edit.php', [
            'id' => $this->userid,
            'course' => get_site()->id,
            'returnurl' => $returnurl,
        ]);
        $menu->add(new action_menu_link_secondary($editurl, null, get_string('edit', 'moodle')));

        if ($isdisabled) {
            $unblockurl = new moodle_url('/local/secondaryemail/report.php', [
                'action' => 'unblock',
                'userid' => $this->userid,
                'sesskey' => sesskey(),
            ]);
            $menu->add(new action_menu_link_secondary(
                $unblockurl,
                null,
                get_string('secondaryemailunblockaction', 'local_secondaryemail')
            ));
        } else {
            $blockurl = new moodle_url('/local/secondaryemail/report.php', [
                'action' => 'block',
                'userid' => $this->userid,
                'sesskey' => sesskey(),
            ]);
            $menu->add(new action_menu_link_secondary(
                $blockurl,
                null,
                get_string('secondaryemailblockaction', 'local_secondaryemail')
            ));
        }

        $deleteurl = new moodle_url('/local/secondaryemail/report.php', [
            'action' => 'delete',
            'userid' => $this->userid,
            'sesskey' => sesskey(),
        ]);
        $menu->add(new action_menu_link_secondary(
            $deleteurl,
            null,
            get_string('secondaryemaildeleteaction', 'local_secondaryemail')
        ));

        if (\local_secondaryemail\verification::is_tagging_enabled()) {
            $availabletags = \local_secondaryemail\verification::get_available_tags();
            if (!empty($availabletags)) {
                foreach ($availabletags as $tagkey => $tagname) {
                    $tagurl = new moodle_url('/local/secondaryemail/report.php', [
                        'action' => 'setrelationship',
                        'userid' => $this->userid,
                        'relationship' => $tagkey,
                        'sesskey' => sesskey(),
                    ]);
                    $menu->add(new action_menu_link_secondary(
                        $tagurl,
                        null,
                        get_string('settagaction', 'local_secondaryemail') . ': ' . $tagname
                    ));
                }
            }

            $currenttag = \local_secondaryemail\verification::get_relationship($this->userid);
            if ($currenttag !== '') {
                $clearurl = new moodle_url('/local/secondaryemail/report.php', [
                    'action' => 'clearrelationship',
                    'userid' => $this->userid,
                    'sesskey' => sesskey(),
                ]);
                $menu->add(new action_menu_link_secondary(
                    $clearurl,
                    null,
                    get_string('removetagaction', 'local_secondaryemail')
                ));
            }
        }

        if ($menu->is_empty()) {
            return '';
        }

        return html_writer::span($output->render($menu), 'ms-1');
    }
}
