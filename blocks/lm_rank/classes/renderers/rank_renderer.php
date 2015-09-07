<?php
class block_lm_rank_renderer extends block_manage_renderer
{
    /**
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=lm_rank';
    public $pagename = 'Ранги';
    public $type = 'lm_rank';
    public $pagelayout = "base";

    public function navigation(){
        $subparts = array();

        return $this->subnav($subparts);
    }

    public function main_content(){
        return 'Страница в разработке';
    }
}