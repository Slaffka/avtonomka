<?php
class lm_staffer extends lm_user{
    static private $i = NULL;

    public $id = 0;
    public $partnerid = 0;
    public $userid = 0;
    public $type = 0;
    public $stageid = 0;
    public $archive = 0;



    /**
     * @param $activityid
     * @return lm_staffer
     */
    static public function i($partnerid, $userid){
        if(!isset(self::$i[$partnerid][$userid])){
            self::$i[$partnerid][$userid] = new lm_staffer($partnerid, $userid);
        }

        return self::$i[$partnerid][$userid];
    }

    static public function self(){
        global $USER;

        if( $partnerid = lm_user::get_partnerid() ) {
            return lm_staffer::i($partnerid, $USER->id);
        }
        return false;
    }

    private function __construct($partnerid, $userid){
        global $CFG, $DB;

        if($partnerid && is_numeric($partnerid) && $userid && is_numeric($userid)) {

            $sql = "SELECT lps.*, u.firstname, u.lastname
                      FROM {lm_partner_staff} lps
                      JOIN {user} u ON u.id=lps.userid
                      WHERE lps.userid={$userid} AND lps.partnerid={$partnerid}";

            $staffer = $DB->get_record_sql($sql);
        }else{
            die('Ошибка в классе lm_staffer!');
        }

        if ($staffer) {
            foreach ($staffer as $field=>$value) {
                $this->$field = $value;
            }
        }

        if( $CFG->lm_matrix_enabled && !$this->stageid ){
            $this->set_stageid(1);

        }

        return $this;
    }



    /**
     * Идентификатор этапа развития сотрудника (см. матрицу развития)
     */
    public function get_stageid(){
        return $this->stageid;
    }

    public function set_stageid($stageid){
        global $DB;
        $dataobj = new StdClass();
        $dataobj->id = $this->id;
        $dataobj->stageid = $stageid;
        $this->stageid = $stageid;

        return $DB->update_record('lm_partner_staff', $dataobj);
    }

    public function set_next_stage(){

        if($this->stageid > 4){
            return false;
        }

        $this->stageid ++;

        return $this->set_stageid($this->stageid);
    }


    public function programs($stageid = 0){
        global $CFG;

        if(!$stageid) $stageid = $this->stageid;
        $mypostid = $this->post()->get_id();
        if( $programs = lm_matrix::programs($mypostid, $stageid) ){
            $usercourses = enrol_get_users_courses($this->userid);
            $prevprogram = false;
            $partner = lm_partner::i($this->partnerid);

            if( $this->stageid == $stageid ) {
                $partner->recalculate_staffer_progress_all_programs($this->userid);
                $partner->recalculate_staffer_progress($this->userid);

                $stageprogress = $partner->staffer_progress($this->userid);
                if( $stageprogress >= 100 ){
                    $this->set_next_stage();
                }
            }



            foreach($programs as $program){
                $courseid = lm_programs::get_courseid($program->id);
                $program->available = false;
                if( isset($usercourses[$courseid]) ){
                    $program->available = true;
                }

                $program->progress = $this->get_program_result($program->id, $stageid);

                if( $this->stageid == $stageid ) {
                    // Если сотрудник не назначен на первую программу в списке
                    $is_initial_enrolled = !(!$program->available && $courseid && !$prevprogram);

                    // Если предыдущая программа пройдена, а след. не назначена
                    $is_next_enrolled = !($prevprogram && $prevprogram->progress >= 80 && !$program->available && $courseid);

                    if (!$is_initial_enrolled || !$is_next_enrolled) {
                        lm_course::i($courseid)->enrol($this->userid, $CFG->block_manage_studentroleid);
                        $program->available = true;
                    }
                }

                $prevprogram = $program;
            }
        }

        return $programs;
    }

    public function get_stage_progress($stageid){
        return $this->get_program_result(0, $stageid);
    }

    /**
     * Возвращает список успешно пройденных программ сотрудником
     *
     * @param $userid
     * @return array
     */
    public function passed_programs(){
        global $DB;

        $sql = "SELECT lar.*
                      FROM {lm_activity_request} lar
                      WHERE lar.passed > 0 AND lar.partnerid={$this->partnerid} AND lar.userid={$this->userid}";

        return $DB->get_records_sql($sql);
    }

