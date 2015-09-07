<?php
class block_manage_trainer{
    static private $i = NULL;

    public $id = 0;
    public $firstname = '';
    public $lastname = '';

    /**
     * @param $userid
     * @return block_manage_trainer
     */
    static public function i($userid){
        if(!isset(self::$i[$userid])){
            self::$i[$userid] = new block_manage_trainer($userid);
        }

        return self::$i[$userid];
    }

    private function __construct($userid){
        global $DB;
        $userfields = $DB->get_record('user', array('id'=>$userid), 'id, firstname, lastname');
        foreach($userfields as $field=>$value){
            $this->$field = $value;
        }
    }

    /**
     * Возвращает ФИО тренера
     *
     * @return string
     */
    public function fullname(){
        return $this->lastname.' '.$this->firstname;
    }

    /**
     * Возвращает ссылку на профиль тренера
     *
     * @return string
     */
    public function link(){
        return '<a href="/user/view.php?id='.$this->id.'" target="_blank">'.$this->fullname().'</a>';
    }

    public function get_my_programs(){
        global $DB;

        $sql = "SELECT lp.id, lp.courseid, lp.name
                      FROM {lm_activity} la
                      JOIN {lm_program} lp ON lp.id=la.programid
                      WHERE la.trainerid={$this->id}";

        return $DB->get_records_sql($sql);
    }

    /**
     * Кол-во программ обучения, проводимых тренером
     *
     * @return int
     * @throws coding_exception
     */
    public function count_my_programs(){
        global $DB;

        $sql = "SELECT COUNT(DISTINCT(la.programid)) FROM {lm_activity} la WHERE la.trainerid={$this->id}";
        return $DB->count_records_sql($sql);
    }

    /**
     * Возвращает список участников, которые посещали тренинги тренера за период времени
     *
     * @param int $datestart
     * @param int $dateend
     * @return array
     */
    public function get_listeners($datestart=0, $dateend=0){
        global $DB;

        $where = "la.trainerid={$this->id}";
        $where = $this->get_date_where($where, $datestart, $dateend);

        $sql = "SELECT u.id, u.firstname, u.lastname, lar.partnerid
                      FROM {lm_activity_request} lar
                      JOIN {lm_activity} la ON la.id=lar.activityid
                      JOIN {user} u ON u.id=lar.userid
                      WHERE $where
                      GROUP BY lar.userid";

        return $DB->get_records_sql($sql);
    }

    /**
     * Подсчитывает кол-во обученных этим тренером участников за период
     *
     * @param $auditory
     * @param int $datestart
     * @param int $dateend
     * @return int
     * @throws coding_exception
     */
    public function trained_count($auditory, $datestart=0, $dateend=0){
        global $DB;

        $where = "la.trainerid={$this->id} AND lar.passed > 0";
        $where = $this->get_date_where($where, $datestart, $dateend);

        $join = "";
        if($auditory){
            $join = "JOIN {lm_partner} lp ON lar.partnerid=lp.id
                     JOIN {lm_company} lc ON lp.companyid=lc.id AND lc.type='{$auditory}'";
        }

        $sql = "SELECT COUNT(lar.id)
                      FROM {lm_activity_request} lar
                      JOIN {lm_activity} la ON la.id=lar.activityid
                      $join
                      WHERE $where";

        return $DB->count_records_sql($sql);
    }

    /**
     * Подсчитывает кол-во часов, потраченное на обучение участников
     *
     * @param $type
     * @param null $auditory
     * @param $datestart
     * @param $dateend
     * @return int
     */
    public function count_hours($type, $auditory=null, $datestart, $dateend){
        global $DB;

        $types = lm_activity::types();
        if($type && !isset($types[$type])){
            return false;
        }

        $where = "la.trainerid={$this->id}";
        if($type){
            $where .= " AND type LIKE '{$type}'";
        }

        $join = "";
        if($auditory){
            $join = "JOIN {lm_activity_request} lar ON la.id=lar.activityid
                      JOIN {lm_partner} lp ON lar.partnerid=lp.id
                      JOIN {lm_company} lc ON lp.companyid=lc.id AND lc.type='{$auditory}'";
        }

        $where = $this->get_date_where($where, $datestart, $dateend);
        $sql = "SELECT la.id, la.hourscount
                      FROM {lm_activity} la
                      $join
                      WHERE $where
                      GROUP BY la.id";

        $hourscount = 0;
        if($hours= $DB->get_records_sql_menu($sql)){
            foreach($hours as $count){
                $hourscount += $count;
            }
        }

        return $hourscount;
    }

