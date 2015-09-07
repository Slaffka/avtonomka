<?php

require_once($CFG->dirroot.'/user/lib.php');

class lm_bank extends stdClass
{
    private static $i = NULL;

    protected $channelid = 0;
    protected $instanceid = 0;
    protected $userid = 0;
    protected $amount = 0;
    public $balance = 0;
    protected $correspondent = 0;

    /**
     * @param $userid
     * @return lm_bank|NULL
     */
    public static function i($userid)
    {
        if ( !isset(self::$i[$userid]) || !$userid ) {
            self::$i[$userid] = new lm_bank($userid);
        }

        return self::$i[$userid];
    }

    public static function me()
    {
        global $USER;

        return new lm_bank($USER->id);
    }

    public function __construct($userid)
    {
        global $DB;

        if ( ( $user = lm_user::i($userid) ) && empty($user->id) ) {
            return NULL;
        }

        $sql = "SELECT * FROM {lm_bank_account} WHERE userid = {$userid} ORDER BY date DESC";
        if ( $payment = $DB->get_record_sql($sql) ) {
            foreach ($payment as $field => $value) {
                $this->$field = $value;
            }
        } else {
            $this->userid = $userid;
        }
    }


    /**
     * Взять баланс пользователя
     * @return int
     */
    public function get_balance()
    {
        $balance = number_format($this->balance, 0, ',', "'");
        return $balance;
    }

    /**
     * @param $userid
     * @return mixed
     * @throws int
     */
    public static function get_user_balance($userid)
    {
        global $DB;
        return $DB->get_field_select("lm_bank_account", "balance", "userid = {$userid} ORDER BY date DESC");
    }

    /**
     * Проверка на существование канала
     * @param $channel
     * @return int|mixed
     * @throws dml_missing_record_exception
     */
    public function channel_check($channel)
    {
        global $DB;

        if ( $channel ) {
            if ( $channelid = $DB->get_field_select("lm_bank_channel", "id", "code = '{$channel}'") ) {
                return $channelid;
            }
        }
        return 0;
    }

    /**
     * Возвращает идентификатор канала по коду
     *
     * @param $channelname
     * @return mixed
     */
    public function get_channelid_by_name($channelname){
        global $DB;

        return $DB->get_field('lm_bank_channel', 'id', array('code'=>$channelname));
    }

    /**
     * Информация для админа, если он смотрит конкретного юзера
     * @return int|StdClass
     * @throws dml_missing_record_exception
     * @throws dml_multiple_records_exception
     */
    public function info_burn()
    {
        global $DB;

        $info_burn = new StdClass();
        $year = date("Y");
        $month = date("n");
        //$month = 1;
        if ( $month == 1 ) {
            $year = $year - 1;
        }

        $sql = "SELECT SUM(amount) as balance FROM {lm_bank_account} WHERE year(date) = {$year} AND userid = {$this->userid}";
        $payment = $DB->get_record_sql($sql);
        if ( !is_null($payment->balance) && $payment->balance > 0 ) {
            $info_burn->balance = number_format($payment->balance, 0, ',', "'");
            $s_year = $year + 1;
            $s_month = "2";
            $s_day = "1";
            $days = ceil((mktime(0, 0, 0, $s_month, $s_day, $s_year) - time()) / 86400);
            $info_burn->days = $days;
        } else {
            $info_burn = 0;
        }

        return $info_burn;
    }

    /**
     * Информаця о сгорании для обычного юзера (...возле баланса красный кружок)
     * @return array
     * @throws dml_missing_record_exception
     * @throws dml_multiple_records_exception
     */
    public function burning_info()
    {
        global $DB, $CFG, $USER;

        $burn = array();
        /*$month = date("n");
        $year =  date("Y");*/
        $month = 1;
        $year =  date("Y");
        if ( $month == 1 ) {
            $old_year = $year - 1;
            $burn[$old_year]->startdate = "{$old_year}-01-01";
            $burn[$old_year]->enddate = "{$old_year}-12-31";
        }
        $burn[$year]->startdate = "{$year}-01-01";
        $burn[$year]->enddate = "{$year}-12-31";

        $array = array("бал", "бала", "балов");

        foreach ( $burn as $year => $val ) {
            $sql = "SELECT SUM(amount) as sum FROM {$CFG->prefix}lm_bank_account WHERE userid = {$USER->id} AND date BETWEEN '{$val->startdate}' AND '{$val->enddate}'";
            $payment = $DB->get_record_sql($sql);
            if ( !is_null($payment->sum) && $payment->sum > 0 ) {
                $burn[$year]->balance = number_format($payment->sum, 0, ',', "'");
                $burn[$year]->title = $this->get_word($payment->sum, $array);
                $burn[$year]->year = $year + 1;
            } else {
               unset ($burn[$year]);
            }
        }

        return $burn;
    }

    /**
     * Просклонять слово
     * @param $number - ваше число
     * @param $suffix - массив слов вида: array("монета", "монеты", "монет");
     * @return mixed
     */
    public function get_word($number, $suffix) {

        $keys = array(2, 0, 1, 1, 1, 2);
        if ( $number < 0 ) {
            $number = $number * (-1);
        }
        $mod = $number % 100;
        $suffix_key = ($mod > 7 && $mod < 20) ? 2: $keys[min($mod % 10, 5)];

        return $suffix[$suffix_key];
    }

