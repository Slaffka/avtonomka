<?php
class block_lm_ipe_renderer extends block_manage_renderer
{
    /**
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=lm_ipe';
    public $pagename = 'Мой ИПР';
    public $type = 'lm_ipe';
    public $pagelayout = "base";

    public function navigation(){
        $subparts = array();

        return $this->subnav($subparts);
    }

    public function main_content(){
        return 'Страница в разработке';
    }
}