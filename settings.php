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
 * Settings for local_secondaryemail.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_secondaryemail', get_string('pluginname', 'local_secondaryemail'));

    // Quick links section.
    $profilefieldsurl = new moodle_url('/user/profile/index.php');
    $reporturl = new moodle_url('/local/secondaryemail/report.php');

    $linkshtml = html_writer::start_tag('ul', ['class' => 'list-unstyled']);
    $linkshtml .= html_writer::tag(
        'li',
        html_writer::link(
            $profilefieldsurl,
            get_string('profilefieldlink', 'local_secondaryemail'),
            ['class' => 'btn btn-outline-secondary btn-sm me-2 mb-2', 'target' => '_blank']
        )
    );
    $linkshtml .= html_writer::tag(
        'li',
        html_writer::link(
            $reporturl,
            get_string('secondaryemailreport', 'local_secondaryemail'),
            ['class' => 'btn btn-outline-primary btn-sm mb-2']
        )
    );
    $linkshtml .= html_writer::end_tag('ul');

    $settings->add(new admin_setting_heading(
        'local_secondaryemail/quicklinks',
        get_string('quicklinks', 'local_secondaryemail'),
        get_string('quicklinks_desc', 'local_secondaryemail') . $linkshtml
    ));

    // Verification settings.
    $settings->add(new admin_setting_heading(
        'local_secondaryemail/verificationsettings',
        get_string('verificationsettings', 'local_secondaryemail'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_secondaryemail/verificationexpiryhours',
        get_string('verificationexpiry', 'local_secondaryemail'),
        get_string('verificationexpiry_help', 'local_secondaryemail'),
        24,
        PARAM_INT
    ));

    // Tagging settings.
    $settings->add(new admin_setting_heading(
        'local_secondaryemail/taggingsettings',
        get_string('taggingsettings', 'local_secondaryemail'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_secondaryemail/enablerelationshiptag',
        get_string('enabletag', 'local_secondaryemail'),
        get_string('enabletag_help', 'local_secondaryemail'),
        0
    ));

    $defaulttags = "mother\nfather\nguardian\nemployer\nother";
    $settings->add(new admin_setting_configtextarea(
        'local_secondaryemail/relationshiptags',
        get_string('availabletags', 'local_secondaryemail'),
        get_string('availabletags_help', 'local_secondaryemail'),
        $defaulttags
    ));

    // Notification filter settings.
    $settings->add(new admin_setting_heading(
        'local_secondaryemail/notificationsettings',
        get_string('notificationsettings', 'local_secondaryemail'),
        get_string('notificationsettings_desc', 'local_secondaryemail')
    ));

    // Build dynamic list of message providers grouped by component.
    $provideroptions = [];
    $grouped = \local_secondaryemail\verification::get_grouped_message_providers();

    foreach ($grouped as $component => $componentproviders) {
        foreach ($componentproviders as $provider) {
            $provideroptions[$provider->providerid] = $provider->componentname . ': ' . $provider->providername;
        }
    }

    // Whitelist approach: select which to ENABLE (safer for privacy).
    $settings->add(new admin_setting_configmulticheckbox(
        'local_secondaryemail/enabledproviders',
        get_string('enabledproviders', 'local_secondaryemail'),
        get_string('enabledproviders_help', 'local_secondaryemail'),
        [], // Default: nothing enabled = nothing sent (safe default).
        $provideroptions
    ));

    // User customization settings.
    $settings->add(new admin_setting_heading(
        'local_secondaryemail/usersettings',
        get_string('usersettings', 'local_secondaryemail'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_secondaryemail/allowuserexclusions',
        get_string('allowuserexclusions', 'local_secondaryemail'),
        get_string('allowuserexclusions_help', 'local_secondaryemail'),
        0
    ));

    $ADMIN->add('localplugins', $settings);

    // Add the report page to accounts section.
    $reportpage = new admin_externalpage(
        'local_secondaryemail_report',
        get_string('secondaryemailreport', 'local_secondaryemail'),
        new moodle_url('/local/secondaryemail/report.php'),
        'local/secondaryemail:viewreport'
    );
    if ($ADMIN->locate('accounts')) {
        $ADMIN->add('accounts', $reportpage);
    } else if ($ADMIN->locate('users')) {
        $ADMIN->add('users', $reportpage);
    }
}
