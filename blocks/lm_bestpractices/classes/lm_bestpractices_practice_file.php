<?php
/**
 * данный класс обрабатывает оброщение к базе таблици привязки типов практик к практикам
 *
 * @author   Andrej Schartner <schartner@as-code.eu>
 */
class lm_bestpractices_practice_file extends lm_bestpractices_model {

    protected static $table = 'lm_bestpractices_practice_file';

    protected static $type_list = [
        'pdf'   => 0,
        'excel' => 1,
        'photo' => 2,
        'other' => 3,
    ];


    /**
     * метод инициализирует данный класс
     *
     * @param array $filter  список филтров для филтрации резултата
     * @param array $filter  номер актуальной страници
     */
    public function __construct($data = null) {
        $this->properties = [
            'id'          => null,
            'practiceid'  => null,
            'type'        => null,
            'path'        => null,
            'contenttype' => null,
            'filename'    => null,
        ];
        $this->fromObject($data);
    }

    public static function get_type_id($type) {
        if (isset(self::$type_list[$type])) {
            return self::$type_list[$type];
        }
        return null;
    }

    public function setType($value) {
        if (in_array($value, array_values(self::$type_list))) {
            $this->properties['type'] = $value;
            return true;
        }
        if (!isset(self::$type_list[$value])) {
            return false;
        }
        $this->properties['type'] = self::$type_list[$value];
    }

    public static function get_list_by_practiceid($id) {
        global $DB;
        $sql = "SELECT * FROM {" . self::$table
             . "} WHERE practiceid = ?";
        $res = $DB->get_records_sql($sql, [$id]);
        return self::get_models_by_result($res);
    }

}
