<?php

defined('MOODLE_INTERNAL') || die();

class block_lm_notifications extends lm_profile_block {

    public $details_btn = FALSE;
    public $details_url = FALSE;

    public function widget_data($renderer){
        $this->page->requires->js("/blocks/{$this->blockname}/js/widget.js");

        $tpl = $renderer->tpl;
        $tpl->prefix = $this->blockname;

        global $USER;
        lm_notification::add('lm_notifications:update', $USER->id, 50);


        $notifications = lm_notification::get_list(null, null, self::user()->id);
        foreach ($notifications as $index => &$notify) {
            $notify = array(
                'type'    => $notify->get_type(),
                'url'     => $notify->get_url(),
                'message' => $notify->get_text()
            );
            if (empty($notify['message'])) unset($notifications[$index]);
        }

        $tpl->notifications = $notifications;
        return true;
    }
}
