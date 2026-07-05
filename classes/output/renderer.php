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

/**
 * Renderer for local_secondaryemail output.
 *
 * @package    local_secondaryemail
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render a report email cell.
     *
     * @param report_email_cell $renderable
     * @return string
     */
    public function render_report_email_cell(report_email_cell $renderable): string {
        $data = $renderable->export_for_template($this);
        $data['actionmenuhtml'] = $renderable->build_action_menu($this);

        return $this->render_from_template('local_secondaryemail/report_email_cell', $data);
    }
}
