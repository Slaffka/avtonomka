<?php

defined('MOODLE_INTERNAL') || die();
/**
 * данный класс инициализирует и конфигурирует блок передовой опыт
 *
 * @author   Andrej Schartner <schartner@as-code.eu>
 */
class block_lm_bestpractices extends lm_profile_block {

    /**
     * инициализация блока
     */
    public function init()
    {
        global $CFG;
        $this->details_btn = false;
        // урл к блоку
        $this->details_url = $CFG->wwwroot . '/blocks/manage/?_p=lm_bestpractices';

        parent::init();
    }

    /**
     * инициализация данных блока по умолчанию
     */
    public function widget_data($renderer){

        $tpl = $renderer->tpl;
        $tpl->nodata = 'Нет данных';
        return true;
    }

    /**
     * включает конфигурацию для данного блока
     */
    public function has_config()
    {
        return true;
    }

}