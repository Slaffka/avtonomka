<?php
/**
 * Userverifier configuration form
 *
 * @package    mod_userverifier
 * @copyright  2015 Maxim Lobov  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/userverifier/lib.php');

class mod_userverifier_mod_form extends moodleform_mod {
    function definition() {
        global $CFG, $DB;
        $mform = $this->_form;

        $config = get_config('url');

        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->add_intro_editor($config->requiremodintro);


        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons();
    }

    public function add_completion_rules() {
        $mform =& $this->_form;
        $items = array();

        $mform->addElement('checkbox', 'completionphoto', get_string('photorequre', 'userverifier'), get_string('photorequredetails', 'userverifier'));;
        $mform->setType('completionphoto', PARAM_BOOL);
        $items[] = 'completionphoto';


        return $items;
    }

    public function completion_rule_enabled($data) {
        return !empty($data['completionphoto']);
    }

}
