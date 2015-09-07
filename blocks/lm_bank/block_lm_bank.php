<?php

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/blocks/manage/lib.php');

class block_lm_bank extends lm_profile_block {
    public $details_btn = false;

    public function init()
    {
        global $CFG, $USER;

        $this->details_url = "{$CFG->wwwroot}/blocks/manage/?_p=lm_bank&id={$USER->id}";
        parent::init();
    }

    function has_config()
    {
        return true;
    }

    public function widget_data($renderer)
    {
        global $USER;
        $tpl = $renderer->tpl;
        $tpl->userid = $USER->id;
        $tpl->balance = lm_bank::me()->get_balance();

        return true;
    }
}
