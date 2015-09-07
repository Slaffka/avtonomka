<?php
/**
 * данный класс обрабатывает оброщение к базе таблици типов практик
 *
 * @author   Andrej Schartner <schartner@as-code.eu>
 */
class lm_bestpractices_practice_types extends lm_bestpractices_model {

    protected static $table = 'lm_bestpractices_practice_types';

    /**
     * метод инициализирует данный класс
     *
     * @param array $filter  список филтров для филтрации резултата
     * @param array $filter  номер актуальной страници
     */
    public function __construct($data = null) {
        $this->properties = [
            'id'               => null,
            'name'             => null,
        ];
        $this->fromObject($data);
    }

    /**
     * метод достаёт список типов из базы
     *
     */
    public static function get_list() {
        global $DB;
        $sql = "SELECT * FROM {" . self::$table . "}";
        $res = $DB->get_records_sql($sql);
        return self::get_models_by_result($res);
    }
}
