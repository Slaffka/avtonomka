<?php

class lm_bank_notification_debit extends lm_notification {

    public function get_text() {
        return FALSE; // Сначала думал, что уведомлене нужно, но, оказалось, нужно просто алерт...
        return 'На Ваш счет поступило '
                .'<b>'.$this->data->amount.'</b>'
                .' '.get_num_ending((int) $this->data->amount, array('монета', 'монеты', 'монет'));
    }

    public function get_url() {
        return '/blocks/manage/?_p=lm_bank';
    }

    // фукнция вызывается при вызове lm_notification::add();
    public function _update_data($amount) {
        if (empty($this->data)) {
            $this->data = new stdClass();
            $this->data->amount = 0.0;
        }

        $this->data->amount += $amount;

        return $this->data;
    }

}
