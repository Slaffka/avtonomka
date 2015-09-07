<?php

class lm_course
{
    public static $i = NULL;

    public $id = 0;
    public $fullname = NULL;
    public $shortname = NULL;

    private $scorms = array();

    /**
     * @param $courseid
     * @return lm_course
     */
    static public function i($courseid){
        $course = 0;
        if($courseid && is_numeric($courseid)) {
            $course = $courseid;
        }else if($courseid && is_object($courseid)){
            $course = clone $courseid;
            $courseid = $course->id;
        }

        if(!isset(self::$i[$courseid])){
            self::$i[$courseid] = new lm_course($course);
        }

        return self::$i[$courseid];
    }

    private function __construct($courseid){
        global $DB;

        if($courseid && is_numeric($courseid)) {
            $course = $DB->get_record('course', array('id'=>$courseid), 'id, fullname, shortname');
        }else if($courseid && is_object($courseid)){
            $course = $courseid;
        }else{
            die('Ошибка в классе lm_course!');
        }

        if ($course) {
            foreach ($course as $field => $value) {
                if(property_exists($this, $field)) $this->$field = $value;
            }
        }

        return $this;
    }

    /**
     * Тесты из курса $courseid
     *
     * @param $courseid
     * @return array
     */
    public static function quizes_by_course($courseid)
    {
        global $DB;

        $sql = "SELECT q.*
                    FROM {course_modules} cm
                    JOIN {modules} m ON cm.module=m.id
                    JOIN {quiz} q ON q.id=cm.instance
                    WHERE m.name LIKE 'quiz' AND cm.course={$courseid}";

        return $DB->get_records_sql($sql);
    }

    /**
     * Возвращает последний тест в курсе
     *
     * @return cm_info
     */
    public function get_last_quize()
    {
        $modinfo = get_fast_modinfo( (object) array('id'=>$this->id) );
        if( !$cms = $modinfo->get_cms() ) return NULL; // Курс еще пуст

        $lastquiz = false;
        foreach($cms as $cm) {
            if ($cm->modname == 'quiz') $lastquiz = $cm;
        }

        return $lastquiz;
    }

    /**
     * Возвращает итоговый результат по курсу. Итоговый результат - это результат последнего теста в курсе.
     * Если было несколько попыток, то вернется результат последней.
     *
     * @param $courseid
     * @param $userid
     * @return bool|float|string
     */
    public static function calculate_course_result($courseid, $userid)
    {
        global $CFG;

        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        if (!$courseid) return false;

        $grade = false;
        if ($quizes = self::quizes_by_course($courseid)) {
            if ( $quiz = array_pop($quizes) ) {
                return self::calculate_quiz_result($quiz, $userid);
            }
        }

        return $grade;
    }

    public static function calculate_quiz_result($quiz, $userid){
        global $DB;

        $quizresult = NULL;
        if( !is_object($quiz) )$quiz = $DB->get_record('quiz', array('id' => $quiz));
        if( !$quiz ) return $quizresult;

        if( $attempt = self::get_quiz_last_attempt($quiz->id, $userid) ){
            if($attempt->state == 'finished') {
                $grade = quiz_rescale_grade($attempt->sumgrades, $quiz, false);
                $quizresult = round($grade * 100 / $quiz->grade, 1);
            }
        }
        return $quizresult;
    }

    public static function get_quiz_last_attempt($quizid, $userid){
        $attempt = false;
        if( $attempts = quiz_get_user_attempts($quizid, $userid, 'all', false) ){
            $attempt = array_pop($attempts);
        }
        return $attempt;
    }

