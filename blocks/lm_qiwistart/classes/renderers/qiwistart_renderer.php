<?php
class block_lm_qiwistart_renderer extends block_manage_renderer
{

    /**
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=lm_qiwistart';
    public $pagename = 'Главная';
    public $type = 'lm_qiwistart';
    public $pagelayout = "base";

    public function navigation(){
        $subparts = array();
        return $this->subnav($subparts);
    }

    public function main_content(){
        return $this->fetch('/blocks/lm_qiwistart/tpl/index.tpl');
    }
}