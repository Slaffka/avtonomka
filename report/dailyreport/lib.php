<?php

/**
 * Public API of the daily report.
 *
 * Defines the APIs used by daily reports
 *
 * @package    report_dailyreport
 * @copyright  2015 Pinta webware
 * @license    All rights reserved
 */

defined('MOODLE_INTERNAL') || die;

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function report_dailyreport_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/dailyreport:view', $context)) {
        $url = new moodle_url('/report/dailyreport/index.php', array('id'=>$course->id));
        $navigation->add(get_string('pluginname', 'report_dailyreport'), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}