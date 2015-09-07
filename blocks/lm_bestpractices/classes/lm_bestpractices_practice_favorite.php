<?php
/**
 * данный класс обрабатывает оброщение к базе таблици привязки фаваритов к практикам
 *
 * @author   Andrej Schartner <schartner@as-code.eu>
 */
class lm_bestpractices_practice_favorite extends lm_bestpractices_model {

    protected static $table = 'lm_bestpractices_practice_favorite';
    /**
     * метод инициализирует данный класс
     *
     * @param array $filter  список филтров для филтрации резултата
     * @param array $filter  номер актуальной страници
     */
    public function __construct($data = null) {
        $this->properties = [
            'id'         => null,
            'practiceid' => null,
            'userid'     => null,
            'created'    => null,
        ];
        $this->fromObject($data);
    }

    public static function get_by_user_and_practice($userid, $practiceid) {
        global $DB;
        $sql = "SELECT * FROM {" . self::$table
             . "} WHERE userid = ? AND practiceid = ?";
        $res = $DB->get_record_sql($sql, [$userid, $practiceid]);
        return self::get_model_by_row($res);
    }

    public static function get_user_list_by_practice_id($practiceid) {
        global $DB;
        $sql = "SELECT * FROM {" . self::$table
             . "} WHERE practiceid = ?";
        $res = $DB->get_records_sql($sql, [$practiceid]);
        return self::get_models_by_result($res);
    }



}
