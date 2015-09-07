<?php
class block_lm_qiwicontact_renderer extends block_manage_renderer
{

    /**
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=lm_qiwicontact';
    public $pagename = 'Мой ИПР';
    public $type = 'lm_qiwicontact';
    public $pagelayout = "base";

    public function navigation(){
        $subparts = array();
        return $this->subnav($subparts);
    }

    public function main_content(){
        return $this->fetch('/blocks/lm_qiwicontact/tpl/index.tpl');
    }
}