    /**
     * Возвращает кол-во ошибок по курсу. Итоговый результат - это результат последнего теста в курсе.
     * Если было несколько попыток, то вернется результат последней.
     *
     * @param int $courseid
     * @param int $userid
     * @return int|NULL
     */
    public function get_mistakes($userid, $group = FALSE)
    {
        global $CFG;

        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        if ( ! $this->id) {
            return false;
        }

        $mistakes = NULL;

        /*// mistakes in quiz calculation
        if ($quizes = self::quizes_by_course($courseid)) {
            $quiz = array_pop($quizes);
            if ($quiz) {
                if ($attempts = quiz_get_user_attempts($quiz->id, $userid, 'finished', true)) {
                    $attempt = array_pop($attempts);

                    $mistakes = self::_quiz_get_attempt_mistakes($attempt, $group);
                }
            }
        }
        */

        if ($scorms = $this->get_scorms()) {
            foreach ($scorms as $scorm) {
                $scorm_mistakes = lm_scorm::get_mistakes($scorm->id, $userid, $group);
                if ( ! is_null($scorm_mistakes)) {
                    if ($group) {
                        $mistakes = $mistakes ? $mistakes : array();
                        // сложить ошибки поблочно если скорм несколько
                        foreach ($scorm_mistakes as $block) {
                            $mistakes[$block->name] += $block->value;
                        }
                    } else {
                        $mistakes += $scorm_mistakes;
                    }
                }
            }
        }

        return $mistakes;
    }

    /**
     * Подсчитывает кол-во ошибок в попытке
     * @param stdClass $attempt попытка сдачи теста
     * @param bool $group группировать ошибки по группам
     * @return int|array
     */
    public static function _quiz_get_attempt_mistakes(stdClass $attempt, $group = false)
    {
        global $DB;

        if (!$attempt->id) return FALSE;

        $is_attempt_correct =
            (int)$attempt->userid > 0
            && (int)$attempt->timestart > 0
            && (int)$attempt->timefinish > 0
            && (int)$attempt->timefinish > (int)$attempt->timestart;

        if (!$is_attempt_correct) return FALSE;

        $select = "COUNT(*) as `count`";
        $from = "{question_attempt_steps} qas";
        if ($group) {
            $select = "q.category, COUNT(qas.id) as `count`";
            $from .= "
                LEFT JOIN {question_attempts} qa ON qa.id = qas.questionattemptid
                LEFT JOIN {question} q ON q.id = qa.questionid
            ";
        }

        //TODO: связь вопроса с тестом по времени прохождения - это плохо
        $sql = "
            SELECT {$select}
            FROM {$from}
            WHERE
              qas.userid = :userid
              AND qas.sequencenumber = 2
              AND qas.state = 'gradedwrong'
              /*AND qas.timecreated > :timestart*/
              AND qas.timecreated /*<*/= :timefinish
        ";

        if ($group) {
            $sql .= "GROUP BY q.category";
            $mistakes = $DB->get_records_sql_menu($sql, (array)$attempt);
        } else {
            $mistakes = $DB->get_field_sql($sql, (array)$attempt);
        }

        return $mistakes;
    }

    /**
     * Возвращает время, проведенное в SCORM-пакете
     *
     * @param int $userid
     * @return int|NULL
     */
    public function get_duration($userid)
    {
        if ( ! $this->id) {
            return false;
        }

        $duration = NULL;
        if ($scorms = $this->get_scorms()) {
            foreach ($scorms as $scorm) {
                $scorm_duration = lm_scorm::get_duration($scorm->id, $userid);
                if ( ! is_null($scorm_duration)) $duration += $scorm_duration;
            }
        }

        return $duration;
    }

    public function enrol($userid, $roleid)
    {
        global $DB, $CFG;

        if (!$enrol_manual = enrol_get_plugin('manual')) {
            throw new coding_exception('Can not instantiate enrol_manual');
        }


        if(!$instance = $DB->get_record('enrol', array('courseid'=>$this->id, 'enrol'=>'manual'))){
            $a = "{$CFG->wwwroot}/enrol/instances.php?id={$this->id}";
            throw new moodle_exception('error_requiremanualenrolcapability', 'block_manage', '', $a);
        }

        $enrol_manual->enrol_user($instance, $userid, $roleid);
    }

