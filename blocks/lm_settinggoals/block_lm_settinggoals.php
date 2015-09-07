<?php

defined('MOODLE_INTERNAL') || die();
/**
 * данный класс инициализирует и конфигурирует блок целеполагание
 *
 * @author   Andrej Schartner <schartner@as-code.eu>
 */
class block_lm_settinggoals extends lm_profile_block {

    /**
     * инициализация блока
     */
    public function init()
    {
        global $CFG;
        $this->details_btn = false;
        // урл к блоку
        $this->details_url = $CFG->wwwroot . '/blocks/manage/?_p=lm_settinggoals';
        $this->cron =10;
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

    /**
     * запускают кроны необходимые для работы с данным блоком
     */
    public function cron() {
        lm_settinggoals::check_deleys();
        lm_settinggoals::cleanup_db();
        // lm_settinggoals::export_data();
        lm_settinggoals::update_top_sv_for_last_moth();
        return true;
    }

}
