<?php

class block_lm_report_renderer extends block_manage_renderer {

    public $pageurl = '/blocks/manage/?_p=lm_report';
    public $pagename = 'Статистика';
    public $type = 'lm_report';
    public $pagelayout = "base";

    /**
     * инициализация страници и подготовка всех параметров
     */
//    public function init_page() {
//        global $USER, $OUTPUT, $CFG, $redirect;
//
//        // запускаем инициализацию от базового класса
//        parent::init_page();
//    }

    /**
     * инициализация прав данного пользователя
     */
//    protected function initACL() {
//        global $USER;
//        $this->acl = lm_report::get_user_acl($USER->id);
//    }

    /**
     * Создание навигации
     */
    public function navigation() {
        $subparts = array();
        return $this->subnav($subparts);
    }

    public function main_content(){
        return 'Страница в разработке';
    }

}
