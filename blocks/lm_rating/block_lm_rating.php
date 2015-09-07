<?php

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/blocks/manage/lib.php');

class block_lm_rating extends lm_profile_block {
    public $details_btn = false;

    public function init()
    {
        global $CFG;

        $this->details_url = $CFG->wwwroot.'/blocks/manage/?_p=lm_rating';
        parent::init();
    }

    function has_config()
    {
        return true;
    }

    public function widget_data($renderer)
    {
        $this->page->requires->js("/theme/tibibase/jquery/chart/line.js");
        $this->page->requires->js("/blocks/{$this->blockname}/js/widget.js");

        $tpl = $renderer->tpl;
        $myrating = lm_rating::me();

        $tpl->incity = $myrating->incity();
        $tpl->inregion = $myrating->inregion();
        $tpl->incountry = $myrating->incountry();

        return true;
    }
}