    /**
     * Индекс по собственной рознице
     *
     * @param int $datestart
     * @param int $dateend
     * @return bool|float|int
     */
    public function index_own_auditory($datestart=0, $dateend=0){
        if(!$trainedcount = $this->trained_count('own', $datestart, $dateend)){
            return 0;
        }

        if($hourscount = $this->count_hours(null, 'own', $datestart, $dateend)){
            return $trainedcount/$hourscount;
        }

        return false;
    }

    /**
     * Индекс по партнерской рознице
     *
     * @param int $datestart
     * @param int $dateend
     * @return bool|float|int
     */
    public function index_partner_auditory($datestart=0, $dateend=0){
        if(!$trainedcount = $this->trained_count('partner', $datestart, $dateend)){
            return 0;
        }

        if($hourscount = $this->count_hours(null, 'partner', $datestart, $dateend)){
            return $trainedcount/$hourscount;
        }

        return false;
    }

    /**
     * Итоговый индекс
     *
     * @param int $start
     * @param int $end
     * @return float
     */
    public function index_study($start=0, $end=0){
        $oaindex = $this->index_own_auditory($start, $end);
        $paindex = $this->index_partner_auditory($start, $end);
        $pcoef = $this->coef_programs();

        $index = $oaindex + $paindex + $pcoef;
        if(!$oaindex || !$paindex || !$pcoef){
            $index = $index / 3;
        }

        return round($index, 2);
    }

    /**
     * Итоговый индекс в динамике по месяцам, неделям и т.п
     *
     * @param string $mode
     * @param int $datestart
     * @param int $dateend
     * @return array
     * @throws dml_missing_record_exception
     */
    public function index_study_dynamic($periods){
        $indexes = array();
        foreach($periods as $period){
            $indexes[] = $this->index_study($period->start, $period->end);
        }

        return $indexes;
    }

    /**
     * Коэффициент по программам обучения
     *
     * @return float
     */
    public function coef_programs(){
        return $this->count_my_programs()/100;
    }

