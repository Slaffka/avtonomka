<?php

class lm_activity{
    static private $i = NULL;
    public $courseid = 0;
    public $name = '';

    public $id = 0;
    public $activityid = 0;
    public $trainerid = 0;
    public $programid = 0;
    public $placeid = 0;
    public $maxmembers = 0;
    public $type = NULL;
    public $auditory = NULL;
    public $comment = NULL;
    public $comment2 = NULL;
    public $properties = NULL;
    public $startdate = 0;
    public $enddate = 0;

    /**
     * Кол-во часов тренинга. Значение кэшируется в таблице lm_activity и используется для сортировки.
     *
     * @var float
     */
    public $hourscount = 0;

    /**
     * Кол-во заявок, поданных на тренинг. Значение кэшируется в таблице lm_activity и используется для сортировки.
     *
     * @var int
     */
    public $requestcount = 0;

    /**
     * Кол-во обученных участников. Значение кэшируется в таблице lm_activity и используется для сортировки.
     * @var int
     */
    public $trainedcount = 0;

    /**
     * @param $activityid
     * @return lm_activity
     */
    static public function i($activityid){
        $activity = 0;
        if($activityid && is_numeric($activityid)) {
            $activity = $activityid;
        }else if($activityid && is_object($activityid)){
            $activity = clone $activityid;
            $activityid = $activity->id;
        }

        if(!isset(self::$i[$activityid])){
            self::$i[$activityid] = new lm_activity($activity);
        }

        return self::$i[$activityid];
    }

    private function __construct($activityid){
        global $DB;

        if($activityid && is_numeric($activityid)) {

            $sql = "SELECT la.*, lp.courseid, lp.name
                      FROM {lm_activity} la
                      LEFT JOIN {lm_program} lp ON la.programid=lp.id
                      WHERE la.id=?";
            $params = array($activityid);

            $activity = $DB->get_record_sql($sql, $params);

        }else if($activityid && is_object($activityid)){
            $activity = $activityid;
        }else{
            die('Ошибка в классе lm_activity!');
        }

        if ($activity) {
            foreach ($activity as $field => $value) {
                if($field == 'properties'){
                    $value = unserialize($value);
                }

                $this->$field = $value;
            }
        }

        $this->activityid = $this->id;

        return $this;
    }

    public function needActivityId(){
        if(!$this->activityid){
            die("Не указан идентификатор активности!");
        }
    }

    /**
     * Возвращает название активности (курса)
     *
     * @return mixed
     */
    public function fullname(){
        return $this->name;
    }

    /**
     * Удаляет текуущую активность без возможности восстановления!
     *
     * @return bool
     */
    public function remove(){
        global $DB;

        if(!$this->has_capability_edit()){
            return false;
        }

        $members = $DB->get_records('lm_activity_request', array('activityid'=>$this->id));

        if($DB->delete_records('lm_activity_request', array('activityid'=>$this->id)) ){
            return $DB->delete_records('lm_activity', array('id'=>$this->id));
        }

        if($members){
            // Пересчитываем кэшируемые данные
            $prevpatnerid = false;
            foreach($members as $member){
                if($prevpatnerid != $member->partnerid) {
                    // Пересчитываем процент обученных сотрудников у партнера по программе и в общем
                    $partner = lm_partner::i($member->partnerid);
                    $partner->recalculate_trained_percent($this->programid);
                    $partner->recalculate_trained_percent();
                }

                $prevpatnerid = $member->partnerid;
            }
        }

        return false;
    }

    /**
     * Возвращает ФИО тренера
     *
     * @return string
     */
    public function trainer_fullname(){
        global $DB;
        $user = $DB->get_record('user', array('id'=>$this->trainerid), 'id, firstname, lastname' );
        return fullname($user);
    }

    /**
     * Отмечает был ли пользователь на тренинге или нет.
     *
     * @param $userid
     * @param bool $passed
     * @return bool
     */
    public function set_mark_member($userid, $passed=true){
        global $DB;

        $this->needActivityId();

        $result = false;
        $request = $DB->get_record('lm_activity_request', array('activityid'=>$this->activityid, 'userid'=>$userid));
        if($request){
            if($passed){
                $request->passed = time();
            }else{
                $request->passed = -1;
            }
            $result = $DB->update_record('lm_activity_request', $request);

            // Пересчитываем процент обученных сотрудников у партнера по программе и в общем
            $partner = lm_partner::i($request->partnerid);
            $partner->recalculate_trained_percent($this->programid);
            $partner->recalculate_trained_percent();

            // Пересчитываем процент обученности сотрудника
            $partner->recalculate_staffer_progress($userid, $this->programid);

            // Пересчитываем процент обученных по активности
            $this->recalculate_trained_count();
        }


        return $result;
    }

