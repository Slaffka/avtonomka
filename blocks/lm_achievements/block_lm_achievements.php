<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/manage/lib.php');

class block_lm_achievements extends lm_profile_block {

    public $details_btn = FALSE;
    public $details_url = FALSE;

    public function widget_data($renderer){
        return true;
    }

    public function details_content()
    {
        return 'Страница в разработке';
    }
}
