<?php
/**
 * данный класс обрабатывает оброщение к базе таблици типов практик
 *
 * @author   Andrej Schartner <schartner@as-code.eu>
 */
class lm_bestpractices_practice_user_roles extends lm_bestpractices_model {

    protected static $table = 'lm_bestpractices_practice_user_roles';

    /**
     * метод инициализирует данный класс
     *
     * @param array $filter  список филтров для филтрации резултата
     * @param array $filter  номер актуальной страници
     */
    public function __construct($data = null) {
        $this->properties = [
            'id'      => null,
            'userid'  => null,
            'rolesid' => null,
        ];
        $this->fromObject($data);
    }

    public function getRole()
    {
        $obj = lm_bestpractices_practice_roles::get_by_id(
            $this->rolesid
        );
        return $obj;
    }

    public static function get_by_user_id($userid, $create = true) {
        global $DB, $CFG;

        $sql = "SELECT * FROM {" . self::$table . "} WHERE userid = ?";
        $res = $DB->get_record_sql($sql, [$userid]);
        if (!$res) {
            $roles = get_user_roles(context_system::instance(), $userid);
            $role = array_shift($roles);
            $obj = new self();
            $obj->userid = $userid;
            $obj->rolesid = $CFG->{'lm_bestpractices_role_' . $role->roleid};
            if ($obj->rolesid == 0) {
                return $obj;
            }
            $obj->save();
            return $obj;
        }
        return self::get_model_by_row($res);
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