    /**
     * Добавляет участника на тренинг (активность)
     *
     * @param $userid
     * @return bool|int
     */
    public function add_member($userid, $partnerid, $isrecalculate=true){
        global $DB, $CFG, $USER;

        $this->needActivityId();

        if(!$this->programid){
            die("Не указана программа!");
        }

        $dataobj = new StdClass();
        $dataobj->activityid = $this->activityid;
        $dataobj->userid = $userid;
        $dataobj->partnerid = $partnerid;
        $dataobj->requestedby = $USER->id;
        $dataobj->passed = 0;

        $result = $DB->insert_record('lm_activity_request', $dataobj);

        // Если программа с привязкой к курсу - написываем участника на курс в качестве студента
        if($this->courseid){
            lm_course::i($this->courseid)->enrol($userid, $CFG->block_manage_studentroleid);
        }

        if($isrecalculate) {
            // Пересчитываем кол-во участников
            $this->recalculate_members_count();

            // Пересчитываем процент обученных по активности
            $this->recalculate_trained_count();
        }

        return $result;
    }


    /**
     * Возвращает список участников тренинга (активности)
     *
     * @return array
     */
    public function get_members(){
        global $DB;

        $this->needActivityId();

        $sql = "SELECT lar.id as reqid, u.id, u.email, u.username, u.picture, u.imagealt, u.firstname, u.lastname, lar.passed, lar.partnerid
                      FROM {user} u
                      JOIN {lm_activity_request} lar ON lar.userid=u.id
                      WHERE lar.activityid = ?
                      ORDER BY u.lastname ASC, u.firstname ASC";

        return $DB->get_records_sql($sql, array($this->activityid));
    }

    /**
     * Подсчитывает кол-во участников тренинга и кэширует это значение в таблице lm_activity
     * для дальнейшего использования в сортировках
     *
     * @return boolean
     */
    public function recalculate_members_count(){
        global $DB;

        $this->needActivityId();

        $count = $DB->count_records('lm_activity_request', array('activityid'=>$this->id));

        if($count != $this->requestcount){
            $this->requestcount = $count;
            $dataobj = new StdClass();
            $dataobj->id = $this->id;
            $dataobj->requestcount = $this->requestcount;

            return $DB->update_record('lm_activity', $dataobj);
        }

        return false;
    }

    /**
     * Возвращает кол-во участников тренинга (активности)
     *
     * @return int
     */
    public function count_members(){
        return $this->requestcount;
    }

    /**
     * Подсчитывает кол-во участников прошедших тренинг и кэширует это значение в таблице lm_activity
     * для дальнейшего использования в сортировках
     *
     * @return int
     */
    public function recalculate_trained_count(){
        global $DB;

        $this->needActivityId();

        $count = $DB->count_records_select('lm_activity_request', 'activityid=? AND passed > 0', array($this->activityid));
        if($count != $this->trainedcount){
            $this->trainedcount = $count;

            $dataobj = new StdClass();
            $dataobj->id = $this->id;
            $dataobj->trainedcount = $this->trainedcount;

            return $DB->update_record('lm_activity', $dataobj);
        }

        return false;
    }

    /**
     * Возвращает кол-во участников успешно прошедших тренинг (активность)
     *
     * @return int
     */
    public function count_trained_members(){
        return $this->trainedcount;
    }

    /**
     * Определяет записан ли участник на тренинг
     *
     * @param $userid
     * @param $partnerid
     * @return bool
     */
    public function is_member_exists($userid, $partnerid){
        global $DB;

        $this->needActivityId();

        return $DB->record_exists('lm_activity_request', array('activityid'=>$this->activityid, 'userid'=>$userid, 'partnerid'=>$partnerid));
    }

    /**
     * Устанавливает дату проведения тренинга, либо изменяет уже существующую (если установлен $dateid)
     *
     * @param $datefrom
     * @param $dateto
     * @param int $dateid
     * @return bool
     */
    public function set_date($datefrom, $dateto, $dateid=0){

        $date = new StdClass();
        $date->start = $datefrom-3600*3;
        $date->end = $dateto-3600*3;
        if(isset($this->properties['dates'][$dateid-1])){
            $this->properties['dates'][$dateid-1] = $date;
        }else{
            $this->properties['dates'][] = $date;
        }

        return $this->recalculate_dates();
    }

    /**
     * Убирает дату проведения тренинга с порядковым номером равным $dateid
     *
     * @param $dateid
     * @return bool
     */
    public function remove_date($dateid){
        if(isset($this->properties['dates'][$dateid-1])){
            unset($this->properties['dates'][$dateid-1]);
            return $this->recalculate_dates();
        }

        return false;
    }

    /**
     * Возвращает количество установленных диапазонов дат
     *
     * @return int
     */
    public function count_dates(){
        return count($this->properties['dates']);
    }