    /**
     * Вычисляем index_quality
     *
     * @return object
     */
    public function index_quality(){
        global $DB;

        $result = (object) array('totalpassedcount'=>0, 'totalfeedbackcount'=>0, 'totalaveragescore'=>0,
                                 'feedbackcoef'=>0, 'scorecoef'=>0, 'totalcoef'=>0,
                                 'passedcount'=>array(), 'feedbackcount'=>array(), 'averagescores'=>array());

        $averagescoressum = $averagescorescount = array();

        $sql = "SELECT lar.*, lp.courseid, la.startdate
                      FROM {lm_activity_request} lar
                      JOIN {lm_activity} la ON la.id=lar.activityid AND la.trainerid={$this->id}
                      JOIN {lm_program} lp ON lp.id=la.programid
                      WHERE lar.passed > 0
                      ORDER BY lar.activityid";

        if($requests = $DB->get_records_sql($sql)){
            $result->totalpassedcount = count($requests);

            foreach($requests as $request){
                $staffer = lm_staffer::i($request->partnerid, $request->userid);

                // Увеличиваем счетчик "Кол-во прошеших тренинг"
                if(!isset($result->passedcount[$request->startdate])){
                    $result->passedcount[$request->startdate] = 0;
                }
                $result->passedcount[$request->startdate] ++;

                if($complete = $staffer->feedback($request->courseid)){
                    // Увеличиваем счетчик "Кол-во фидбэков"
                    if(!isset($result->feedbackcount[$request->startdate])){
                        $result->feedbackcount[$request->startdate] = 0;
                    }
                    $result->feedbackcount[$request->startdate] ++;

                    $result->totalfeedbackcount ++;



                    // ВЫЧИСЛЯЕМ СРЕДНЮЮ ОЦЕНКУ

                    // Находим все вопросы, которые выглядят как бальная шкала (напр. от 1 до 5)
                    $sql = "SELECT id, id as item
                                   FROM {feedback_item}
                                   WHERE feedback={$complete->feedbackid} AND typ='multichoice'
                                         AND presentation LIKE '%1\n|2\n|3\n|4\n|5%'";

                    if($questions = $DB->get_records_sql_menu($sql)){
                        // Смотрим ответы сотрудника на такие вопросы
                        $questions = implode(',', $questions);
                        $select = "completed={$complete->completedid} AND item IN({$questions})";
                        if($answers = $DB->get_records_select_menu('feedback_value', $select, array(), '', 'id, value')){
                            $count = $sum = 0;

                            foreach($answers as $score){
                                // Если ответ является число, то плюсуем его, чтобы потом вычислить среднее
                                if($score && is_numeric($score)) {
                                    $sum += $score;
                                    $count++;
                                }
                            }

                            // Вычисляем среднюю оценку, которую дал сотрудник и суммируем ее к другим средним
                            if(!isset($averagescoressum[$request->startdate])){
                                $averagescoressum[$request->startdate] = 0;
                                $averagescorescount[$request->startdate] = 0;
                            }
                            $averagescoressum[$request->startdate] = $averagescoressum[$request->startdate]+ round($sum/$count, 2);
                            $averagescorescount[$request->startdate] ++;
                        }
                    }

                    // Финальный этап - вычисление средних оценок
                    if($averagescorescount){
                        $scoressum = $scorescount = 0;
                        foreach($averagescorescount as $time=>$count){
                            $result->averagescores[$time] = round($averagescoressum[$time]/$count, 2);

                            $scoressum += $result->averagescores[$time];
                            $scorescount ++;
                        }

                        $result->totalaveragescore = round($scoressum/$scorescount, 2);
                    }


                    // ВЫЧИСЛЯЕМ КОЭФФИЦИЕНТЫ
                    if($result->totalpassedcount)
                        $result->feedbackcoef = round($result->totalfeedbackcount / $result->totalpassedcount, 2);

                    $result->scorecoef = round($result->totalaveragescore / 10, 2);

                    $result->totalcoef = round($result->feedbackcoef + $result->scorecoef, 2);
                }
            }

            ksort($result->passedcount);
            ksort($result->feedbackcount);
            ksort($result->averagescores);
        }

        return $result;
    }

    public function index_sale($start=0, $end=0){

        $total = $this->total_sales(0, $start, $end);
        $my = $this->my_sales($start, $end);

        $result = new StdClass();
        $result->factsales = $my->factsales;
        $result->average = 0;
        if(isset($total->trainercount) && $total->trainercount){
            $result->average = round($total->factsales/$total->trainercount, 2);
        }

        $result->variance = 0;
        if($my->factsales) {
            $result->variance = $my->factsales - $result->average;
        }
        $result->index = round($result->variance / 100, 3);

        return $result;
    }

    public function index_sale_dynamic($periods){
        $indexes = array();
        foreach($periods as $period){
            $sales = $this->index_sale($period->start, $period->end);
            $indexes[] = $sales->index;
        }

        return $indexes;
    }

    public function my_sales($start=0, $end=0){
        return self::total_sales($this->id, $start, $end);
    }

    public static function total_sales($userid=0, $start=0, $end=0){
        global $DB;

        if($start = self::timestamp2array($start)){
            $start = make_period($start['year'], $start['mon'], $start['decade']);
        }
        if($end = self::timestamp2array($end)){
            $end = make_period($end['year'], $end['mon'], $end['decade']);
        }

        $where = "lpl.trainerid != 0";
        $where .= $userid ? " AND lpl.trainerid={$userid}": "";

        $where .= $where && $start ? " AND ": "";
        $where .= $start ? "ls.period >= {$start}": "";
        $where .= $where && $end ? " AND ": "";
        $where .= $end ? "ls.period <= {$end}": "";

        $where = !$where ? "1": $where;

        $sql = "SELECT SUM(ls.factsales) as factsales, SUM(ls.returns) as returns
                      FROM {lm_stat} ls
                      JOIN {lm_place} lpl ON ls.ttid=lpl.id
                      WHERE {$where}";

        if(!$sales = $DB->get_record_sql($sql)){
            $sales = new StdClass();
            $sales->factsales = $sales->returns = 0;
        }

        if(!$userid){
            $sql = "SELECT COUNT(DISTINCT(lpl.trainerid))
                      FROM {lm_stat} ls
                      JOIN {lm_place} lpl ON ls.ttid=lpl.id
                      WHERE {$where}";

            $sales->trainercount = $DB->count_records_sql($sql);
        }

        return $sales;
    }

