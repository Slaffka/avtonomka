<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/blocks/manage/lib.php');

class block_lm_myindex extends lm_profile_block {

    public $details_btn = false;

    public function init()
    {
        global $CFG;
        $this->details_url = $CFG->wwwroot.'/blocks/manage/?_p=mycourses';

        parent::init();
    }

    public function widget_data($renderer){

        if($staffer = lm_staffer::self()) {
            $stages = lm_matrix::stages();

            $stage = false;
            if (isset($stages[$staffer->stageid])) {
                $stage = $stages[$staffer->stageid];
                $stage->progress = $staffer->get_stage_progress($stage->id);
            }

            $renderer->tpl->stage = $stage;
            $renderer->tpl->details_url = $this->details_url;
        }

        return true;
    }
}
