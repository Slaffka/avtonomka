<?php
class block_lm_qiwiipe_renderer extends block_manage_renderer
{

    /**
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=lm_qiwiipe';
    public $pagename = 'Мой ИПР';
    public $type = 'lm_qiwiipe';
    public $pagelayout = "base";

    public function navigation(){
        $subparts = array();
        return $this->subnav($subparts);
    }

    public function main_content(){
        return $this->fetch('/blocks/lm_qiwiipe/tpl/index.tpl');
    }
}