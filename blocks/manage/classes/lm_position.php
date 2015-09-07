<?php

class lm_position
{
    private static $i = NULL;

    private $myteam = NULL;

    /**
     * Идентификатор позиции
     * @var int
     */
    public $id = 0;

    /**
     * Внешний ключ позиции (необходимо для импорта)
     * @var int
     */
    public $code = 0;

    /**
     * Внешний ключ определяющий родительскую позицию (необходимо для импорта)
     * @var int
     */
    public $parentcode = 0;


    public $postcode = 0;
    /**
     * Идентификатор родительской позиции
     * @var int
     */
    public $parentid = 0;

    /**
     * Идентификатор записи о том когда пользователь занимал эту позицию
     * @var int
     */
    public $posxrefid = 0;

    /**
     * Идентификатор пользователя, который занимает данную позицию в текущий момент времени
     * @var int
     */
    public $userid = 0;

    /**
     * Идентификатор города, определяющий гео положение позиции
     * @var int
     */
    public $cityid = 0;

    /**
     * Идентификатор должности позиции
     * @var int
     */
    public $postid = 0;

    /**
     * Идентификатор территории позиции
     * @var int
     */
    public $areaid = 0;

    /**
     * Идентификатор канала сбыта
     * @var int
     */
    public $distribid = 0;

    /**
     * Идентификатор сегмента
     * @var int
     */
    public $segmentid = 0;

    /**
     * @param $userid
     * @return lm_position
     */
    static public function i($userid=0){
        global $USER;

        if(!$userid) $userid = $USER->id;

        if( !isset(self::$i[$userid]) ){
            self::$i[$userid] = new lm_position($userid);
        }

        return self::$i[$userid];
    }

    public function __construct($userid=0){
        global $DB, $USER;

        if($userid === 0) $userid = $USER->id;

        $position = null;
        if($userid) {
            $sql = "SELECT lp.*,  lpx.id as posxrefid, lpx.userid
                          FROM {lm_position} lp
                          JOIN {lm_position_xref} lpx ON lp.id=lpx.posid
                          WHERE lpx.userid={$userid} AND lpx.archive=0
                          ";
            $position = $DB->get_record_sql($sql);
        }

        if ($position) {
            foreach ($position as $field => $value) {
                $this->$field = $value;
            }
        }
    }


    public function create(){
        global $DB;

        $this->id = (int) $DB->insert_record('lm_position', $this);
        return $this;
    }

    public function update(){
        global $DB;

        if ($this->id > 0) return $DB->update_record('lm_position', $this);
        return FALSE;
    }

    /**
     * Возвращает идентификатор позиции в оргструктуре
     *
     * @return int
     */
    public function get_id(){
        return $this->id;
    }

    /**
     * Возвращает идентификатор позиции в оргструктуре
     *
     * @return int
     */
    public static function get_code($id) {
        global $DB;
        return $DB->get_field('lm_position', 'code', array('id' => (int) $id));
    }

    /**
     * Возвращает команду для текущей позиции, представляющую из себя массив пользователей.
     * Сотрудник в текущей позиции будет включен в этот массив.
     *
     * Для сотрудников без подчиненных (тп, мч, отп), команда это коллеги.
     * Для сотрудников с подчиненными (св, тм), команда это их подчиненные.
     * @return array
     */
    public function get_my_team(){
        global $DB;

        $myteam = array();
        $parentid = $this->id;
        if( !$this->has_staffers() ) {
            $parentid = $this->get_my_chief_posid();
        }

        if( $parentid ){
            $sql = "SELECT u.*
                      FROM {user} u
                      JOIN {lm_position_xref} lpx ON lpx.userid=u.id AND lpx.archive=0
                      JOIN {lm_position} lp ON lp.id=lpx.posid
                      WHERE lp.parentid={$parentid}";

            $myteam = $DB->get_records_sql($sql);
        }



        return $myteam;
    }

    /**
     * Есть ли у этой позиции кто-то в подчинении?
     *
     * @return bool
     */
    public function has_staffers(){
        global $DB;

        return $DB->record_exists('lm_position', array('parentid'=>$this->id));
    }

    /**
     * Возвращает идентификатор позиции начальника для текущей позиции
     *
     * @return mixed
     */
    public function get_my_chief_posid(){

        return $this->parentid;
    }

