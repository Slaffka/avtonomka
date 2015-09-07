<?php
class lm_report{
    private static $i = NULL;
    public $filter = 0;
    public $type = '';
    public $datefrom = '';
    public $dateto = '';
    public $regions = 0;

    public static function i(){
        if(self::$i === NULL){
            self::$i = new lm_report();
        }

        return self::$i;
    }

    private function __construct(){
        global $USER;

        $this->type = optional_param('type', '', PARAM_TEXT);
        if(!$this->type){
            if( has_capability('block/manage:viewreportbycourse', context_system::instance()) ) {
                $this->type = 'program';
            }else if(has_capability('block/manage:viewreportbypartner', context_system::instance()) || lm_user::is_rep() ) {
                $this->type = 'partner';
            }else if( has_capability('block/manage:viewreportbytrainer', context_system::instance()) ) {
                $this->type = 'trainer';
            }
        }

        $this->filter = optional_param('filter', array(), PARAM_RAW);

        switch($this->type){
            case 'partner':
                // У представителя партнера есть возможность просматривать
                // отчеты только по своим компаниям
                if( lm_user::is_rep() && empty($this->filter['partner']) && $partnerid = get_my_company_id()){
                    $this->filter['partner'] = $partnerid;
                }
                break;
            case 'trainer':
                if( lm_user::is_trainer() && !lm_user::is_admin()){
                    $this->filter['trainer'] = $USER->id;
                }
                break;
            case 'tm':
                if(lm_user::is_responsible() && !lm_user::is_admin()){
                    $this->filter['tm'] = $USER->id;
                }

                break;
        }


        $this->datefrom = optional_param('datefrom', '', PARAM_TEXT);
        $this->dateto = optional_param('dateto', '', PARAM_TEXT);
        $this->regions = optional_param('regions', 0, PARAM_SEQUENCE);

        $regions = get_my_regions();
        if(!$this->regions && $regions && $regions != 'all'){
            $this->regions = $regions;
        }

        if(!is_array($this->filter)){
            $this->filter = array();
        }
    }


    // ОТЧЕТ ПО ПРОГРАММЕ
    public function program_count($type='passed'){
        global $DB;

        $where = array();
        if($type == 'passed'){
            $where[] = 'passed > 0';
        }else if($type == 'inprocess'){
            $where[] = 'passed=0';
        }else{
            $where[] = 'passed < 0';
        }

        $where = $this->construct_where($where);

        $sql = "SELECT COUNT(lar.id)
                      FROM (
                          SELECT lar.id
                          FROM {lm_activity_request} lar
                              JOIN {lm_activity} la ON lar.activityid=la.id
                              JOIN {lm_partner} lp ON lar.partnerid=lp.id
                              JOIN {lm_partner_staff} lps ON lps.userid=lar.userid AND lps.partnerid=lar.partnerid
                              WHERE $where AND archive=0
                              GROUP BY lar.userid, lar.partnerid, la.programid
                      ) lar";

        return $DB->count_records_sql($sql);
    }