    /**
     * SCORM-пакеты из курса
     *
     * @return array
     */
    public function get_scorms(){

        global $DB;

        if (! $this->id) return FALSE;

        if (empty($this->scorms)) {
            $sql = "SELECT q.*
                    FROM {course_modules} cm
                    JOIN {modules} m ON cm.module=m.id
                    JOIN {scorm} q ON q.id=cm.instance
                    WHERE m.name LIKE 'scorm' AND cm.course={$this->id}";


            $this->scorms = $DB->get_records_sql($sql);
        }

        return $this->scorms;
    }

    /**
     * Возвращает предыдущий завершенный модуль
     *
     * @return bool|cm_info|null
     */
    public function get_prev_finished_module(){
        $modinfo = get_fast_modinfo( (object) array('id'=>$this->id) );
        if( !$cms = $modinfo->get_cms() ) {
            // Курс еще пуст
            return NULL;
        }

        $finished = array();
        $modules = array();
        foreach($cms as $cm){
            if( count($finished) >= 3 ){
                array_shift($finished);
                array_shift($modules);
            }

            $finished[] = $this->is_module_completed($cm);
            $modules[] = $cm;

            if( isset($finished[0]) && $finished[0]
                && isset($finished[1]) && $finished[1]
                && isset($finished[2]) && !$finished[2]
            ){
                return $modules[0];
            }
        }

        return false;
    }

    /**
     * Возвращает последний завершенный модуль
     *
     * @return bool|cm_info|null
     */
    public function get_last_finished_module(){
        $modinfo = get_fast_modinfo( (object) array('id'=>$this->id) );
        if( !$cms = $modinfo->get_cms() ) {
            // Курс еще пуст
            return NULL;
        }

        $currentcm = false;
        foreach($cms as $cm){
            if( !$this->is_module_completed($cm) ){
                return $currentcm;
            }
            $currentcm = $cm;
        }

        return $currentcm;
    }

    /**
     * Возвращает модуль следующий за завершенным текущим пользователем
     *
     * @return bool|mixed
     */
    public function get_next_module(){

        $modinfo = get_fast_modinfo( (object) array('id'=>$this->id) );
        if( !$cms = $modinfo->get_cms() ) {
            // Курс еще пуст
            return NULL;
        }

        $nextcm = false;
        foreach($cms as $cm){
            if( !$this->is_module_completed($cm) ){
                return $cm;
            }
        }

        return $nextcm;
    }

    /**
     * Проверяет завершен ли модуль текущим пользователем
     *
     * @param $cm
     * @return bool
     */
    private function is_module_completed($cm){
        global $DB, $USER;

        $finished = false;
        if($cm->modname == 'scorm') {
            $scormid = $DB->get_field("scorm", "id", array("id" => $cm->instance));
            if( lm_scorm::get_last_completed_attempt($scormid, $USER->id) ) $finished = true;
        }else if($cm->modname == 'quiz') {
            if( $lastattempt = self::get_quiz_last_attempt($cm->instance, $USER->id) ){
                if( $lastattempt->state == 'finished' ) return true;
            }
            //$quizresult = lm_course::calculate_quiz_result($cm->instance, $USER->id);
        }else{
            $completion = new completion_info( (object) array("id" => $this->id) );
            if ( $completion->is_enabled($cm) ) {
                $current = $completion->get_data($cm, false, $USER->id);
                if( $current->completionstate == COMPLETION_COMPLETE_PASS ||
                    $completion->internal_get_state($cm, $USER->id, $current) == COMPLETION_COMPLETE
                ){
                    $finished = true;
                }
            }else{
                // Отслеживание выполнения для этого модуля отключено
                // TODO: Записать предупреждение в лог для администратора
            }
        }

        return $finished;
    }

    public function is_module_passed($cm){
        global $USER;

        $passed = false;
        switch( $cm->modname ){
            case 'quiz':
                $quizresult = lm_course::calculate_quiz_result($cm->instance, $USER->id);
                if($quizresult > 80) return true;
                /*
                Можно выставлять в журнале минимальный проходной балл, но что-то не определяет, что модуль пройден
                Ппц они запрятали настройку проходного балла:
                В блоке "настройки" Оценки/Категории и элементы/Редактировать/Проходной балл

                $completion = new completion_info( (object) array("id" => $this->id) );
                if ( $completion->is_enabled($cm) ) {
                    $current = $completion->get_data($cm, false, $USER->id);
                    if ($current->completionstate == COMPLETION_COMPLETE_PASS ||
                        $completion->internal_get_state($cm, $USER->id, $current) == COMPLETION_COMPLETE
                    ) {
                        $passed = true;
                    }
                }*/
                break;
            default:
                $passed = $this->is_module_completed($cm);
                break;
        }

        return $passed;
    }

