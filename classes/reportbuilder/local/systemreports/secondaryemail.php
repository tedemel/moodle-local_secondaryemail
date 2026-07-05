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

namespace local_secondaryemail\reportbuilder\local\systemreports;

use core\context\system;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\helpers\user_profile_fields;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\system_report;
use lang_string;
use moodle_url;

/**
 * Secondary email management report.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class secondaryemail extends system_report {
    /**
     * Initialise report.
     */
    protected function initialise(): void {
        global $CFG;

        $entityuser = new user();
        $entityuseralias = $entityuser->get_table_alias('user');

        $this->set_main_table('user', $entityuseralias);
        $this->add_entity($entityuser);

        $this->add_base_fields("{$entityuseralias}.id, {$entityuseralias}.deleted, {$entityuseralias}.mnethostid");

        $guestparam = database::generate_param_name();
        $this->add_base_condition_sql("{$entityuseralias}.deleted = 0 AND {$entityuseralias}.id <> :{$guestparam}", [
            $guestparam => $CFG->siteguest,
        ]);

        $this->add_columns();
        $this->add_filters();

        $this->set_downloadable(true);
    }

    /**
     * Validate access.
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('local/secondaryemail:viewreport', system::instance());
    }

    /**
     * Add report columns.
     */
    private function add_columns(): void {
        $entityuser = $this->get_entity('user');
        $entityuseralias = $entityuser->get_table_alias('user');
        $contextsystem = system::instance();

        $this->add_column($entityuser->get_column('fullnamewithpicturelink'));
        $this->add_column($entityuser->get_column('email'));
        $this->add_column($entityuser->get_column('username'));

        $profilefields = new user_profile_fields($entityuseralias . '.id', $entityuser->get_entity_name());
        foreach ($profilefields->get_columns() as $profilecolumn) {
            if ($profilecolumn->get_name() !== 'profilefield_secondaryemail') {
                continue;
            }

            $profilecolumn
                ->set_title(new lang_string('secondaryemailfieldname', 'local_secondaryemail'))
                ->add_callback(static function ($value, \stdClass $row) use ($contextsystem): string {
                    global $OUTPUT, $PAGE;

                    $email = trim((string) $value);
                    $canmanage = has_capability('local/secondaryemail:manage', $contextsystem) &&
                        has_capability('moodle/user:update', $contextsystem);
                    $renderable = new \local_secondaryemail\output\report_email_cell(
                        (int) $row->userid,
                        $email,
                        $canmanage,
                        is_mnet_remote_user($row)
                    );

                    $renderer = $PAGE->get_renderer('local_secondaryemail');
                    return $renderer->render($renderable);
                });

            $this->add_column($profilecolumn);
            break;
        }

        $this->set_initial_sort_column('user:fullnamewithpicturelink', SORT_ASC);
        $this->set_default_no_results_notice(new lang_string('nousersfound', 'moodle'));
    }

    /**
     * Add report filters.
     */
    private function add_filters(): void {
        $entityuser = $this->get_entity('user');
        $entityuseralias = $entityuser->get_table_alias('user');

        $filters = [
            'user:fullname',
            'user:email',
            'user:username',
        ];
        $this->add_filters_from_entities($filters);

        $prefverified = database::generate_param_name();
        $preftoken = database::generate_param_name();
        $prefpending = database::generate_param_name();
        $prefdisabled = database::generate_param_name();
        $secondaryfieldsql = "(SELECT id FROM {user_info_field} WHERE shortname = 'secondaryemail')";
        $pendingtokensql = "EXISTS (
            SELECT 1
              FROM {user_info_data} uid
              LEFT JOIN {user_preferences} upt
                ON upt.userid = uid.userid
               AND upt.name = :{$preftoken}
              LEFT JOIN {user_preferences} upp
                ON upp.userid = uid.userid
               AND upp.name = :{$prefpending}
             WHERE uid.userid = {$entityuseralias}.id
               AND uid.fieldid = {$secondaryfieldsql}
               AND uid.data <> ''
               AND upt.value <> ''
               AND upp.value = uid.data
        )";
        $verifiedsql = "EXISTS (
            SELECT 1
              FROM {user_info_data} uid
              LEFT JOIN {user_preferences} up
                ON up.userid = uid.userid
               AND up.name = :{$prefverified}
             WHERE uid.userid = {$entityuseralias}.id
               AND uid.fieldid = {$secondaryfieldsql}
               AND uid.data <> ''
               AND up.value = uid.data
        )";
        $disabledsql = "EXISTS (
            SELECT 1
              FROM {user_preferences} up
             WHERE up.userid = {$entityuseralias}.id
               AND up.name = :{$prefdisabled}
               AND up.value = '1'
        )";
        $secondaryemailstatussql = "CASE
            WHEN {$disabledsql} THEN 3
            WHEN {$verifiedsql} THEN 2
            WHEN {$pendingtokensql} THEN 1
            ELSE 0
        END";
        $this->add_filter((new filter(
            select::class,
            'secondaryemailstatus',
            new lang_string('secondaryemailstatusfilter', 'local_secondaryemail'),
            $this->get_entity('user')->get_entity_name(),
        ))
            ->set_field_sql($secondaryemailstatussql, [
                $prefverified => 'local_secondaryemail_verified',
                $preftoken => 'local_secondaryemail_token',
                $prefpending => 'local_secondaryemail_pending',
                $prefdisabled => 'local_secondaryemail_disabled',
            ])
            ->set_options([
                1 => new lang_string('secondaryemailpendingtag', 'local_secondaryemail'),
                2 => new lang_string('secondaryemailverifiedtag', 'local_secondaryemail'),
                3 => new lang_string('secondaryemailblockedtag', 'local_secondaryemail'),
            ]));
    }
}
