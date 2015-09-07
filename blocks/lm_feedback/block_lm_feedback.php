<?php

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/blocks/manage/lib.php');

class block_lm_feedback extends lm_profile_block {

    public function init()
    {
        global $CFG;

        $this->details_btn = false;
        $this->details_url = $CFG->wwwroot.'/blocks/manage/?_p=lm_feedback';

        parent::init();
    }


    public function widget_data($renderer)
    {
        $this->page->requires->js("/blocks/{$this->blockname}/js/script.js");
        $this->page->requires->js("/blocks/{$this->blockname}/js/file-upload.js");

        $tpl = $renderer->tpl;
        $form = new lm_feedback_add_ticket();
        $tpl->form_addticket = $form->render();
        $form = new lm_feedback_form_uploadfile();
        $tpl->form_fileupload = $form->render();
        $tpl->files = lm_feedback::get_count_files();

        return true;
    }
}