    public function clear_all_attempts($userid){
        global $CFG, $DB;

        require_once($CFG->dirroot.'/mod/scorm/locallib.php');
        require_once($CFG->libdir . '/completionlib.php');

        $course = $DB->get_record('course', array('id'=>$this->id));
        $modinfo = new course_modinfo($course, $userid);
        if( $cms = $modinfo->get_cms() ){
            foreach( $cms as $cm ){
                $this->clear_module_attempt($cm);
            }
        }

        $completion = new completion_info($course);
        $completion->delete_all_completion_data();
    }

    public function clear_module_attempt($module){
        global $DB, $USER;

        switch( $module->modname ){
            case 'userverifier':
                $conditions = array('userverifier'=>$module->instance, 'userid'=>$USER->id);
                $DB->delete_records('userverifier_photo', $conditions);
                break;

            case 'scorm':
                $scorm = $DB->get_record("scorm", array("id" => $module->instance));
                if( $attemptid = scorm_get_last_completed_attempt($module->instance, $USER->id) ){
                    if( !scorm_delete_attempt($USER->id, $scorm, $attemptid) ){
                        // Не удалось очистить попытку
                        // TODO: предупреждение в лог администратору
                    }
                } else {
                    // Попытка не найдена...
                    // TODO: предупреждение в лог разработчикам
                }
                break;
            case 'quiz':
                if( $attempts = quiz_get_user_attempts($module->instance, $USER->id, 'all', true) ){
                    $quiz = $DB->get_record('quiz', array('id' => $module->instance));
                    foreach($attempts as $attempt){
                        quiz_delete_attempt($attempt, $quiz);
                    }
                }

                break;
            case 'feedback':
                $tracks = $DB->get_records('feedback_tracking', array('feedback'=>$module->instance, 'userid'=>$USER->id));
                if( $tracks ){
                    foreach($tracks as $track){
                        feedback_delete_completed($track->completed);
                    }
                }
                break;
            default:

                break;
        }
    }

    /**
     * Возвращает кол-во полученных монет за курс
     *
     * @return int
     */
    public function calculate_course_coins(){
        global $USER, $DB;

        $coins = 0;
        $course = $DB->get_record('course', array('id'=>$this->id));
        $modinfo = new course_modinfo($course, $USER->id);
        if( $cms = $modinfo->get_cms() ){
            foreach( $cms as $cm ){
                if( $cm->modname == 'scorm' ) {
                    list($scromcoins, $maxscormcoins) = $this->calculate_scorm_coins($cm->instance);
                    $coins += $scromcoins;
                }
            }
        }

        return $coins;
    }

    /**
     * Возвращает кол-во полученных монет из максимально возможных по данному SCORM-пакету
     *
     * @param $cminstanceid
     * @return array
     */
    public function calculate_scorm_coins($cminstanceid){
        global $USER;

        $coins = 0;
        $maxcoins = 0;
        if( $attempt = lm_scorm::get_last_completed_attempt($cminstanceid, $USER->id) ){
            $last_interaction = $attempt->interactions[count($attempt->interactions) - 1];
            if( isset($last_interaction['learner_response']->coins) ){
                $coins = $last_interaction['learner_response']->coins;
            }else{
                // TODO: предупреждение в лог администратору о том, что SCORM не содержит инфо о монетах
            }

            if( isset($last_interaction['learner_response']->maxcoins) ){
                $maxcoins = $last_interaction['learner_response']->maxcoins;
            }else{
                // TODO: предупреждение в лог администратору о том, что SCORM не содержит инфо о монетах
            }
        }

        return array($coins, $maxcoins);
    }
}