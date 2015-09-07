<?php
/**
 * данный класс обрабатывает оброщение к базе таблици привязки типов практик к практикам
 *
 * @author   Andrej Schartner <schartner@as-code.eu>
 */
class lm_bestpractices_practice_history extends lm_bestpractices_model {

    protected static $table = 'lm_bestpractices_practice_history';

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
            'date'       => null,
            'state'      => null,
            'comment'    => null,
            'data'       => null,
        ];
        $this->fromObject($data);
    }

    public function getDateStr () {
        return date("d.m.Y", $this->date);
    }

    public function getStateStr () {
        switch ($this->state) {
            case lm_bestpractices_practice::STATE_ACCEPTED:
                return "Принята";
                break;
            case lm_bestpractices_practice::STATE_REJECTED:
                return "Отклонена";
                break;
            case lm_bestpractices_practice::STATE_NEW:
            default:
                return "Новая";
                break;
        }
    }

    public function getPractice() {
        $practice = lm_bestpractices_practice::get_model_by_row(
            (object)unserialize($this->data)
        );
        return $practice;
    }

    public static function get_list_by_practice_id($practiceid) {
        global $DB;
        $sql = "SELECT * FROM {" . self::$table
             . "} WHERE practiceid = ? ORDER BY id DESC";
        $res = $DB->get_records_sql($sql, [$practiceid]);
        return self::get_models_by_result($res);
    }
}
