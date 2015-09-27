<?php

defined('MOODLE_INTERNAL') || die();
global $CFG;

class block_lm_report extends lm_profile_block {
    public $details_btn = false;

    public function init()
    {
        global $CFG;

        $this->details_url = $CFG->wwwroot.'/blocks/manage/?_p=lm_report';
        parent::init();
    }

    function has_config()
    {
        return true;
    }

    public function widget_data($renderer)
    {

        return true;
    }
}