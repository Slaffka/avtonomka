<?php
/**
 * данный класс обрабатывает оброщение к базе таблици привязки типов практик к практикам
 *
 * @author   Andrej Schartner <schartner@as-code.eu>
 */
class lm_bestpractices_practice_trade_outlet extends lm_bestpractices_model {

    protected static $table = 'lm_bestpractices_practice_trade_outlet';
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
            'outletid'   => null,
        ];
        $this->fromObject($data);
    }

    public static function get_type_list_by_practice_id($practiceid) {
        global $DB;
        $sql = "SELECT * FROM {" . self::$table
             . "} WHERE practiceid = ?";
        $res = $DB->get_records_sql($sql, [$practiceid]);
        return self::get_models_by_result($res);
    }
}
