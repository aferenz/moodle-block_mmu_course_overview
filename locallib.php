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
 * Helper functions for mmu_course_overview block
 *
 * @package    block_mmu_course_overview
 * @copyright  2012 Adam Olley <adam.olley@netspot.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Display overview for courses
 *
 * @param array $courses courses for which overview needs to be shown
 * @return array html overview
 */
function block_mmu_course_overview_get_overviews($courses) {
    $htmlarray = array();
    if ($modules = get_plugin_list_with_function('mod', 'print_overview')) {
        foreach ($modules as $fname) {
            $fname($courses,$htmlarray);
        }
    }
    return $htmlarray;
}

/**
 * Sets user preference for maximum courses to be displayed in course_overview block
 *
 * @param int $number maximum courses which should be visible
 */
function block_mmu_course_overview_update_mynumber($number) {
    set_user_preference('course_overview_number_of_courses', $number);
}

/**
 * Sets user course sorting preference in mmu_course_overview block
 *
 * @param array $sortorder sort order of course
 */
function block_mmu_course_overview_update_myorder($sortorder) {
    set_user_preference('mmu_course_overview_course_order', serialize($sortorder));
}

/**
 * Returns shortname of activities in course
 *
 * @param int $courseid id of course for which activity shortname is needed
 * @return string|bool list of child shortname
 */
function block_mmu_course_overview_get_child_shortnames($courseid) {
    global $DB;
    $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
    $sql = "SELECT c.id, c.shortname, $ctxselect
            FROM {enrol} e
            JOIN {course} c ON (c.id = e.customint1)
            JOIN {context} ctx ON (ctx.instanceid = e.customint1)
            WHERE e.courseid = :courseid AND e.enrol = :method AND ctx.contextlevel = :contextlevel ORDER BY e.sortorder";
    $params = array('method' => 'meta', 'courseid' => $courseid, 'contextlevel' => CONTEXT_COURSE);

    if ($results = $DB->get_records_sql($sql, $params)) {
        $shortnames = array();
        // Preload the context we will need it to format the category name shortly.
        foreach ($results as $res) {
            context_helper::preload_from_record($res);
            $context = context_course::instance($res->id);
            $shortnames[] = format_string($res->shortname, true, $context);
        }
        $total = count($shortnames);
        $suffix = '';
        if ($total > 10) {
            $shortnames = array_slice($shortnames, 0, 10);
            $diff = $total - count($shortnames);
            if ($diff > 1) {
                $suffix = get_string('shortnamesufixprural', 'block_mmu_course_overview', $diff);
            } else {
                $suffix = get_string('shortnamesufixsingular', 'block_mmu_course_overview', $diff);
            }
        }
        $shortnames = get_string('shortnameprefix', 'block_mmu_course_overview', implode('; ', $shortnames));
        $shortnames .= $suffix;
    }

    return isset($shortnames) ? $shortnames : false;
}

/**
 * Returns maximum number of courses which will be displayed in mmu_course_overview block
 *
 * @return int maximum number of courses
 */
function block_mmu_course_overview_get_max_user_courses() {
    // Get block configuration
    $config = get_config('block_mmu_course_overview');
    $limit = $config->defaultmaxcourses;

    // If max course is not set then try get user preference
    if (empty($config->forcedefaultmaxcourses)) {
        $limit = get_user_preferences('mmu_course_overview_number_of_courses', $limit);
    }
    return $limit;
}

/**
 * Return sorted list of user courses
 *
 * @return array list of sorted courses and count of courses.
 */