    /**
     * Информация о затраченных часах по каждому виду обучения (аудиторное, дистанционное, полевое)
     *
     * @return array
     */
    public function passed_programs_stat(){
        global $DB;

        $sql = "SELECT lar.id, la.programid, la.type, la.hourscount
                      FROM {lm_activity_request} lar
                      JOIN {lm_activity} la ON la.id=lar.activityid
                      WHERE lar.passed > 0 AND lar.partnerid={$this->partnerid} AND lar.userid={$this->userid}";


        $result = lm_activity::types();
        $result = array_combine($result, array_fill(0, count($result), 0) );

        if($requests = $DB->get_records_sql($sql)){
            foreach($requests as $request){
                if(!isset($result[$request->type])) $result[$request->type] = 0;
                $result[$request->type] = $result[$request->type] + $request->hourscount;
            }
        }

        return $result;
    }


    /**
     * Последние результаты по тестам из курса $courseid
     *
     * @param $courseid
     * @return array
     */
    public function last_attempt_grades($courseid){
        global $CFG;

        require_once($CFG->dirroot.'/mod/quiz/locallib.php');

        $grades = array();
        if($quizes = lm_course::quizes_by_course($courseid)){
            foreach($quizes as $quiz){
                $id = 0;
                if($attempts = quiz_get_user_attempts($quiz->id, $this->userid, 'finished', true)){
                    $attempt = array_pop($attempts);
                    $grade = quiz_rescale_grade($attempt->sumgrades, $quiz, false);
                    $grade = round($grade * 100 / $quiz->grade, 1);
                    $id = $attempt->id;
                }else{
                    $grade = false;
                }

                $grades[$quiz->id] = (object) array('id'=>$id, 'grade'=>$grade, 'name'=>$quiz->name);
            }
        }

        return $grades;
    }


    public function calculate_program_result($programid){
        $courseid = lm_programs::get_courseid($programid);
        return lm_course::calculate_course_result($courseid, $this->userid);
    }

    public function get_program_result($programid, $stageid = 0){
        global $DB;

        if(!$stageid) $stageid = $this->stageid;

        $conditions = array(
            'partnerid' => $this->partnerid,
            'userid'    => $this->userid,
            'programid' => $programid,
            'stageid'   => $stageid
        );
        return (int) $DB->get_field('lm_partner_staff_progress', 'progress',  $conditions );
    }


    /**
     * @param int $programid
     * @return float
     */
    public function get_program_coins($programid){
        return lm_bank::i($this->userid)->get_sum('program', $programid);
    }

    /**
     * @param int $programid
     * @param int $stageid
     * @param bool $group
     * @return int|array|NULL
     */
    public function get_program_mistakes($programid, $stageid = 0, $group = false){
        global $DB;
        if(!$stageid) $stageid = $this->stageid;

        if ($group) {
            $program = lm_program::i($programid);
            if ($program) return $program->get_mistakes($this->userid, true);
            else return FALSE;
        } else {
            $conditions = array(
                'partnerid' => $this->partnerid,
                'userid'    => $this->userid,
                'programid' => $programid,
                'stageid'   => $stageid
            );

            $value = $DB->get_field('lm_partner_staff_progress', 'mistakes',  $conditions );
            if ( ! is_null($value)) $value = (int) $value;

            return $value;
        }
    }


    /**
     * @param int $programid
     * @param int $stageid
     * @return int|NULL
     */
    public function get_program_duration($programid, $stageid = 0){
        global $DB;

        if( ! $stageid) $stageid = $this->stageid;

        $conditions = array(
            'partnerid' => $this->partnerid,
            'userid'    => $this->userid,
            'programid' => $programid,
            'stageid'   => $stageid
        );

        $value = $DB->get_field('lm_partner_staff_progress', 'duration',  $conditions );
        if ( ! is_null($value)) $value = (int) $value;

        return $value;
    }


    /**
     * Возвращает результат последнего теста в курсе (последняя попытка).
     *
     * @param $courseid
     * @param $userid
     * @return stdClass
     */
    public static function get_last_quiz_attempt($courseid, $userid){
        global $CFG;

        require_once($CFG->dirroot.'/mod/quiz/locallib.php');

        if(!$courseid) {
            return false;
        }

        if($quizes = lm_course::quizes_by_course($courseid)){
            $quiz = array_pop($quizes);
            if($quiz){
                if($attempts = quiz_get_user_attempts($quiz->id, $userid, 'finished', true)){
                    $attempt = array_pop($attempts);
                    return $attempt;
                }
            }
        }

        return false;
    }
    public function get_program_last_quiz_attempt($programid){
        $courseid = lm_programs::get_courseid($programid);
        return self::get_last_quiz_attempt($courseid, $this->userid);
    }

