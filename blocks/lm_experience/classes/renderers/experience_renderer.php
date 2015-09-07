<?php
class block_lm_experience_renderer extends block_manage_renderer
{
    /**
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=lm_experience';
    public $pagename = 'Передовой опыт';
    public $type = 'lm_experience';
    public $pagelayout = "base";

    public function navigation(){
        $subparts = array();

        return $this->subnav($subparts);
    }

    public function main_content(){
        return 'Страница в разработке';
    }
}