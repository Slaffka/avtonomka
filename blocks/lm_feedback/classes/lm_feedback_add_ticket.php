<?php
/**
 * Created by PhpStorm.
 * User: Dominik
 * Date: 05.05.2015
 * Time: 16:01
 */
global $CFG;
require_once $CFG->libdir.'/formslib.php';

class lm_feedback_add_ticket extends moodleform
{
    public function definition() {
        global $PAGE, $DB, $USER;

        $mform = & $this->_form;

        $subjs = array();
        $subjs[0] = 'Выберите тему обращения';
        $subjects = $DB->get_records("lm_feedback_subjects");
        if ( !empty($subjects) ) {
            foreach ($subjects as $subject) {
                $subjs[$subject->id] = $subject->name;
            }
        }

        $ticket = $DB->get_record_select("lm_feedback", "userid = {$USER->id} AND send = 0");
        $mform->addElement('select', 'subject', '', $subjs);

        $mform->addElement('textarea', 'message', '', "class='feedback-text' placeholder='Введите ваше сообщение'");

        if ( $ticket ) {
            $mform->setDefault('message', $ticket->message);
            $mform->setDefault('subject', $ticket->subjectid);
        }
    }



}