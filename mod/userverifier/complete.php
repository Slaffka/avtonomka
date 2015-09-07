<?php

require_once("../../config.php");
require_once("lib.php");
require_once($CFG->libdir . '/completionlib.php');

$id = required_param('id', PARAM_INT);

if (!$cm = get_coursemodule_from_id('userverifier', $id)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error('coursemisconf');
}

if (! $selfiemodule = $DB->get_record("userverifier", array("id"=>$cm->instance))) {
    print_error('invalidcoursemodule');
}


$a = (object) array('success'=>false, 'redirect'=>false, 'errormessage'=>'');
$a->redirect = $CFG->wwwroot. "/course/view.php?id={$course->id}";
$a->success = true;

$dataobj = new StdClass();
$dataobj->userverifier = $selfiemodule->id;
$dataobj->userid = $USER->id;
$dataobj->photo = required_param('image', PARAM_RAW);
$dataobj->timecreated = time();
if(!$photoid = $DB->insert_record('userverifier_photo', $dataobj)){
    $a->errormessage = 'Ошибка записи в базу данных';
}

// Update completion state
$completion = new completion_info($course);
if ($completion->is_enabled($cm) && $selfiemodule->completionphoto && $photoid) {
    $completion->update_state($cm, COMPLETION_COMPLETE);
}

echo json_encode($a);
die();