    /**
     * Возвращает пользователя, который является начальником для текущей позиции
     *
     * @return mixed|null
     * @throws dml_missing_record_exception
     * @throws dml_multiple_records_exception
     */
    public function get_my_chief(){
        global $DB;

        $chief = NULL;

        if( $this->parentid ) {
            $sql = "SELECT u.*
                          FROM {user} u
                          JOIN {lm_position_xref} lpx ON lpx.userid=u.id AND lpx.archive=0
                          WHERE lpx.posid={$this->parentid}
                          LIMIT 1";

            $chief = $DB->get_record_sql($sql);
        }

        return $chief;
    }

    /**
     * Проверяет является ли пользователь $userid начальником для сотрудника занимающего эту позицию
     *
     * @param $userid
     * @return bool
     */
    public function is_my_chief($userid){
        $ismy = false;
        if( $chief = $this->get_my_chief() ){
            if( $chief->id == $userid ) $ismy = true;
        }

        return $ismy;
    }

    /**
     * Возвращает пользователя, который является функциональным руководителем для текущей позиции
     *
     * @return mixed|null
     * @throws dml_missing_record_exception
     * @throws dml_multiple_records_exception
     */
    public function get_my_fchief(){
        global $DB;

        $fchief = NULL;

        if( $this->parentfid ) {
            $sql = "SELECT u.*
                          FROM {user} u
                          JOIN {lm_position_xref} lpx ON lpx.userid=u.id AND lpx.archive=0
                          WHERE lpx.posid={$this->parentfid}
                          LIMIT 1";

            $fchief = $DB->get_record_sql($sql);
        }

        return $fchief;
    }

    /**
     * Возвращает пользователя, который является тренером для текущей позиции
     *
     * @return mixed|null
     * @throws dml_missing_record_exception
     * @throws dml_multiple_records_exception
     */
    public function get_my_trainer(){
        global $DB;

        $trainer = NULL;

        if( $this->areaid ) {
            $sql = "SELECT u.*
                          FROM {lm_place} lpl
                          /*JOIN {lm_position_xref} lpos ON lpos.posid = lpl.trainerid*/
                          JOIN {user} u ON u.id = lpl.trainerid/*lpos.userid*/
                          WHERE lpl.id = {$this->areaid}
                          LIMIT 1";

            $trainer = $DB->get_record_sql($sql);
        }

        return $trainer;
    }

    public function get_my_city(){
        global $DB;

        $city = NULL;
        if( $this->cityid ) {
            $city = $DB->get_record('lm_region', array('id' => $this->cityid));
        }

        return $city;
    }


    /**
     * Возвращает идентификатор позиции по внешнему ключу
     *
     * @param $code
     * @return mixed
     */
    public static function  posid_by_code($code)
    {
        global $DB;
        return $DB->get_field('lm_position', 'id', array('code'=>$code));
    }

    /**
     * Возвращает позицию по внешнему ключу (включая информацию о назначенном сотруднике в текущий момент)
     * @param $code
     * @return self|FALSE
     * @throws dml_missing_record_exception
     * @throws dml_multiple_records_exception
     */
    public static function by_code($code){
        global $DB;

        $sql = "
            SELECT lp.*, lpx.id as posxrefid, lpx.userid
            FROM {lm_position} lp
            JOIN {lm_position_xref} lpx ON lp.id=lpx.posid
            WHERE lp.code=\"{$code}\" AND lpx.archive=0
        ";

        $raw_position = $DB->get_record_sql($sql);
        if ($raw_position) {
            $position = new self(FALSE);
            foreach ($raw_position as $field => $value) {
                $position->$field = $value;
            }
            return $position;
        } else {
            return FALSE;
        }
    }

    /**
     * Возвращает позиции, которые занимает сотрудник
     *
     * @param $staffercode - Внешний ключ
     * @param int $posid - Идентификатор позиции
     * @return array
     */
    public static function get_staffer_xrefs($staffercode, $posid=0){
        global $DB;

        $conditions = array('staffercode'=>$staffercode, 'archive'=>0);
        if( $posid ){
            $conditions['posid'] = $posid;
        }

        return $DB->get_records('lm_position_xref', $conditions, 'dateassignment DESC');
    }

