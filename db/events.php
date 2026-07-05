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
 * Event observers for local_secondaryemail.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\user_created',
        'callback' => '\local_secondaryemail\observer::user_created',
    ],
    [
        'eventname' => '\core\event\user_updated',
        'callback' => '\local_secondaryemail\observer::user_updated',
    ],
    [
        'eventname' => '\core\event\notification_sent',
        'callback' => '\local_secondaryemail\observer::notification_sent',
    ],
    // Profile field protection observers.
    [
        'eventname' => '\core\event\user_info_field_deleted',
        'callback' => '\local_secondaryemail\observer::profile_field_deleted',
    ],
    [
        'eventname' => '\core\event\user_info_field_updated',
        'callback' => '\local_secondaryemail\observer::profile_field_updated',
    ],
    [
        'eventname' => '\core\event\user_info_category_deleted',
        'callback' => '\local_secondaryemail\observer::profile_category_deleted',
    ],
    [
        'eventname' => '\core\event\user_info_category_updated',
        'callback' => '\local_secondaryemail\observer::profile_category_updated',
    ],
];
