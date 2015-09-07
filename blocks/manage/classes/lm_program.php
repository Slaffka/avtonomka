<?php
class lm_program{
    static private $i = NULL;

    /**
     * Идентификатор программы
     *
     * @var int
     */
    public $id = 0;

    /**
     * Название программы
     *
     * @var null
     */
    public $name = NULL;

    /**
     * Идентификатор курса, привязанного к программе (не обязательно)
     * Равен нулю, если программа не привязана ни к какому курсу
     *
     * @var int
     */
    public $courseid = 0;

    /**
     * Период обучения по программе. Еще в разработке, этот параметр пока никак не влияет на функционал!
     *
     * @var int
     */
    public $period = 0;

    /**
     * Идентификатор группу, к которой относится программа (необходимо для построения двухуровнего списка)
     *
     * @var int
     */
    public $parent = 0;

    protected $_update = array();

    /**
     * @param $programid
     * @return lm_program
     */
    static public function i($programid){
        $program = 0;
        if($programid && is_object($programid)){
            $program = clone $programid;
            $programid = $program->id;
        }else if($programid) {
            $program = $programid;
        }

        if(!isset(self::$i[$programid])){
            self::$i[$programid] = new lm_program($program);
        }

        return self::$i[$programid];
    }

    private function __construct($programid){
        global $DB;

        $program = null;

        if($programid && is_object($programid)){
            $program = $programid;
        }else if($programid) {
            $program = $DB->get_record('lm_program', array('id'=>$programid));
        }

        if ($program) {
            foreach ($program as $field => $value) {
                $this->$field = $value;
            }
        }

        return $this;
    }

    public static function create($object){
        global $DB;

        //TODO: Проверка прав доступа

        $id = $DB->insert_record('lm_program', $object);

        return self::i($id);
    }

    public function get_course() {
        if ($this->id) return lm_programs::get_courseid($this->id);
        else return FALSE;
    }

    /**
     * Устанавливает новое значение для поля
     *
     * @param $fieldname
     * @param $value
     * @return $this
     */
    public function set($fieldname, $value){
        if(isset($this->$fieldname)){
            $this->$fieldname = $value;
            $this->_update[] = $fieldname;
        }

        return $this;
    }

    /**
     * Обновляет данные
     * @return bool
     */
    public function update(){
        global $DB;

        // TODO: проверка прав
        if($this->_update) {
            $dataobj = new StdClass();
            $dataobj->id = $this->id;

            foreach ($this->_update as $field) {
                unset($this->_update[$field]);
                $dataobj->$field = $this->$field;
            }

            return $DB->update_record('lm_program', $dataobj);
        }

        return false;
    }

    /**
     * Возвращает идентификатор курса по идентификатору программы, если курс не привязан ни к одной из
     * программ, то вернется false
     * TODO: Ограничить возможность привязки одного курса к нескольким программам
     *
     * @param $courseid
     * @return bool|int
     */
    public static function get_id_by_courseid($courseid){
        global $DB;

        return $DB->get_field("lm_program", "id", array("courseid"=>$courseid));
    }

    /**
     * @param $userid
     * @param bool $group
     * @return int|NULL
     */
    public function get_mistakes($userid, $group = false)
    {
        $courseid = $this->get_course();
        $course = lm_course::i($courseid);
        return $course->get_mistakes($userid, $group);
    }

    /**
     * @param $userid
     * @return int|NULL
     */
    public function get_duration($userid){
        $courseid = lm_programs::get_courseid($this->id);
        $course = lm_course::i($courseid);
        return $course->get_duration($userid);
    }


    /**
     * Возвращает среднее кол-во монет, полученных за курс по региону
     *
     * @param int $programid
     * @param int $regionid
     * @return float
     */
    public static function get_coins_avg($programid, $regionid = 0)
    {
        return lm_bank::get_avg_in_gerion('program', $programid, $regionid);
    }

    /**
     * Возвращает среднее время прохождения курса по регионту
     *
     * @param int $programid
     * @param int $stageid
     * @return int
     */
    public static function get_duration_avg($programid, $stageid = 0, $regionid = 0, $partnerid = 0)
    {
        global $DB;

        $programid = (int)$programid;

        if ($programid < 1) return 0;

        $from = "{lm_partner_staff_progress} as psp";
        $where = "programid = {$programid}";

        // исключить тех кто не сдавал
        $where .= "\n\tAND duration IS NOT NULL AND duration > 0";

        if ($stageid) $where .= "\n\tAND psp.stageid = {$stageid}";

        if ($partnerid) $where .= "\n\tAND psp.partnerid = {$partnerid}";

        if ($regionid) {
            //var_dump($regionid);
            $regions = array($regionid => $regionid);
            $regions += lm_city::get_menu(array('parentid' => $regionid), '', 'id k, id v');
            $from .= "\n\tJOIN {lm_partner} as p ON p.id = psp.partnerid";
            $where .= "\n\tAND p.regionid IN (" . implode(',', $regions) . ")";
        }

        $sql = "
            SELECT AVG(duration)
            FROM {$from}
            WHERE {$where}
        ";
        //var_dump($sql);
        return (int) $DB->get_field_sql($sql);
    }

    /**
     * Возвращает среднее кол-во ошибок по курсу по региону
     *
     * @param int $programid
     * @param int $stageid
     * @return float
     */
    public static function get_mistakes_avg($programid, $stageid = 0, $regionid = 0, $partnerid = 0)
    {
        global $DB;

        $programid = (int)$programid;

        if ($programid < 1) return 0;

        $from = "{lm_partner_staff_progress} as psp";
        $where = "programid = {$programid}";

        // исключить тех кто не сдавал
        $where .= "\n\tAND mistakes IS NOT NULL";

        if ($stageid) $where .= "\n\tAND psp.stageid = {$stageid}";

        if ($partnerid) $where .= "\n\tAND psp.partnerid = {$partnerid}";

        if ($regionid) {
            $regions = array($regionid => $regionid);
            $regions += lm_city::get_menu(array('parentid' => $regionid), '', 'id k, id v');
            $from .= "\n\tJOIN {lm_partner} as p ON p.id = psp.partnerid";
            $where .= "\n\tAND p.regionid IN (" . implode(',', $regions) . ")";
        }

        $sql = "
            SELECT AVG(mistakes)
            FROM {$from}
            WHERE {$where}
        ";
        return $DB->get_field_sql($sql);
    }

    /**
     * Возвращает ссылку на курс, который привязан к программе или название программы, если нет привязки
     *
     * @return null|string
     */
    public function link(){
        global $CFG;

        if($this->courseid) {
            return html_writer::link($CFG->wwwroot . '/course/view.php?id=' . $this->courseid, $this->name);
        }

        return $this->name;
    }
}