    /**
     * Добавляет позицию сотруднику. Перед назначением необходимо убедиться, что предыдущие назначения на эту позиции
     * помечены архивными!
     *
     * @param $staffercode - Внешний ключ
     * @param $posid - Идентификатор позиции
     * @param $dateassignment - Дата назначения на позицию
     * @return bool|int
     */
    public static function insert_staffer_xref($staffercode, $posid, $dateassignment){
        global $DB;

        $pos_xref = new StdClass();
        $pos_xref->staffercode = $staffercode;
        $pos_xref->posid = $posid;
        $pos_xref->userid = 0;
        $pos_xref->dateassignment = $dateassignment;

        return $DB->insert_record('lm_position_xref', $pos_xref);
    }

    /**
     * Обновляет дату назначения сотрудника на позицию
     *
     * @param $xrefid
     * @param $dateassignment
     * @return bool
     */
    public static function update_staffer_xref_dateassignment($xrefid, $dateassignment){
        global $DB;

        $pos_xref = new StdClass();
        $pos_xref->id = $xrefid;
        $pos_xref->dateassignment = $dateassignment;

        return $DB->update_record('lm_position_xref', $pos_xref);
    }

    /**
     * Помещает назначение в архив
     *
     * @param $xrefid
     * @param $archive
     * @return bool
     */
    public static function update_staffer_xref_archive($xrefid, $archive){
        global $DB;

        $pos_xref = new StdClass();
        $pos_xref->id = $xrefid;
        $pos_xref->archive = $archive;

        return $DB->update_record('lm_position_xref', $pos_xref);
    }

    /**
     * Возвращает список должностей сотрудника в виде массива [posid]=postid
     *
     * @param $userid
     * @return array
     */
    public static function get_user_posts($userid){
        global $DB;

        $sql = "SELECT lp.id, lp.postid
                      FROM {lm_position_xref} lpx
                      JOIN {lm_position} lp ON lp.id=lpx.posid
                      WHERE lpx.userid=? AND lpx.archive=?";

        return $DB->get_records_sql_menu($sql, array('userid'=>$userid, 'archive'=>0));
    }

    /**
     * Корректирует оргструктуру после выгрузки
     */
    public static function make_correct(){
        global $DB;
        if( $positions = $DB->get_records('lm_position', array('parentid'=>0)) ){
            $parents = $DB->get_records_menu('lm_position', array(), '', 'DISTINCT(parentcode), id');
            foreach($positions as $position){
                if( isset($parents[$position->parentcode]) ){
                    $position->parentid = $DB->get_field('lm_position', 'id', array('code'=>$position->parentcode) );
                    $DB->update_record('lm_position', $position);
                }
            }
        }
    }


    /**
     * Корректирует назначенные роли для все пользователей, в соответствии с занимаемыми позициями
     *
     * @throws Exception
     * @throws dml_exception
     */
    public static function correct_assigned_roles(){
        global $DB;

        if( $users = $DB->get_records('user', array(), '', 'id') ) {
            $context = context_system::instance();
            foreach($users as $user) {
               self::correct_assigned_roles_by_user($user->id, $context->id);
            }
        }
    }

    /**
     * Корректирует назначенные сотруднику роли в соответствии с занимаемым позициям
     *
     * @param $userid
     * @param null $contextid
     * @throws Exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function correct_assigned_roles_by_user($userid, $contextid=null){
        if( !$contextid ) {
            $context = context_system::instance();
            $contextid = $context->id;
            unset($context);
        }

        $roles = lm_post::post_menu('id', 'roleid');

        if( $posts = self::get_user_posts($userid) ) {
            foreach($posts as $postid) {
                if( !empty($roles[$postid]) ) {
                    role_assign($roles[$postid], $userid, $contextid);
                }
            }
        }
    }


    /**
     * Вернет текущий position_xref.id для пользователя
     * @param $userid
     * @return mixed
     */
    public static function get_user_posixrefid($userid) {
        global $DB;

        $userid = (int) $userid;

        if ($userid <= 0) return FALSE;

        return (int) $DB->get_field('lm_position_xref', 'id', array('userid' => $userid));
    }
}