    /**
     * @param $channelid
     * @param $instanceid
     * @param $correspondent
     * @param $userid
     * @param $comment
     * @param $amount
     * @return bool
     */
    private function transaction($amount, $channel, $instanceid=0, $correspondent=0, $comment = NULL)
    {
        global $DB, $USER;

        if ( is_string($amount) ) return false;

        $channelid = (int)$DB->get_field_select("lm_bank_channel", "id", "code = '{$channel}'");
        if ( !$channelid ){
            //TODO: Ошибка в лог администратору
            return false;
        }

        $correspondent_bank = NULL;
        if ( $correspondent ) {
            $correspondent_bank = self::i($correspondent);
            if ( !$correspondent_bank ) return false;
        }

        $result = true;

        $transaction = $DB->start_delegated_transaction();
        try {
            $dbdata = new StdClass();
            $dbdata->operatorid = (int)$USER->id;
            $dbdata->channelid  = (int)$channelid;
            $dbdata->instanceid = (int)$instanceid;
            $dbdata->date       = date("Y-m-d H:i:s");
            $dbdata->comment    = $comment;

            if ( $correspondent_bank ) {
                $dbdata->userid         = $correspondent_bank->userid;
                $dbdata->correspondent  = (int)$this->userid;
                $dbdata->amount         = $amount;
                $dbdata->balance        = $correspondent_bank->get_balance() + $amount;
                if ( $result && $DB->insert_record("lm_bank_account", $dbdata) ) {
                    $dbdata->userid         = $this->userid;
                    $dbdata->correspondent  = (int)$correspondent;
                    $dbdata->amount         = 'dasd';
                    $dbdata->balance        = $this->balance - $amount;
                    $result = $result && $DB->insert_record("lm_bank_account", $dbdata);
                }
            } else {
                $dbdata->userid         = $this->userid;
                $dbdata->correspondent  = 0;
                $dbdata->amount         = $amount;
                $dbdata->balance        = $this->balance + $amount;
                $result = $result && $DB->insert_record("lm_bank_account", $dbdata);
            }

            $transaction->allow_commit();
        } catch (dml_write_exception $e) {
            try {
                $transaction->rollback($e);
            } catch (dml_write_exception $e) {
                $e->getMessage();
                $result = false;
            }
        }

        // отправить пользователю, получившему монеты, уведомление
        if ($result) {
            lm_notification::add('lm_bank:debit', false, $amount < 0 ? $correspondent : $this->userid, $amount);
        }

        return $result;
    }

    /**
     * Перечисление между юзерами
     * @param $userid
     * @param $amount
     * @param null $comment
     * @return bool
     */
    public function send($userid, $amount, $comment = NULL)
    {
        $amount = abs($amount);
        if ( $this->balance > $amount ) {
            return $this->transaction($amount, 'transfer', 0, $userid, $comment);
        }
        return false;
    }

    // Начислить монет
    public function debit($amount, $channel, $instanceid=0, $comment = NULL)
    {
        return $this->transaction($amount, $channel, $instanceid, 0, $comment);
    }

    // Списать монет
    public function credit($amount, $channel, $instanceid=0, $comment = NULL)
    {
        $amount = abs($amount);
        if ( $this->balance > $amount ) {
            return $this->transaction(-$amount, $channel, $instanceid, 0, $comment);
        }
        return false;
    }


    /**
     * Вернет сумму, заработанную пользователем по указанному каналу[ и instance]
     * @param string $channel_name
     * @param int $instanceid
     * @return float
     */
    public function get_sum($channel_name, $instanceid = 0) {
        global $DB;

        $instanceid = (int) $instanceid;

        $sum = 0.0;
        $channel = lm_bank_channel::i($channel_name);
        if ($channel->id) {
            $conditions = array(
                'channelid' => $channel->id,
                'userid'    => $this->userid
            );
            if ($instanceid) $conditions['instanceid'] = $instanceid;

            $sum = (float) $DB->get_field('lm_bank_account', 'SUM(amount)', $conditions);
        } else {
            // TODO: Ошибка в лог администратору
            trigger_error('There is no channel "'.$channel_name.'"', E_USER_WARNING);
        }

        return $sum;
    }

    /**
     * Вернет среднюю сумму, зарабатываемую по указанному каналу[ и instance] в регионе
     * @param string $channel_name
     * @param int $instanceid
     * @param int $regionid
     * @return float
     */
    public static function get_avg_in_gerion($channel_name, $instanceid = 0, $regionid = 0) {
        global $DB;

        $instanceid = (int) $instanceid;

        $sum = 0.0;
        $channel = lm_bank_channel::i($channel_name);
        if ($channel->id) {

            $from = "{lm_bank_account} as ba";

            $where = "ba.channelid = {$channel->id}";

            if ($instanceid) $where .= "\n\tAND ba.instanceid = {$instanceid}";

            if ($regionid) {
                $regions = array($regionid => $regionid);
                $regions += lm_city::get_menu(array('parentid' => $regionid), '', 'id k, id v');

                $from .= "\n\tJOIN {lm_partner_staff} as ps ON ps.userid = ba.userid";
                $from .= "\n\tJOIN {lm_partner} as p ON p.id = ps.partnerid";

                $where .= "\n\tAND p.regionid IN (" . implode(',', $regions) . ")";
            }

            $sql = "
                SELECT AVG(ba.amount)
                FROM {$from}
                WHERE {$where}
            ";
            $sum = (float) $DB->get_field_sql($sql);
        } else {
            trigger_error('There is no channel "'.$channel_name.'"', E_USER_WARNING);
        }
        return $sum;
    }

}