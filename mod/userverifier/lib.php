<?php
/**
 * Mandatory public API of userverifier module
 *
 * @package    mod_userverifier
 * @copyright  2015 Maxim Lobov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * @uses FEATURE_COMPLETION_HAS_RULES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function userverifier_supports($feature) {
    switch($feature) {
        case FEATURE_COMPLETION_HAS_RULES:    return true;

        default: return null;
    }
}

/**
 * Add userverifier instance.
 * @param object $data
 * @param object $mform
 * @return int new userverifier instance id
 */
function userverifier_add_instance($data, $mform) {
    global $CFG, $DB;

    $data->timemodified = $data->timemodified = time();
    $data->id = $DB->insert_record('userverifier', $data);

    return $data->id;
}

/**
 * Update userverifier instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function userverifier_update_instance($data, $mform) {
    global $CFG, $DB;

    $data->timemodified = time();
    $data->id           = $data->instance;

    $DB->update_record('userverifier', $data);

    return true;
}

/**
 * Delete userverifier instance.
 * @param int $id
 * @return bool true
 */
function userverifier_delete_instance($id) {
    global $DB;

    if (!$userverifier = $DB->get_record('userverifier', array('id'=>$id))) {
        return false;
    }

    // note: all context files are deleted automatically
    $DB->delete_records('userverifier', array('id'=>$userverifier->id));

    return true;
}

/**
 * Obtains the automatic completion state for this userverifier based on the condition
 * in userverifier settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function userverifier_get_completion_state($course, $cm, $userid, $type){
    global $DB;

    $userverifier = $DB->get_record('userverifier', array('id'=>$cm->instance), '*', MUST_EXIST);

    if( $userverifier->completionphoto ){
        $select = "userverifier={$cm->instance} AND userid={$userid} AND (status=0 OR status=1)";
        return $DB->record_exists_select('userverifier_photo', $select);
    }

    return $type;
}
