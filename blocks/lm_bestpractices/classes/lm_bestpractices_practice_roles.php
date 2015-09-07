<?php
/**
 * данный класс обрабатывает оброщение к базе таблици типов практик
 *
 * @author   Andrej Schartner <schartner@as-code.eu>
 */
class lm_bestpractices_practice_roles extends lm_bestpractices_model {

    protected static $table = 'lm_bestpractices_practice_roles';

    /**
     * метод инициализирует данный класс
     *
     * @param array $filter  список филтров для филтрации резултата
     * @param array $filter  номер актуальной страници
     */
    public function __construct($data = null) {
        $this->properties = [
            'id'     => null,
            'name'   => null,
            'access' => null,
        ];
        $this->fromObject($data);
    }

    public static function get_role_list()
    {
        $res = [ 0 => 'Доступ закрыт'];
        $r = self::get_list();
        foreach ($r as $role) {
            $res[$role->id] = $role->name;
        }
        return $res;
    }

    /**
     * метод достаёт список ролей из базы
     */
    public static function get_list() {
        global $DB;
        $sql = "SELECT * FROM {" . self::$table . "}";
        $res = $DB->get_records_sql($sql);
        return self::get_models_by_result($res);
    }
}
