<?php

defined('MOODLE_INTERNAL') || die();

class block_lm_experience extends lm_profile_block {
    public $details_btn = false;
    public $details_url = '/blocks/manage/?_p=lm_experience';

    public function widget_data($renderer){
        return true;
    }
}
