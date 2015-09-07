<?php
/**
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!    ДЕМО УВЕДОМЛЕНИЯ    !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 *
 * User: FullZero
 * Date: 6/11/2015
 * Time: 10:28 AM
 *
 *
 * Образецы уведомления
 */


global $USER;

// создадим уведомление о поступившем письме
lm_notification::add('lm_notifications:message', FALSE, $USER->id, $USER->firstname);

// в реальном скрипте будет написано, например, так:
// lm_notification::add('lm_messages:incoming_message', $receiver->id, $sender->id);
// там где юзер прочетет письмо(-а) нужно вызвать ->remove() или ::delete(), чтобы удалить уведомления


class lm_notifications_notification_update extends lm_notification {

    public $alert = TRUE;

    public function __construct() {
        call_user_func_array('parent::__construct', func_get_args());

        if ($this->get_id()) {
            if (--$this->data) $this->update();
            else {
                $this->remove();
                parent::delete('lm_notifications:message');
            }
        }
    }

    public function get_text() {
        if ( ! $this->data) {
            return "[TEST] Это сообщение показывается последний раз";
        } else {
            return "[TEST] Это сообщение будет показано еще {$this->data} раз";
        }
    }

    public function get_url() {
        return '/blocks/manage/?_p=profile';
    }

    public function get_type() {
        return self::TYPE_SUCCESS;
    }

}

class lm_notifications_notification_message extends lm_notification {

    public function get_text() {
        if ( $this->data->count > 10) {
            return "[TEST] Внимание!!! У Вас {$this->data->count} непрочитанных сообщений";
        } else {
            return "[TEST] У Вас {$this->data->count} входящих сообщений.\r\nПоследнее от {$this->data->from}";
        }
    }

    public function get_url() {
        return '/blocks/manage/?_p=profile';
    }

    public function get_type() {
        return $this->data->count > 10 ? self::TYPE_DANGER : self::TYPE_SUCCESS;
    }

    // фукнция вызывается при вызове lm_notification::add();
    public function _update_data($from) {
        if (empty($this->data)) $this->data = new stdClass();

        $this->data->from = $from;
        $this->data->count++;

        return $this->data;
    }

}
