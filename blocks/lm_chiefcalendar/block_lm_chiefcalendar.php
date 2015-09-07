<?php

defined('MOODLE_INTERNAL') || die();

class block_lm_chiefcalendar extends lm_profile_block {

    public function widget_data($renderer){
        global $OUTPUT;

        return true;
    }

    public function details_content()
    {
        return 'Страница в разработке';
    }
}