    // ОТЧЕТ ПО ПАРТНЕРУ
    public function count_by_partner()
    {
        global $DB;

        /*$partnerid = 0;
        if(isset($this->filter['partner'])){
            $partnerid = $this->filter['partner'];
        }*/

        //$params = array();
        $join = "";
        $where = $this->construct_where('', array('programid'=>'lpsp', 'partnerid'=>'lpsp', 'startdate'=>false));
        if($where == '1'){
            $where = "";
        }else{
            $where = " AND ".$where;
            if(strpos($where, 'region') !== false){
                $join = " JOIN {lm_partner} lp ON lps.partnerid=lp.id";
            }
        }


        $result = new StdClass();

        $sql = "SELECT COUNT(lpsp.id)
                    FROM {lm_partner_staff_progress} lpsp
                    JOIN {lm_partner_staff} lps ON lpsp.userid=lps.userid AND lpsp.partnerid=lps.partnerid
                    {$join}
                    WHERE programid > 0 AND progress = 100 AND archive=0 $where";

        $result->passedcount = $DB->count_records_sql($sql);

        $sql = "SELECT COUNT(lpsp.id)
                    FROM {lm_partner_staff_progress} lpsp
                    JOIN {lm_partner_staff} lps ON lpsp.userid=lps.userid AND lpsp.partnerid=lps.partnerid
                    {$join}
                    WHERE programid > 0 AND progress = 0 AND archive=0 $where";

        $result->nottrainedcount = $DB->count_records_sql($sql);


        return $result;


        /*$sql = "SELECT lpa.*, lpr.name as fullname
                      FROM {lm_partner} lp
                      JOIN {lm_partner_program} lpa ON lp.id=lpa.partnerid
                      JOIN {lm_program} lpr ON lpa.programid=lpr.id
                      WHERE $where";


        $passeduserscount = $nottraineduserscount = 0;
        if($appointedprograms = $DB->get_records_sql($sql, $params)) {

            $params = array();
            $where = $this->construct_where('');

            if($where == '1'){
                $where = '';
            }else if($where){
                $where .= ' AND ';
            }

            $where .= 'passed > 0';

            $join = '';
            if($partnerid) {
                $join = "JOIN {lm_partner_program} lpp ON la.programid=lpp.programid AND lpp.partnerid={$partnerid}";
            }


            // GROUP BY используется для предотвращения ошибки в отчете, в случе если в таблице lm_partner_staff есть несколько записей с одинаковыми
            // partnerid и userid (это ошибка!) или если сотрудник несколько раз проходил обучение по программе
            // И все это помещено в подзапрос SELECT, т.к. невозможно вычислить COUNT с GROUP BY
            $sql = "SELECT COUNT(lar.id)
                      FROM (
                            SELECT lar.id FROM {lm_activity_request} lar
                                  JOIN {lm_partner} lp ON lar.partnerid=lp.id
                                  JOIN {lm_partner_staff} lps ON lar.userid=lps.userid AND lar.partnerid=lps.partnerid AND lps.archive=0
                                  JOIN {lm_activity} la ON la.id=lar.activityid
                                  $join
                                  WHERE $where
                                  GROUP BY lar.userid, lar.partnerid, la.programid
                      ) lar";

            $passeduserscount = $DB->count_records_sql($sql, $params);
            foreach ($appointedprograms as $program) {

                $sql = "SELECT  COUNT(lps.userid)
                       FROM (SELECT userid
                                 FROM {lm_partner_staff}
                                 WHERE partnerid= ? AND archive=0
                             ) lps
                       LEFT JOIN(
                           SELECT lar.id, lar.userid, la.programid, la.startdate, la.enddate
                                FROM {lm_activity_request} lar
                                JOIN {lm_activity} la ON la.id=lar.activityid
                                WHERE programid= ? AND lar.passed > 0
                       ) lar ON lar.userid=lps.userid
                       WHERE programid IS NULL";

                $nottraineduserscount = $nottraineduserscount + $DB->count_records_sql($sql, array($program->partnerid, $program->programid));
            }
        }

        $result = new StdClass();
        $result->passedcount = $passeduserscount;
        $result->nottrainedcount = $nottraineduserscount;

        return $result;*/
    }



    // ОТЧЕТ ПО ТМ
    public function get_report_tm_count_data($user, $programs, $percents, $n){
        if ($programs) {
            foreach ($programs['list'] as $pid=>$fullname) {
                // Если партнеру назначена эта программа
                if(isset($programs['appointed'][$user->partnerid][$pid])) {
                    $percent = round($percents['sum'][$pid] / $percents['count'][$pid], 2);
                    $percents['programs'][$pid] = $percent;
                }else{
                    $percents['programs'][$pid] = 0;
                }
            }
        }

        return $percents;
    }

    public function get_report_tm_count(){

        if(!has_capability('block/manage:viewreportbypartner', context_system::instance()) ){
            return 'Вы не имеете доступа для просмотра этого отчета!';
        }

        $result = new StdClass();
        $result->programslist = $this->tm_get_programlist();
        $result->programslist = $result->programslist['list'];
        $result->percents = $this->tm_get_data_from(array('lm_report', 'get_report_tm_count_data'));

        return $result;
    }

    public function tm_get_data_from($func){

        $data = array();
        $programs = $this->tm_get_programlist();

        if($partners = $this->tm_get_partnerlist()) {

            $tminfo = array();
            foreach ($partners as $partner) {
                $lmpartner = lm_partner::i($partner);

                $uid = $partner->userid;

                if ($programs['list']) {
                    $staffers = $lmpartner->get_staffers();

                    foreach ($programs['list'] as $pid=>$fullname) {
                        if(!isset($tminfo[$uid]['sum'][$pid])){
                            $tminfo[$uid]['sum'][$pid] = 0;
                            $tminfo[$uid]['count'][$pid] = 0;
                            $tminfo[$uid]['user'] = (object) array('id'=>$partner->userid, 'fullname'=>$partner->lastname.' '.$partner->firstname, 'partnerid'=>$partner->id);
                        }

                        if($staffers){
                            foreach($staffers as $staffer){
                                $tminfo[$uid]['sum'][$pid] = $tminfo[$uid]['sum'][$pid] + $lmpartner->staffer_progress($staffer->id, $pid);
                                $tminfo[$uid]['count'][$pid] ++;
                            }
                        }
                    }
                }
            }

            if($tminfo) {
                $n = $i = 1;
                foreach($tminfo as $uid=>$info) {
                    $data[$uid] = call_user_func($func, $info['user'], $programs, $info, $n);
                    $n++;
                }
            }
        }

        return $data;
    }

    public function tm_construct_where($where=array(), $shorts=array()){
        $tmid = isset($this->filter['tm']) ? $this->filter['tm']: 0;

        if($tmid){
            $where[] = 'lpl.tmid='.$tmid;
        }

        return $this->construct_where($where, array('programid'=>'lpa', 'partnerid'=>'lp.id'));
    }

