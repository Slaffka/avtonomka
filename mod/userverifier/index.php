<?php
/**
 * List of photos in course
 *
 * @package    mod_userverifier
 * @copyright  2015 Maxim Lobov  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_once("lib.php");

$courseid = required_param('id', PARAM_INT);

if (!$course = $DB->get_record("course", array("id" => $courseid))) {
    print_error('coursemisconf');
}

require_course_login($course, true);

$out = "";
$sql = "SELECT up.*
              FROM {userverifier_photo} up
              JOIN {userverifier} uv ON uv.id=up.userverifier
              WHERE uv.course={$courseid}
              ORDER BY timecreated DESC";
if($selfies = $DB->get_records_sql($sql)){
    foreach($selfies as $selfie){
        $out .= '<img src="'.$selfie->photo.'">';
    }
}


$PAGE->set_url('/mod/userverified/штвуч.php', array('id' => $course->id));
$PAGE->set_title("Селфи в этом курсе");
$PAGE->set_heading("Все селфи этого курса");
//$PAGE->set_activity_record($url);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($url->name), 2);

echo $out;

echo $OUTPUT->footer();
die;