    /**
     * Проверяет оставил ли сотрудник обратную связь по курсу $courseid. Обратной связью считается модуль feedback
     * с типом qualitycheck. Если таких модулей несколько в курсе, то берется последний из них.
     *
     * @param $courseid
     * @return NULL|bool|object Возвращает NULL в случае, если в курсе нет модуля обратной связи
     *                          Возвращает false в случае, если сотрудник не оставил обратную связь
     *                          Возвращает массив с ключами: completedid (идентификатор завершенного фидбэка) и
     *                                                       id (идентификатор модуля в курсе из таблицы course_modules)
     * @throws dml_missing_record_exception
     * @throws dml_multiple_records_exception
     */
    public function feedback($courseid){
        global $DB;

        $sql = "SELECT cm.id, f.id as feedbackid, f.name
                     FROM {modules} m
                     JOIN {course_modules} cm ON m.id=cm.module AND cm.course={$courseid}
                     JOIN {feedback} f ON cm.instance=f.id AND f.type LIKE 'qualitycheck'
                     WHERE m.name LIKE 'feedback'
                     ORDER BY cm.section DESC, cm.added DESC, cm.id DESC"; // Сортировка нужна для того, чтобы выбрать последний модуль, в случае если их несколько

        $module = $DB->get_record_sql($sql);
        if($module){
            $completes = $DB->get_records_select('feedback_completed', "feedback={$module->feedbackid} AND userid={$this->userid}", array(), 'timemodified DESC');

            if($completes){
                $complete = array_shift($completes);
                return (object) array('completedid'=>$complete->id, 'id'=>$module->id, 'time'=>$complete->timemodified,
                                      'feedbackid'=>$complete->feedback);
            }else{
                return false;
            }
        }else{
            return NULL;
        }
    }

    /**
     * Конвертирует результат фидбэка (то, что возвращает $this->feedback) в простой результат,
     * с 3-мя возможными строковыми вариантами на выходе: "Да", "Нет", " - "
     *
     * @param $feedback
     * @return string
     */
    protected function feedback2plain($feedback){
        $feedbackstr = 'Нет';
        if($feedback === NULL) {
            $feedbackstr = ' - ';
        }else if(isset($feedback->completedid) && isset($feedback->id)){
            $feedbackstr = 'Да';
        }

        return $feedbackstr;
    }

    /**
     * Возвращает простой результат фидбэка, 3 возможных варианта: "Да", "Нет", " - "
     *
     * @param $courseid
     * @return string
     */
    public function feedback_plain($courseid){
        $feedback = $this->feedback($courseid);
        return $this->feedback2plain($feedback);
    }

    /**
     * Возвращает:
     * - html ссылку на анкету обратной связи
     * - "Нет", если сотрудник не оставил обратную связи
     * - " - ", если в курсе нет обратной связи
     *
     * @param $courseid
     * @return string
     */
    public function feedback_link($courseid){
        $feedbackstr = 'Нет';
        $feedback = $this->feedback($courseid);

        if($feedback === NULL) {
            $feedbackstr = ' - ';
        }else if(isset($feedback->completedid) && isset($feedback->id)){
            $feedbackstr = '<a href="/mod/feedback/show_entries.php?id='.$feedback->id.
                '&do_show=showoneentry&userid='.$this->userid.
                '&completeid='.$feedback->completedid.'" target="_blank">Да</a>';
        }

        return $feedbackstr;
    }

    /**
     * Возвращает пользователя, который записал этого сотрудника на активность $activityid
     *
     * @param $activityid
     * @return mixed
     * @throws dml_missing_record_exception
     * @throws dml_multiple_records_exception
     */
    public function who_requested_activity($activityid){
        global $DB;

        $sql = "SELECT lar.id as requestid, u.*
                     FROM {lm_activity_request} lar
                     LEFT JOIN {user} u ON lar.requestedby=u.id
                     WHERE activityid={$activityid} AND userid={$this->userid} AND partnerid={$this->partnerid}";

         return $DB->get_record_sql($sql);
    }
}