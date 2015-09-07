<?php

/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 6/23/2015
 * Time: 2:06 PM
 */
class block_lm_notifications_renderer  extends block_manage_renderer {
    public $type = "lm_notifications";

    public function ajax_get_list($p) {
        global $USER;

        /* just for test*/
        if (isset($p->test)) {
            $res = array();
            $types = array('success', 'info', 'warning', 'danger');
            for($i = 0; $i < rand(3,15); $i++) {
                $res[] = (object) array(
                    'type' => $types[array_rand($types)],
                    'url' => '#href',
                    //'message' => 'Новое сообщение '.$i
                    'message' => 'Андрей Крючков прошел курс "Я и мой продукт"'
                );
            }
            return $res;
        }

        /* real program */
        $userid = (int) $p->userid;
        if ( ! $userid) $userid = $USER->id;
        $notifications = lm_notification::get_list(null, null, $userid);
        foreach ($notifications as $index => &$notify) {
            $notify = array(
                'id'      => $notify->get_id(),
                'type'    => $notify->get_type(),
                'event'   => $notify->event,
                'url'     => $notify->get_url(),
                'message' => $notify->get_text(),
                'data'    => $notify->get_data()
            );
            if (empty($notify['message'])) unset($notifications[$index]);
        }

        return $notifications;
    }

}