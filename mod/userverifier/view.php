<?php
/**
 * Userverifier module main user interface
 *
 * @package    mod_userverifier
 * @copyright  2015 Maxim Lobov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/mod/userverifier/lib.php");
require_once($CFG->libdir . '/completionlib.php');

$PAGE->requires->js('/blocks/lm_report/js/statistics_include.js', true);

$id       = optional_param('id', 0, PARAM_INT);        // Course module ID


$cm = get_coursemodule_from_id('userverifier', $id, 0, false, MUST_EXIST);
$userverifier = $DB->get_record('userverifier', array('id'=>$cm->instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
//require_capability('mod/url:view', $context);


// Update 'viewed' state if required by completion system
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/userverified/view.php', array('id' => $cm->id));



$PAGE->set_title($course->shortname.': '.$url->name);
$PAGE->set_heading($course->fullname);
//$PAGE->set_activity_record($url);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($url->name), 2);

include($CFG->dirroot."/mod/userverifier/tpl/main.tpl");

echo $OUTPUT->footer();
die;
