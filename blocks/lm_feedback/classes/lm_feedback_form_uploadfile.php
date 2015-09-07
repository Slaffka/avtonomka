<?php
/**
 * Created by PhpStorm.
 * User: �������������
 * Date: 12.05.2015
 * Time: 21:31
 */

class lm_feedback_form_uploadfile extends moodleform
{
    public function definition() {
        global $PAGE;

        $mform = & $this->_form;

        $mform->addElement('file', 'repo_upload_file', '', 'class="fileupload" multiple="multiple"');
        $mform->addElement('hidden', 'itemid', file_get_unused_draft_itemid());
        $mform->addElement('hidden', 'contextid', $PAGE->context->id);

        $mform->addElement('button', 'btn-upload', 'Прикрепить файл',"class='btn-upload-new'");
        $mform->addElement('submit', 'btn', 'Отправить', "class='btnsubmit btn btn-primary' data-loading-text='Отпраляется...' ");
    }

    public function validation($data, $files) {

        return array();
    }


}