    /**
     * Смотрит в properties и пересчитывает дату начала/окончания тренинга, а также продолжительность в часах.
     * После пересчета, данные будут обновлены в таблице lm_activity. Эти данные необходимы для выборок,
     * построения отчетов, сортировок.
     *
     * @return bool
     */
    public function recalculate_dates(){
        global $DB;

        $dataobj = new StdClass();
        $dataobj->id = $this->id;
        $dataobj->hourscount = 0;
        $dataobj->startdate = 0;
        $dataobj->enddate = 0;
        if(isset($this->properties['dates']) && is_array($this->properties['dates'])){
            foreach($this->properties['dates'] as $date){
                $dataobj->hourscount = $dataobj->hourscount + ($date->end - $date->start) / 3600;

                if($date->start < $dataobj->startdate || !$dataobj->startdate) $dataobj->startdate = $date->start;
                if($date->end > $dataobj->enddate) $dataobj->enddate = $date->end;
            }
        }

        $this->properties['hours'] = $dataobj->hourscount;
        $this->startdate = $dataobj->startdate;
        $this->enddate = $dataobj->enddate;

        $this->hourscount = $dataobj->hourscount;

        $dataobj->properties = serialize($this->properties);

        return $DB->update_record('lm_activity', $dataobj);
    }

    /**
     * Возвращает общую продолжительность тренинга в часах из кэшированного значения
     *
     * @return int
     */
    public function count_hours(){
        return self::float2hours($this->hourscount);
    }

    /**
     * Форматирует число с запятой в формат продолжительности часов, например 2,50 отформатирует в 2ч. 30мин.
     * @param $float
     * @return string
     */
    public static function float2hours($float){
        $hours = floor($float);
        $minutes = round(60* ($float - $hours));
        if($hours) {
            $hours .= 'ч.';
        }else{
            $hours = '';
        }

        if($minutes) {
            $minutes .= 'мин.';
        }else{
            $minutes = '';
        }

        return "$hours $minutes";
    }

    /**
     * Возвращает дату начала тренинга в формате удобном для человека :)
     * Если дата не установлена, то возвращает пустую строку
     *
     * @return bool|string
     */
    public function date_start(){
        $datestart = '';
        if($this->startdate){
            $datestart = date('d.m.Y', $this->startdate);
        }

        return $datestart;
    }

    /**
     * Возвращает дату окончания тренинга в формате удобном для человека :)
     * Если дата не установлена, то возвращает пустую строку
     *
     * @return bool|string
     */
    public function date_end(){
        $dateend = '';
        if($this->enddate){
            $dateend = date('d.m.Y', $this->enddate);
        }

        return $dateend;
    }

    /**
     * Возвращает диапазон дат проведения тренинга в удобном для человека формате :)
     *
     * @return string
     */
    public function date_range(){
        return $this->date_start().' - '.$this->date_end();
    }

    /**
     * Возвращает код текущего статуса тренинга
     *
     * @return string
     */
    public function get_status(){
        $current = time();

        if($this->startdate > $current && $this->enddate > $current) {
            return 'registering';
        }else if($this->enddate&& $this->enddate < $current){
            return 'finished';
        }else if($this->startdate > $current && $this->enddate <= $current){
            return 'inprocess';
        }

        return '';
    }

    /**
     * Возвращает название текущего статуса тренинга
     *
     * @return string
     */
    public function get_status_name(){
        $statuses = array('registering'=>'Еще не проведен', 'finished'=>'Завершен', 'inprocess'=>'Идет сейчас...');
        $status = $this->get_status();

        if(isset($statuses[$status])){
            return $statuses[$status];
        }

        return '';
    }

    /**
     * Возвращает массив доступных типов активности
     *
     * @return array
     */
    public static function types(){
        return array('auditory'=>'Аудиторная', 'online'=>'Онлайн', 'field'=>'Полевая', 'method'=>'Методическая');
    }

    /**
     * Возвращает название типа активности
     *
     * @return string
     */
    public function get_type(){
        $types = self::types();
        if(isset($types[$this->type])){
            return $types[$this->type];
        }

        return '';
    }

    /**
     * Имеет ли право текущий пользователь просматривать активность?
     *
     * @return bool
     * @throws Exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public function has_capability_view(){
        global $USER;

        if($USER->id == $this->trainerid){
            return true;
        }

        if(has_capability('block/manage:activitiesview', context_system::instance())){
            return true;
        }

        // Контактное лицо партнера может просматривать активности, если программа совпадает с одной из назначенных
        $partnerid = get_my_company_id();
        $appointed = lm_partner::i($partnerid)->get_appointed_programs_ids();
        if(in_array($this->programid, $appointed)){
            return true;
        }

        // Тренер может просматривать любую активность
        if(lm_user::is_trainer() || lm_user::is_tm()){
            return true;
        }

        if($this->has_capability_edit()){
            return true;
        }

        return false;
    }

    /**
     * Имеет ли право текущий пользователь редактировать активность?
     *
     * @return bool
     * @throws Exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public function has_capability_edit(){
        global $USER;

        if($USER->id == $this->trainerid){
            return true;
        }

        if(has_capability('block/manage:activityedit', context_system::instance())){
            return true;
        }

        return false;
    }
}