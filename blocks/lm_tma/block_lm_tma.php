<?php

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/blocks/manage/lib.php');

class block_lm_tma extends lm_profile_block {

    public $details_btn = FALSE;
    public $pageurl = '/blocks/manage/?_p=lm_tma';

    public function init()
    {
        global $CFG;

        $this->details_url = $CFG->wwwroot.'/blocks/manage/?_p=lm_tma';

        parent::init();
    }


    public function widget_data($renderer)
    {
        //$this->page->requires->js("/blocks/{$this->blockname}/js/donut/donut.js");
        $this->page->requires->js("/blocks/{$this->blockname}/js/widget.js");
        $this->page->requires->jquery_plugin('chart.wave',  'theme_tibibase');

        $tpl = $renderer->tpl;
        $tpl->tma = false;
        if ( $tmas = lm_tma::tma_for_user() ) {
            $tpl->tma = true;
        }

        return true;
    }

}
