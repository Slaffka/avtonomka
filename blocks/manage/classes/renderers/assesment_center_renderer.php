<?php

class block_manage_assesment_center_renderer extends block_manage_renderer
{
    /**
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=assesment_center';
    public $pagename = 'Ассесмент центр';
    public $type = 'manage_assesment_center';

    public function init_page()
    {
        parent::init_page();
        $this->page->requires->js('/blocks/manage/yui/base.js');
    }

    public function main_content()
    {
        return $this->fetch('assesment_center/index.tpl');
    }
}