    public function tm_get_partnerlist(){
        global $DB;

        $where = $this->tm_construct_where(array("lpl.tmid != 0"));

        $sql = "SELECT lp.*, lc.type, u.firstname, u.lastname, u.id as userid
                      FROM {lm_partner} lp
                      JOIN {lm_place} lpl ON lpl.partnerid=lp.id AND lpl.type='tt'
                      JOIN {lm_company} lc ON lc.id=lp.companyid /*AND lc.type='own'*/
                      JOIN {user} u ON lpl.tmid=u.id
                      WHERE $where
                      GROUP BY lp.id, lpl.tmid
                      ORDER BY u.lastname";

        return $DB->get_records_sql($sql);
    }


    public function tm_get_programlist(){
        global $CFG, $DB;

        $where = $this->tm_construct_where();

        $sql = "SELECT lpa.id, lp.id as partnerid, lpa.programid, lpr.courseid, lpr.name
                      FROM {lm_partner} lp
                      JOIN {lm_place} lpl ON lpl.partnerid=lp.id
                      JOIN {lm_partner_program} lpa ON lp.id=lpa.partnerid
                      JOIN {lm_program} lpr ON lpa.programid=lpr.id
                      WHERE $where
                      ORDER BY lpa.programid";

        $result = array('appointed'=>array(), 'list'=>array(), 'links'=>array());
        if($programs = $DB->get_records_sql($sql)){
            foreach($programs as $program){
                $id = $program->programid;
                $result['appointed'][$program->partnerid][$id] = $program->name;
                if(!isset($result['list'][$program->programid])) {
                    // Если программа с привязкой к курсу
                    if($program->courseid) {
                        $result['links'][$id] = '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $program->courseid . '" target="_blank">' . $program->name . '</a>';
                    }else{
                        $result['links'][$id] = $program->name;
                    }
                }

                $result['list'][$id] = $program->name;
            }
            ksort($result['list']);
        }
        unset($programs);

        return $result;
    }



    // ОТЧЕТ ПО ТРЕНЕРУ
    public function count_by_trainer(){
        global $DB;

        $where = lm_report::i()->construct_where('', array('regionid'=>'lpa'));

        $sql = "SELECT la.*, lp.name
                      FROM {lm_activity} la
                      JOIN {lm_activity_request} lar ON lar.activityid=la.id
                      JOIN {lm_partner} lpa ON lpa.id=lar.partnerid
                      JOIN {lm_program} lp ON la.programid=lp.id
                      WHERE $where";

        $activities = $DB->get_records_sql($sql);
        $result = array();
        foreach($activities as $activity){
            $activityobj = lm_activity::i($activity);

            if(!isset($result[$activity->programid])){
                $result[$activity->programid] = new StdClass();
                $result[$activity->programid]->count = 0;
                $result[$activity->programid]->name = $activity->name;
            }

            $result[$activity->programid]->count = $result[$activity->programid]->count + $activityobj->count_trained_members();
        }

        return $result;
    }


    public function construct_where($where, $shorts=array()){
        if(!isset($shorts['regionid']))
            $shorts['regionid'] = 'lp';

        if(!isset($shorts['trainer']))
            $shorts['trainer'] = 'la';

        if(!isset($shorts['programid']))
            $shorts['programid'] = 'la';

        if(!isset($shorts['partnerid']))
            $shorts['partnerid'] = 'lar';

        if(!isset($shorts['startdate']))
            $shorts['startdate'] = 'la';

        if(!isset($shorts['startdate']))
            $shorts['startdate'] = 'la';


        if($this->regions && $shorts['regionid']){
            $where[] = $shorts['regionid'].'.regionid IN ('.$this->regions.')';
        }

        if($this->filter){
            foreach($this->filter as $name=>$value){
                if($name == 'program' && $value && $shorts['programid']){
                    $where[] = $shorts['programid'].'.programid='.$value ;
                }

                if($name == 'partner' && $value && $shorts['partnerid']){
                    if(strpos($shorts['partnerid'], '.')){
                        $where[] = $shorts['partnerid'].'='.$value;
                    }else{
                        $where[] = $shorts['partnerid'].'.partnerid='.$value;
                    }
                }

                if($name == 'trainer' && $value && $shorts['trainer']){
                    $where[] = $shorts['trainer'].'.trainerid='.$value;
                }
            }
        }

        if($shorts['startdate']) {
            $datefrom = strtotime($this->datefrom);
            if ($datefrom) {
                $where[] = $shorts['startdate'] . '.startdate > '.$datefrom;
            }
        }

        if($shorts['startdate']) {
            $dateto = strtotime($this->dateto);
            if ($dateto) {
                $where[] = $shorts['startdate'] . '.startdate < '.$dateto;
            }
        }

        if(!$where){
            $where = '1';
        }else{
            $where = implode(' AND ', $where);
        }

        return $where;
    }
}