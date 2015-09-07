<?php

require_once($CFG->dirroot.'/user/lib.php');

class lm_bank_channel extends stdClass
{
    private static $i = NULL;

    public $id      = 0;
    public $code    = "";
    public $blockid = 0;

    public static function i($code = "")
    {
        if ( !isset(self::$i[$code]) || !$code ) {
            self::$i[$code] = new lm_bank_channel($code);
        }

        return self::$i[$code];
    }

    public function __construct($code = "")
    {
        global $DB;

        if ( $code ) {
            if ($payment = $DB->get_record_select("lm_bank_channel", "code = '{$code}'")) {
                foreach ($payment as $field => $value) {
                    $this->$field = $value;
                }
            } else {
                $this->code = "";
            }
        }
    }

    /**
     * Получить blockname по id канала
     * @return mixed|string
     * @throws dml_missing_record_exception
     */
    public function get_blockname()
    {
        global $DB;

        $blockname = "";
        if ( $this->blockid ) {
            $blockname = $DB->get_field_select("block", "name", "id = {$this->blockid}");
        }

        return $blockname;
    }

    /**
     * Получить blockname по коду
     * @param $code
     * @return mixed|string
     * @throws dml_missing_record_exception
     */
    public static function get_blockname_by_code($code)
    {
        global $DB;

        $blockname = "";
        if ( $blockid = $DB->get_field_select("lm_bank_channel", "blockid", "code = '{$code}'") ) {
            $blockname = $DB->get_field_select("block", "name", "id = {$blockid}");
        }

        return $blockname;
    }

    /**
     * Добавить канал
     * @param $blockid
     * @return int
     */
    public function add($code, $blockid)
    {
        global $DB;

        $dbdata = new StdClass();
        $dbdata->code = $code;
        $dbdata->blockid = $blockid;

        if ( $DB->insert_record("lm_bank_channel", $dbdata) ) {
            return true;
        }
        return false;
    }

    /**
     * Получить список каналов
     * @return array
     */
    public static function get_list()
    {
        global $DB;

        return $DB->get_records("lm_bank_channel");
    }

    /**
     * Получить инстансы по каналу
     * @param $userid
     * @return bool
     */
    public function get_instances($userid)
    {
        $blockname = self::get_blockname_by_code($this->code);
        $class_name = "{$blockname}_channel_{$this->code}";
        if ( class_exists($class_name) ) {
            $class = new $class_name;
            return $class->get_instances($userid);
        }
        return false;
    }
}