    public static function timestamp2array($ts){
        if($ts && is_int($ts)){
            $ts = getdate($ts);
            if($ts['mday'] <= 10){
                $ts['decade'] = 1;
            }else if($ts['mday'] > 10 && $ts['mday'] <= 20){
                $ts['decade'] = 2;
            }else{
                $ts['decade'] = 3;
            }
        }

        return $ts;
    }


    public static function define_periods($mode='month', $datestart=0, $dateend=0){
        $tstypes = array('week'=>60*60*24*7, 'decade'=>60*60*24*10, 'month'=>60*60*24*30);

        if(!$dateend){
            $dateend = time();
        }

        if(!$datestart){
            $datestart = $dateend - $tstypes[$mode]*12;
        }


        if($mode == 'decade') {
            $date = getdate($datestart);
            $day = floor($date['mday'] / 10) * 10;
            if (!$day) $day = 1;
            $datestart = mktime($date['hours'], $date['minutes'], $date['seconds'], $date['mon'], $day, $date['year']);
            $dateend = $datestart + $tstypes[$mode] * 12;
        }

        $timespan = $dateend - $datestart;
        $timespancount = $timespan/$tstypes[$mode];
        if($timespancount > 48 && ($mode == 'week' || $mode == 'decade')){
            $mode = 'month';
            $dateend = $datestart + $tstypes[$mode]*12;
            $timespan = $dateend - $datestart;
            $timespancount = $timespan/$tstypes[$mode];
        }

        if($timespancount > 24 && $mode == 'month'){
            $datestart = $dateend - $tstypes[$mode] * 24;
        }


        $timespan = $dateend - $datestart;
        $timespancount = round($timespan/$tstypes[$mode]);

        $start = $datestart;
        $end = $start;

        $period = array();
        for($i=1; $i < $timespancount+1; $i++){
            $end = $end + $tstypes[$mode];



            // В режиме декад нужно дотягивать до последнего числа месяца, напр. если в месяце 31 день,
            // а в $end мы получили 28, то изменяем $end на 31
            if($mode == 'decade'){
                $d = getdate($end - $tstypes[$mode]);
                // Если это 3-я декада, то $end будет последним числом месяца
                if(ceil($d['mday'] / 10) == 3){
                    $end = mktime($d['hours'], $d['minutes'], $d['seconds'], $d['mon']+1, 1, $d['year']);
                }
                $start = $end - $tstypes[$mode];
            }


            $label = '';
            if($mode == 'week') {
                $label = date('M y', $end - $tstypes[$mode]);
                $label .= ' (' . date("W", $end - $tstypes[$mode]) . '-я неделя)';
            }else if($mode == 'decade'){
                $label = date('M y', $end - $tstypes[$mode]);
                $date = self::timestamp2array($end - $tstypes[$mode]);
                $label .= ' (' . $date['decade'] . '-я декада)';
            }else if($mode == 'month'){
                $label = date('M Y', $end-$tstypes[$mode]);
            }

            $period[] = (object) array('name'=>$label,
                              'start'=>$start,
                              'end'=>$end);
        }

        return $period;
    }

    protected function get_date_where($where = '', $datestart=0, $dateend=0, $alias='la'){
        $wheresql = array();
        if($datestart) {
            $wheresql[] = "{$alias}.startdate > $datestart";
        }

        if($dateend) {
            $wheresql[] = "{$alias}.startdate < $dateend";
        }

        foreach($wheresql as $cond){
            if($where)
                $where .= ' AND ';

            $where .= $cond;
        }

        return $where;
    }
}