<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Admin settings for local_atrisk.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_atrisk_settings', get_string('pluginname', 'local_atrisk'));
    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configtext(
            'local_atrisk/inactivedays',
            get_string('inactivedays', 'local_atrisk'),
            get_string('inactivedays_desc', 'local_atrisk'),
            14,
            PARAM_INT
        ));
        $settings->add(new admin_setting_configtext(
            'local_atrisk/misseddeadlines',
            get_string('misseddeadlines', 'local_atrisk'),
            get_string('misseddeadlines_desc', 'local_atrisk'),
            2,
            PARAM_INT
        ));
        $settings->add(new admin_setting_configtext(
            'local_atrisk/gradebelow',
            get_string('gradebelow', 'local_atrisk'),
            get_string('gradebelow_desc', 'local_atrisk'),
            50,
            PARAM_INT
        ));
    }
    $ADMIN->add('localplugins', $settings);
}
