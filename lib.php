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
 * Library functions for local_secondaryemail.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Extend the navigation to add secondary email report link.
 *
 * @param global_navigation $navigation
 */
function local_secondaryemail_extend_navigation(global_navigation $navigation): void {
    // Only show on admin pages for users with the right capability.
    if (!has_capability('local/secondaryemail:viewreport', context_system::instance())) {
        return;
    }

    // Add to users node in admin navigation.
    $usersnode = $navigation->find('users', navigation_node::TYPE_SETTING);
    if ($usersnode) {
        $url = new moodle_url('/local/secondaryemail/report.php');
        $usersnode->add(
            get_string('secondaryemailreport', 'local_secondaryemail'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'secondaryemailreport',
            new pix_icon('i/email', '')
        );
    }
}

/**
 * Extend settings navigation for admin area.
 *
 * @param settings_navigation $navigation
 * @param context $context
 */
function local_secondaryemail_extend_settings_navigation(settings_navigation $navigation, context $context): void {
    // Add link in user admin section.
    if ($context instanceof context_system) {
        $usersnode = $navigation->find('users', navigation_node::TYPE_SETTING);
        if ($usersnode && has_capability('local/secondaryemail:viewreport', $context)) {
            $url = new moodle_url('/local/secondaryemail/report.php');
            $usersnode->add(
                get_string('secondaryemailreport', 'local_secondaryemail'),
                $url,
                navigation_node::TYPE_SETTING,
                null,
                'secondaryemailreport',
                new pix_icon('i/email', '')
            );
        }
    }
}

/**
 * Extend user preferences navigation to add secondary email notification settings link.
 *
 * @param navigation_node $navigation
 * @param stdClass $user
 * @param context_user $usercontext
 * @param stdClass $course
 * @param context_course $coursecontext
 */
function local_secondaryemail_extend_navigation_user_settings(
    navigation_node $navigation,
    stdClass $user,
    context_user $usercontext,
    stdClass $course,
    context_course $coursecontext
): void {
    global $USER;

    // Check if user customization is enabled.
    if (!get_config('local_secondaryemail', 'allowuserexclusions')) {
        return;
    }

    // Check permissions.
    $systemcontext = context_system::instance();
    $canedit = false;

    if ((int) $user->id === (int) $USER->id) {
        // User viewing their own settings.
        $canedit = has_capability('local/secondaryemail:configureown', $systemcontext);
    } else {
        // Admin viewing another user's settings.
        $canedit = has_capability('local/secondaryemail:manage', $usercontext);
    }

    if (!$canedit) {
        return;
    }

    // Find the messaging node to add our link nearby.
    $url = new moodle_url('/local/secondaryemail/preferences.php', ['userid' => $user->id]);
    $node = navigation_node::create(
        get_string('userpreferencestitle', 'local_secondaryemail'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'secondaryemail_preferences',
        new pix_icon('i/email', '')
    );

    $navigation->add_node($node);
}

/**
 * Adjust the display of the secondary email field in the profile tree.
 *
 * Adds a verification status indicator to the secondary email field
 * when it has not been verified yet.
 *
 * @param \core_user\output\myprofile\tree $tree The profile tree object.
 * @param \stdClass $user The user whose profile is being displayed.
 * @param bool $iscurrentuser Whether the displayed profile is the current user's.
 * @param \stdClass|null $course The course context or null.
 * @return void
 */
function local_secondaryemail_myprofile_navigation(
    \core_user\output\myprofile\tree $tree,
    \stdClass $user,
    bool $iscurrentuser,
    ?\stdClass $course = null
): void {
    $userid = (int) $user->id;
    $nodes = $tree->nodes;
    $nodename = 'custom_field_secondaryemail';

    $current = \local_secondaryemail\verification::get_secondary_email_value($userid);
    $canmanage = has_capability('local/secondaryemail:manage', context_system::instance());

    // If admin can manage, show a link to the report even without secondary email set.
    if ($canmanage) {
        $reporturl = new moodle_url('/local/secondaryemail/report.php');
        $reportnode = new \core_user\output\myprofile\node(
            'administration',
            'secondaryemail_report',
            get_string('secondaryemailreport', 'local_secondaryemail'),
            null,
            $reporturl
        );
        $tree->add_node($reportnode);
    }

    if (!isset($nodes[$nodename])) {
        return;
    }

    if ($current === '') {
        return;
    }

    // Build status indicator.
    $isdisabled = \local_secondaryemail\verification::is_disabled($userid);
    $isverified = \local_secondaryemail\verification::is_verified($userid, $current);

    $statustext = '';
    $statusclass = '';

    if ($isdisabled) {
        $statustext = get_string('secondaryemailblockedtag', 'local_secondaryemail');
        $statusclass = 'badge badge-secondary text-bg-secondary';
    } else if ($isverified) {
        $statustext = get_string('secondaryemailverifiedtag', 'local_secondaryemail');
        $statusclass = 'badge badge-success text-bg-success';
    } else {
        $statustext = get_string('secondaryemailnotverified', 'local_secondaryemail');
        $statusclass = 'badge badge-warning text-bg-warning';
    }

    // Build relationship tag (only show to users who can manage).
    $relationshiptag = '';
    if ($canmanage && \local_secondaryemail\verification::is_tagging_enabled()) {
        $relationship = \local_secondaryemail\verification::get_relationship($userid);
        if ($relationship !== '') {
            $relationshiptext = \local_secondaryemail\verification::get_tag_display_name($relationship);
            $relationshiptag = ' ' . \html_writer::span($relationshiptext, 'badge badge-info text-bg-info');
        }
    }

    // Add status node.
    $statusnodename = $nodename . '_status';
    if (!isset($nodes[$statusnodename])) {
        $status = \html_writer::span($statustext, $statusclass) . $relationshiptag;
        $statusnode = new \core_user\output\myprofile\node(
            'contact',
            $statusnodename,
            '',
            $nodename,
            null,
            $status
        );
        $tree->add_node($statusnode);
    }
}
