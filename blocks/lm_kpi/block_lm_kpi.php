<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/blocks/manage/lib.php');

class block_lm_kpi extends lm_profile_block {
    public $details_btn = false;

    // Так уж решили
    public $details_url = '/blocks/manage/?_p=lm_myteam';

    public function widget_data($renderer){
        global $USER;

        $this->page->requires->js("/blocks/{$this->blockname}/js/donut/donut.js");
        $this->page->requires->js("/blocks/{$this->blockname}/js/widget.js");

        if( $posid = lm_position::i($USER->id)->get_id() ) {

            $kpilist = new lm_kpi_list($posid, $USER->id);
            $kpiitems = $kpilist->get_latest();
            if (!$kpiitems) {
                $kpiitems = $kpilist->items_by_pos();
            }

            $tpl = $this->renderer->tpl;
            $tpl->kpiitems = $kpiitems;
            /*$tpl->kpiitems[] = array_shift($kpiitems);
            $tpl->kpiitems[] = array_shift($kpiitems);
            $tpl->kpiitems[] = array_shift($kpiitems);
            $tpl->kpiitems[] = array_shift($kpiitems);*/
            //$tpl->kpiitems[] = $tpl->kpiitems[0];
            $tpl->url = $this->details_url;
        }

        return true;
    }

    public function details_pre_hook(){
        $this->page->requires->js("/blocks/{$this->blockname}/js/donut/donut.js");

        $this->page->requires->css("/blocks/lm_kpi/js/rangeinput/rangeinput.css");
        $this->page->requires->js("/blocks/{$this->blockname}/js/rangeinput/rangeinput.js");

        $this->page->requires->jquery_plugin('chart.column', 'theme_tibibase');
        $this->page->requires->js("/blocks/{$this->blockname}/js/index.js");
    }

    public function details_content()
    {
        global $USER;

        $active_kpiid = optional_param('kpi', 0, PARAM_INT);


        if( $posid = lm_position::i($USER->id)->get_id() ) {

            $kpilist = new lm_kpi_list($posid, $USER->id);
            $kpiitems = $kpilist->get_latest();
            if ( !$kpiitems ) {
                $kpiitems = $kpilist->items_by_pos();
            }

            $tpl = $this->renderer->tpl;
            if($kpiitems) {
                if (!$active_kpiid || !isset($kpiitems[$active_kpiid])) {
                    $active_kpiid = key($kpiitems);
                    $tpl->activekpi = $kpiitems[$active_kpiid];
                } else {
                    $tpl->activekpi = $kpiitems[$active_kpiid];
                }
                $tpl->activekpi->isactive = true;

                $tpl->kpiitems = $kpiitems;
            }
            $tpl->url = $this->details_url;

            lm_notification::delete($this->blockname);
            return $this->renderer->fetch('/blocks/lm_kpi/tpl/details.tpl');
        }else{
            return 'Не найдена позиция в оргструктуре';
        }
    }
}
