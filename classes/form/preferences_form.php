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

namespace local_secondaryemail\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * User preferences form for secondary email notifications.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preferences_form extends \moodleform {
    /**
     * Define form elements.
     */
    public function definition(): void {
        $mform = $this->_form;
        $grouped = $this->_customdata['grouped'] ?? [];
        $userdisabledlist = $this->_customdata['userdisabledlist'] ?? [];

        foreach ($grouped as $component => $componentproviders) {
            if (empty($componentproviders)) {
                continue;
            }

            $componentname = $componentproviders[0]->componentname ?? $component;
            $mform->addElement('header', 'component_' . md5($component), $componentname);

            foreach ($componentproviders as $provider) {
                $providerid = $provider->providerid;
                $providername = $provider->providername;
                $elementname = 'disabled[' . $providerid . ']';
                $isuserdisabled = in_array($providerid, $userdisabledlist, true);

                $mform->addElement('advcheckbox', $elementname, $providername);
                $mform->setDefault($elementname, $isuserdisabled ? 1 : 0);
                $mform->setType($elementname, PARAM_BOOL);
            }
        }

        $this->add_action_buttons(false, get_string('savechanges'));
    }
}