function block_mmu_course_overview_get_sorted_courses() {
    global $USER;

    $limit = block_mmu_course_overview_get_max_user_courses();

    $courses = enrol_get_my_courses('id, shortname, fullname, modinfo, sectioncache');
    $site = get_site();

    if (array_key_exists($site->id,$courses)) {
        unset($courses[$site->id]);
    }

    foreach ($courses as $c) {
        if (isset($USER->lastcourseaccess[$c->id])) {
            $courses[$c->id]->lastaccess = $USER->lastcourseaccess[$c->id];
        } else {
            $courses[$c->id]->lastaccess = 0;
        }
    }

    // Get remote courses.
    $remotecourses = array();
    if (is_enabled_auth('mnet')) {
        $remotecourses = get_my_remotecourses();
    }
    // Remote courses will have -ve remoteid as key, so it can be differentiated from normal courses
    foreach ($remotecourses as $id => $val) {
        $remoteid = $val->remoteid * -1;
        $val->id = $remoteid;
        $courses[$remoteid] = $val;
    }

    $order = array();
    if (!is_null($usersortorder = get_user_preferences('mmu_course_overview_course_order'))) {
        $order = unserialize($usersortorder);
    }

    $sortedcourses = array();
    $counter = 0;
    // Get courses in sort order into list.
    foreach ($order as $key => $cid) {
        if (($counter >= $limit) && ($limit != 0)) {
            break;
        }

        // Make sure user is still enroled.
        if (isset($courses[$cid])) {
            $sortedcourses[$cid] = $courses[$cid];
            $counter++;
        }
    }
    // Append unsorted courses if limit allows
    foreach ($courses as $c) {
        if (($limit != 0) && ($counter >= $limit)) {
            break;
        }
        if (!in_array($c->id, $order)) {
            $sortedcourses[$c->id] = $c;
            $counter++;
        }
    }

    // From list extract site courses for overview.
    $sitecourses = array();
    foreach ($sortedcourses as $key => $course) {
        if ($course->id > 0) {
            $sitecourses[$key] = $course;
        }
    }


    // Calculate current academic year.

    // Retrieve start of academic year from the configuration.
    $academicyearstart = intval(get_config('block_mmu_course_overview', 'academicyearstart'));

    $time = time();
    $year = date('y', $time);

    if(date('n', $time) < $academicyearstart){
        $currentacademicyear = ($year - 1).$year;  // 12/13

      /*  $previousacademicyear1 = ($year - 2).$year; // 11/12
        $previousacademicyear2 = ($year - 3).$year; // 10/11
        $previousacademicyear3 = ($year - 3).$year; // 09/10
      */

    }else{
        $currentacademicyear = ($year).($year + 1);
    }

    // Build arrays with current, previous year and orphaned courses.
    $currentyearcourses = array();
    $previousyearcourses = array();
    $orphanedcourses = array();

    // Retrieve all courses that a user is enrolled on
    // alternate function is: enrol_get_users_courses($USER->id);
    $enrolled_courses = enrol_get_all_users_courses($USER->id);
    /*
     * TODO: The following logic need to be re-written to work dynamically every year.
     */

    foreach($enrolled_courses as $course){
       // preg_match('_1011_',$course->shortname,$year10);
       // preg_match('_1112_',$course->shortname,$year11);

        //pregmatch for current year courses
     //   preg_match($currentacademicyear, $course->shortname,$currentyear);
        //pregmatch for courses without academic years that should appear within this year's courses. eg. 'Student Resource Area', etc.
        preg_match('/[0-9]{4}$/', $course->shortname, $academicyear);
   /*     if(!empty($year10)|| !empty($year11)){
            $previousyearcourses[] = $course;
        }else{
            $currentyearcourses[] = $course;
        }*/
        $currentyear ='';

      if ($academicyear){
       // Take 4 digits and check whether it is a valid academic years.
       $firstyear = (substr($academicyear[0], -4, 2));
       $secondyear = (substr($academicyear[0], -2, 2));
        if ($secondyear - $firstyear != 1){
            // academic year is invalid
            $academicyear = '';

            // check whether is is a current academic year
        } elseif ($currentacademicyear == $academicyear[0]){
            $currentyear = $currentacademicyear;
        }
      }
        // If the course's shortname ends with the current academic year OR 4-digit suffix that is not a valid academic year,
        // courses will be grouped under 'This Year's Courses'.
        if(!empty($currentyear) || !($academicyear)){
            $currentyearcourses[] = $course;
        }else{
            $previousyearcourses[] = $course;
        }
    }

    return array($sortedcourses, $sitecourses, count($courses), $previousyearcourses, $currentyearcourses, $orphanedcourses);
}

