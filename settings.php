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
 * mmu_course_overview block settings
 *
 * @package    block_mmu_course_overview
 * @copyright  2013 University of London Computer Centre
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('block_mmu_course_overview/defaultmaxcourses', new lang_string('defaultmaxcourses', 'block_mmu_course_overview'),
        new lang_string('defaultmaxcoursesdesc', 'block_mmu_course_overview'), 10, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('block_mmu_course_overview/forcedefaultmaxcourses', new lang_string('forcedefaultmaxcourses', 'block_mmu_course_overview'),
        new lang_string('forcedefaultmaxcoursesdesc', 'block_mmu_course_overview'), 1, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('block_mmu_course_overview/showchildren', new lang_string('showchildren', 'block_mmu_course_overview'),
        new lang_string('showchildrendesc', 'block_mmu_course_overview'), 1, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('block_mmu_course_overview/showwelcomearea', new lang_string('showwelcomearea', 'block_mmu_course_overview'),
        new lang_string('showwelcomeareadesc', 'block_mmu_course_overview'), 1, PARAM_INT));


    // Start of academic year

    $options = array('1'=>get_string('january', 'block_mmu_course_overview'),
                     '2'=>get_string('february', 'block_mmu_course_overview'),
                     '3'=>get_string('march', 'block_mmu_course_overview'),
                     '4'=>get_string('april', 'block_mmu_course_overview'),
                     '5'=>get_string('may', 'block_mmu_course_overview'),
                     '6'=>get_string('june', 'block_mmu_course_overview'),
                     '7'=>get_string('july', 'block_mmu_course_overview'),
                     '8'=>get_string('august', 'block_mmu_course_overview'),
                     '9'=>get_string('september', 'block_mmu_course_overview'),
                     '10'=>get_string('october', 'block_mmu_course_overview'),
                     '11'=>get_string('november', 'block_mmu_course_overview'),
                     '12'=>get_string('december', 'block_mmu_course_overview'));

    $settings->add(new admin_setting_configselect('block_mmu_course_overview/academicyearstart', get_string('academicyearstart', 'block_mmu_course_overview'),
        get_string('configacademicyearstart', 'block_mmu_course_overview'), 'all', $options));


}




