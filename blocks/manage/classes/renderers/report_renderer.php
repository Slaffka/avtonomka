<?php
error_reporting(E_ALL);
class block_manage_report_renderer extends block_manage_renderer {
    public $pageurl = '/blocks/manage/?_p=report';
    public $pagename = 'Отчеты';
    public $type = 'manage_report';
    public $subtype = 'program';

    /**
     * @var lm_report
     */
    public $report = NULL;


    public function __construct(moodle_page $page, $target){
        parent::__construct($page, $target);

        $this->report = lm_report::i();
    }

    public function init_page(){
        parent::init_page();
        $this->page->requires->js('/blocks/manage/yui/base.js');
        $this->page->requires->js('/blocks/manage/yui/reports.js');
        $this->page->requires->js('/blocks/manage/yui/multiselect/src/jquery.multiselect.min.js');
        $this->page->requires->css('/blocks/manage/yui/multiselect/jquery.multiselect.css');

    }

    public function main_content(){
        $isrep = lm_user::is_rep();

        $out = '<ul class="nav nav-pills reporttype">';

        $type = explode('_', $this->report->type);
        $subtype = isset($type[1]) ? $type[1]: '';

        if( has_capability('block/manage:viewreportbycourse', context_system::instance()) ) {
            $out .= '<li class="' . ($type[0] == 'program' ? 'active' : '') . '" data-type="program"><a href="/blocks/manage/?_p=report">По программе</a></li>';
        }

        if(has_capability('block/manage:viewreportbypartner', context_system::instance())
            || $isrep
        ) {
            $out .= '<li class="' . ($type[0] == 'partner' ? 'active' : '') . '" data-type="partner"><a href="/blocks/manage/?_p=report&type=partner">По партнеру</a></li>';
        }

        if( has_capability('block/manage:viewreportbypartner', context_system::instance()) ) {
            $out .= '<li class="' . ($type[0] == 'tm' ? 'active' : '') . '" data-type="tm"><a href="/blocks/manage/?_p=report&type=tm">По ТМ</a></li>';
        }

        if( has_capability('block/manage:viewreportbytrainer', context_system::instance()) ) {
            $out .= '<li class="' . ($type[0] == 'trainer' ? 'active' : '') . '" data-type="'.$this->report->type.'"><a href="/blocks/manage/?_p=report&type=trainer">По тренеру</a></li>';
        }

        if( has_capability('block/manage:viewreportbystaffer', context_system::instance()) || $isrep) {
            $out .= '<li class="' . ($type[0] == 'staff' ? 'active' : '') . '" data-type="'.$this->report->type.'"><a href="/blocks/manage/?_p=report&type=staff">По сотруднику</a></li>';
        }

        $out .= '</ul>';

        if($this->report->type != 'program' && !$subtype) {
            echo '<a class="btn btn-link pull-right btn-report-xlexport"><i class="icon icon-download-alt"></i> Выгрузить в excel</a>';
        }

        if($this->report->type != 'tm'/* && $this->report->type != 'staffer'*/) {
            $out .= '<div class="controls controls-row pull-right" style="margin:0 15px">
                    <div class="form-inline pull-right">
                        <label for="">Начиная с даты</label>
                        <input id="filter-startdate" class="calendar-trigger input-mini" type="text" value="' . $this->report->datefrom . '">
                        <label for="">до</label>
                        <input id="filter-enddate" class="calendar-trigger input-mini" type="text" value="' . $this->report->dateto . '">
                    </div>
                </div>';
        }

        // Представителю партнера и в режиме отчета по сотруднику выбор регионов не нужен!
        //if(!$isrep && $this->report->type != 'staffer'){
            $out .= '<div class="pull-left">'.
                html_writer::select(get_regions_list(), 'regions', explode(',', $this->report->regions), null, array('class'=>'', 'multiple'=>'multiple'))
                .'</div>';
        //}

        $out .= '<div class="clearer"></div><div id="calendar" class="hide"></div><hr>';

        $report = '';


        switch($type[0]){
            case 'program':
                $report = $this->get_report_program();
                break;
            case 'partner':
                $report = $this->get_report_partner();
                break;

            case 'trainer':
                $report = $this->get_report_trainer($subtype);
                break;

            case 'tm':
                $report = $this->get_report_tm();
                break;

            case 'staff':
                $report = $this->get_report_staff($subtype);
                break;

        }

        $out .= '<script src="http://code.highcharts.com/highcharts.js"></script>';
        $out .= '<div class="report-wrapper">'.$report.'</div>';

        return $out;
    }

    /**
     * Отчет по программе
     *
     * @return string
     * @throws Exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_report_program(){
        global $DB, $CFG;

        // Контактное лицо компании не имеет доступа к этому отчету
        if(!has_capability('block/manage:viewreportbycourse', context_system::instance())){
            return 'Вы не имеете доступа для просмотра этого отчета!';
        }

        $table = new html_table();
        $table->id = 'memberslist';
        $table->attributes['class'] = 'generaltable pull-left';
        $table->head = array('ФИО', 'Партнер', 'Регион', 'Результат');

        $where = array();

        $where = lm_report::i()->construct_where($where);


        $sql = "SELECT COUNT(lar.id)
                      FROM {lm_activity_request} lar
                      JOIN {lm_activity} la ON la.id=lar.activityid
                      JOIN {lm_partner} lp ON lar.partnerid=lp.id
                      JOIN {user} u ON lar.userid=u.id
                      WHERE $where";
        $count = $DB->count_records_sql($sql);


        $sql = "SELECT lar.*, u.firstname, u.lastname
                      FROM {lm_activity_request} lar
                      JOIN {lm_activity} la ON la.id=lar.activityid
                      JOIN {lm_partner} lp ON lar.partnerid=lp.id
                      JOIN {user} u ON lar.userid=u.id
                      WHERE $where
                      LIMIT 0, 100";

        if($members = $DB->get_records_sql($sql) ){
            foreach($members as $member){
                $partner = lm_partner::i($member->partnerid);

                if($member->passed > 0){
                    $member->passed = userdate($member->passed, '%d.%m.%Y');
                }else if($member->passed < 0){
                    $member->passed = 'Не прошел';
                }else{
                    $member->passed = 'В процессе...';
                }

                $link = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$member->userid.'" target="_blank">'
                               .$member->lastname.' '.$member->firstname.
                        '</a>';
                $cells = array(
                    new html_table_cell($link),
                    new html_table_cell($partner->link()),
                    new html_table_cell($partner->get_region_name()),
                    new html_table_cell($member->passed)
                );
                $table->data[] = new html_table_row($cells);
            }
        }

        $programid = 0;
        if(isset($this->report->filter['program'])){
            $programid = $this->report->filter['program'];
        }

        $out = '<div>'.
                   html_writer::select(get_programs_list(), 'program', $programid, 'Выберите программу...', array('class'=>'filtermenu')).
               '</div>';
        $out .= '<div id="report-program-results" class="chart"></div>';
        $out .= html_writer::table($table);
        if($count > count($members)){
            $count = $count-count($members);
            $out .= "<div style=\"text-align:center;clear:both;font-weight:bold\">И еще {$count} записей...</div>";
        }
        return $out;
    }


    /**
     * Отчет по партнеру
     *
     * @return string
     * @throws Exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_report_partner(){
        global $DB, $CFG;

        $isrep = lm_user::is_rep();

        $partners = get_partners_menu();

        $partnerid = 0;
        $partner = NULL;
        if(!empty($this->report->filter['partner'])){
            $partnerid = $this->report->filter['partner'];
            $partner = lm_partner::i($partnerid);
        }

        // Этот отчет имеют право просматривать: контактное лицо компании, либо у кого есть право viewreportbypartner
        if(!has_capability('block/manage:viewreportbypartner', context_system::instance()) && !$isrep
           || $partnerid && !isset($partners[$partnerid])){
            return 'Вы не имеете доступа для просмотра этого отчета!';
        }


        $programid = 0;
        if(isset($this->report->filter['program'])){
            $programid = $this->report->filter['program'];
        }

        $out = '<div>';


        $out .= html_writer::select($partners, 'partner', $partnerid, 'Выберите партнера...', array('class' => 'filtermenu'));

        $out .= html_writer::select(get_programs_list(), 'program', $programid, 'Выберите программу...', array('class'=>'filtermenu'));
        $out .= '</div>';

        if($partner) {
            $out .= '<div class="report-header">' . $partner->link() . '</a></div><br />';
        }

        $out .= '<div id="chart-partner-results" class="chart"></div>';

        // Таблица с сотрудниками, которые прошли обучение
        $tablepassed = new html_table();
        $tablepassed->head = array('ФИО', 'По программе', 'Партнер', 'Регион');


        // Таблица с сотрудниками, которые не проходили еще обучение
        $tablenottrained = new html_table();
        $tablenottrained->head = array('ФИО', 'По программе', 'Партнер', 'Регион');


        $where = lm_report::i()->construct_where('', array('programid'=>'lpa', 'partnerid'=>'lpa', 'startdate'=>false));

        $sql = "SELECT lpa.*, lpr.name, lpr.courseid
                      FROM {lm_partner} lp
                      JOIN {lm_partner_program} lpa ON lp.id=lpa.partnerid
                      JOIN {lm_program} lpr ON lpa.programid=lpr.id
                      WHERE $where";

        if($appointedprograms = $DB->get_records_sql($sql)) {

            $where = lm_report::i()->construct_where(''/*, array('regionid'=>false)*/);

            if($where){
                $where .= ' AND ';
            }else if($where == '1'){
                $where = '';
            }

            $where .= 'passed > 0';


            $join = '';
            if($partnerid) {
                $join = "JOIN {lm_partner_program} lpp ON la.programid=lpp.programid AND lpp.partnerid={$partnerid}";
            }


            foreach ($appointedprograms as $program) {

                $sql = "SELECT lar.id as reqid, u.id, lar.partnerid, u.firstname, u.lastname, lar.startdate, lar.enddate
                              FROM {user} u
                              JOIN (
                                    SELECT lar.id, lar.partnerid, lps.userid, la.startdate, la.enddate FROM {lm_activity_request} lar
                                          JOIN {lm_partner} lp ON lar.partnerid=lp.id
                                          JOIN {lm_partner_staff} lps ON lar.userid=lps.userid AND lar.partnerid=lps.partnerid AND lps.archive=0
                                          JOIN {lm_activity} la ON la.id=lar.activityid
                                          $join
                                          WHERE $where AND la.programid={$program->programid}
                                          GROUP BY lar.userid, lar.partnerid, la.programid
                              ) lar ON lar.userid=u.id
                              LIMIT 0, 50";

                $passedusers = $DB->get_records_sql($sql);
                if ($passedusers) {
                    foreach ($passedusers as $user) {
                        $partner = lm_partner::i($user->partnerid);

                        $linkuser = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'" target="_blank">'.$user->lastname . ' ' . $user->firstname.'</a>';
                        $linkcourse = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$program->courseid.'">'.$program->name.'</a>';

                        $cells = array(
                            new html_table_cell($linkuser),
                            new html_table_cell($linkcourse),
                            new html_table_cell($partner->link()),
                            new html_table_cell($partner->get_region_name())
                        );
                        $tablepassed->data[] = new html_table_row($cells);
                    }
                }


                $sql = "SELECT  u.id, u.firstname, u.lastname, lar.programid, lar.startdate, lar.enddate, lps.partnerid
                       FROM mdl_user u
                       JOIN (
                           SELECT userid, partnerid
                                      FROM mdl_lm_partner_staff
                                      WHERE partnerid= ? AND archive=0
                        ) lps ON lps.userid=u.id
                       LEFT JOIN(
                           SELECT lar.id, lar.userid, lar.partnerid, la.programid, la.startdate, la.enddate
                                FROM mdl_lm_activity_request lar
                                JOIN mdl_lm_activity la ON la.id=lar.activityid
                                WHERE programid= ? AND lar.passed > 0
                       ) lar ON lar.userid=lps.userid
                       WHERE programid IS NULL
                       LIMIT 0, 50";

                $nottrainedusers = $DB->get_records_sql($sql, array($program->partnerid, $program->programid));
                if ($nottrainedusers) {
                    foreach ($nottrainedusers as $user) {
                        $partner = lm_partner::i($user->partnerid);

                        $linkuser = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'" target="_blank">'.$user->lastname . ' ' . $user->firstname.'</a>';

                        $linkcourse = $program->name;
                        if($program->courseid){
                            $linkcourse = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$program->courseid.'" target="_blank">'.$program->name.'</a>';
                        }
                        $cells = array(
                            new html_table_cell($linkuser),
                            new html_table_cell($linkcourse),
                            new html_table_cell($partner->link()),
                            new html_table_cell($partner->get_region_name())
                        );
                        $tablenottrained->data[] = new html_table_row($cells);
                    }
                }

                if(count($tablepassed->data) > 50 || count($tablenottrained->data) > 50){
                    break;
                }
            }
        }

        $tabpassed = html_writer::table($tablepassed);

        $tabnottrained = html_writer::table($tablenottrained);

        $out .=
            '<ul class="nav nav-tabs">
              <li class="active"><a href="#activity-passed" data-toggle="tab">Обучены</a></li>
              <li><a href="#activity-nottrained" data-toggle="tab">Не обучены</a></li>
            </ul>

            <div class="tab-content">
              <div class="tab-pane active" id="activity-passed">'.$tabpassed.'<div class="clearer"></div></div>
              <div class="tab-pane" id="activity-nottrained">'.$tabnottrained.'<div class="clearer"></div></div>
            </div>';

        return $out;
    }


    /**
     * Отчет по ТМ
     *
     * @return string
     * @throws Exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_report_tm(){

        if(!has_capability('block/manage:viewreportbypartner', context_system::instance()) ){
            return 'Вы не имеете доступа для просмотра этого отчета!';
        }

        $tmid = isset($this->report->filter['tm']) ? $this->report->filter['tm']: 0;

        $table = new html_table();
        $table->attributes['class'] = "generaltable tm-results-table";
        $table->head = array('№', 'ФИО ТМ');

        if($programs = lm_report::i()->tm_get_programlist()){
            foreach($programs['list'] as $pid=>$fullname){

                $table->head[] = $programs['links'][$pid];
            }
        }
        $table->data = lm_report::i()->tm_get_data_from(array('block_manage_report_renderer', 'tm_get_data_table'));

        $out = '';
        if(lm_user::is_admin()) {
            $out .= '<div>';
            $out .= html_writer::select(get_tm_menu($this->report->regions), 'tm', $tmid, 'Выберите ТМ...', array('class' => 'filtermenu'));
            $out .= '</div>';
        }

        $out .= '<div id="chart-tm-results" class="chart"></div>';

        if($table->data){
            $out .= html_writer::table($table);
        }

        return $out;
    }

    public function tm_get_data_table($partner, $programs, $percents, $n){
        global $CFG;

        $profilelink = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$partner->id.'" target="_blank">'
            .$partner->fullname
            .'</a>';
        $cells = array(
            new html_table_cell($n),
            new html_table_cell($profilelink)
        );

        if ($programs['list']) {
            foreach ($programs['list'] as $pid=>$fullname) {
                // Если партнеру назначена эта программа
                if(isset($programs['appointed'][$partner->partnerid][$pid])) {
                    if ($percents['count']) {
                        $percent = round($percents['sum'][$pid] / $percents['count'][$pid], 2);
                        $cells[] = new html_table_cell($percent.'%');
                    } else {
                        $cells[] = new html_table_cell('0%');
                    }
                }else{
                    $cells[] = new html_table_cell(' - ');
                }
            }
        }

        return new html_table_row($cells);
    }


    /**
     * Отчет по тренеру
     *
     * @param string $subtype
     * @return string
     * @throws Exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_report_trainer($subtype=''){
        global $DB, $CFG;

        // Контактное лицо компании и сам тренер не имеет доступа к этому отчету
        if(!has_capability('block/manage:viewreportbytrainer', context_system::instance())){
            return 'Вы не имеете доступа для просмотра этого отчета!';
        }

        $out = '';

        if(!$subtype) {
            if (!lm_user::is_trainer() || lm_user::is_admin()) {
                $trainerid = 0;
                $trainers = get_trainers_menu();

                if (isset($this->report->filter['trainer'])) {
                    $trainerid = $this->report->filter['trainer'];
                }

                $out .= '<div>' .
                    html_writer::select($trainers, 'trainer', $trainerid, 'Выберите тренера...', array('class' => 'filtermenu')) .
                    '</div><hr>';
            }
        }

        $out .= '<div class="report-header">Перейти к просмотру индексов:</div>
                 <div class="report-indexes-nav">
                 <a href="/blocks/manage?_p=report&type=trainer_indexletsstudy" class="btn-viewindexes disabled">
                    <span class="glyphicon glyphicon-signal"></span> LetsStudy
                 </a>
                 <a href="/blocks/manage?_p=report&type=trainer_indexstudy" class="btn-viewindexes ' .($subtype=="indexstudy"? "current": ""). '">
                    <span class="fa fa-graduation-cap"></span> Обучение
                 </a>
                 <a href="/blocks/manage?_p=report&type=trainer_indexquality" class="btn-viewindexes ' .($subtype=="indexquality"? "current": ""). '">
                    <span class="glyphicon glyphicon-thumbs-up"></span> Качество
                 </a>
                 <a href="/blocks/manage?_p=report&type=trainer_indexsale" class="btn-viewindexes ' .($subtype=="indexsale"? "current": "").'">
                    <span class="fa fa-rub"></span> Продажи
                 </a>
                 <div class="clearer"></div>
                 </div>';

        switch($subtype){
            case 'indexstudy':
                $out .= $this->get_report_trainer_indexstudy();
                return $out;
                break;
            case 'indexquality':
                $out .= $this->get_report_trainer_indexquality();
                return $out;
                break;
            case 'indexsale':
                $out .= $this->get_report_trainer_indexsale();
                return $out;
                break;
        }


        $out .= '<div id="chart-trainer-results" class="chart"></div>';

        $table = new html_table();
        $table->head = array('#', 'Тренинг', 'Партнер', 'Регион', 'Дата', 'Часов', 'Обучено');

        $where = lm_report::i()->construct_where('', array('regionid'=>'lpa'));

        $sql = "SELECT la.*, lp.name, lp.courseid, lpa.id as partnerid
                      FROM {lm_activity} la
                      JOIN {lm_activity_request} lar ON lar.activityid=la.id
                      JOIN {lm_partner} lpa ON lpa.id=lar.partnerid
                      JOIN {lm_program} lp ON la.programid=lp.id
                      WHERE $where";

        $activities = $DB->get_records_sql($sql);
        $n = 1;
        foreach($activities as $activity){
            $activityobj = lm_activity::i($activity);
            $partner = lm_partner::i($activity->partnerid);

            $activityobj->count_hours();
            $link = $activity->name;
            // Если программа (активность) с привязкой к курсу, то делаем ссылку на курс
            if($activity->courseid) {
                $link = '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $activity->courseid . '" target="_blank">' . $activity->name . '</a>';
            }
            $cells = array(
                new html_table_cell($n),
                new html_table_cell($link),
                new html_table_cell($partner->link()),
                new html_table_cell($partner->get_region_name()),
                new html_table_cell(userdate($activity->startdate, '%d.%m.%Y')),
                new html_table_cell($activityobj->count_hours()),
                new html_table_cell($activityobj->count_trained_members())
            );
            $table->data[] = new html_table_row($cells);

            $n++;
        }

        $out .= html_writer::table($table);


        return $out;
    }

    public function get_report_trainer_indexstudy(){

        $datestart =  strtotime($this->report->datefrom);
        $dateend = strtotime($this->report->dateto);

        $out = '<div id="chart-indexstudy-results" class="chart"></div>';

        $table = new html_table();
        $table->attributes['class'] = "generaltable indexstudy-table";
        $table->head = array('№', 'ФИО', 'Индекс', 'Часы по СР', 'Обучено СР, чел', 'Часы по ПР', 'Обучено ПР, чел');

        if($trainers = get_trainers_menu()) {
            $n = 1;
            foreach($trainers as $trainerid=>$fullname) {
                $trainer = block_manage_trainer::i($trainerid);
                $index = $trainer->index_study();

                $hours_own = $trainer->count_hours(null, 'own', $datestart, $dateend);
                $hours_own = lm_activity::float2hours($hours_own);

                $hours_partner = $trainer->count_hours(null, 'partner', $datestart, $dateend);
                $hours_partner = lm_activity::float2hours($hours_partner);

                $cells = array(
                    new html_table_cell($n),
                    new html_table_cell($trainer->link()),
                    new html_table_cell($index),
                    new html_table_cell($hours_own),
                    new html_table_cell($trainer->trained_count('own', $datestart, $dateend)),
                    new html_table_cell($hours_partner),
                    new html_table_cell($trainer->trained_count('partner', $datestart, $dateend))
                );
                $table->data[] = new html_table_row($cells);
                $n++;
            }
        }

        if($table->data){
            $out .= html_writer::table($table);
        }

        return $out;
    }

    public function get_report_trainer_indexquality(){

        $out = '<div id="chart-indexquality-results" class="chart"></div>';

        $table = new html_table();
        $table->attributes['class'] = "generaltable indexstudy-table";
        $table->head = array('№', 'ФИО', 'Индекс', 'Обучено', 'Анкет ОС', 'Средняя оценка'/*, 'Обучено по ПР', 'Анкет ОС по ПР', 'Средняя оценка по ПР'*/);


        if($trainers = get_trainers_menu()) {
            $n = 1;
            foreach ($trainers as $trainerid => $fullname) {
                $trainer = block_manage_trainer::i($trainerid);
                $result = $trainer->index_quality();

                $cells = array(
                    new html_table_cell($n),
                    new html_table_cell($trainer->link()),
                    new html_table_cell($result->totalcoef),
                    new html_table_cell($result->totalpassedcount),
                    new html_table_cell($result->totalfeedbackcount),
                    new html_table_cell($result->totalaveragescore),
                );
                $table->data[] = new html_table_row($cells);
                $n++;

            }

            if($table->data){
                $out .= html_writer::table($table);
            }
        }
        return $out;
    }

    public function get_report_trainer_indexsale(){
        $out = '<div id="chart-indexsale-results" class="chart"></div>';

        $table = new html_table();
        $table->attributes['class'] = "generaltable indexsale-table";
        $table->head = array('№', 'ФИО', 'Индекс', 'Кол-во продаж', 'Среднее', 'Дисперсия продаж');


        if($trainers = get_trainers_menu()) {
            $n = 1;
            foreach ($trainers as $trainerid => $fullname) {
                $trainer = block_manage_trainer::i($trainerid);
                $result = $trainer->index_sale();

                $cells = array(
                    new html_table_cell($n),
                    new html_table_cell($trainer->link()),
                    new html_table_cell($result->index),
                    new html_table_cell($result->factsales),
                    new html_table_cell($result->average),
                    new html_table_cell($result->variance)
                );
                $table->data[] = new html_table_row($cells);
                $n++;

            }

            if($table->data){
                $out .= html_writer::table($table);
            }
        }
        return $out;
    }


    /**
     * Отчет по сотруднику
     *
     * @return string
     * @throws Exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_report_staff($subtype=''){


        $isrep = lm_user::is_rep();
        if(!has_capability('block/manage:viewreportbystaffer', context_system::instance()) && !$isrep ){
            return 'Вы не имеете доступа для просмотра этого отчета!';
        }

        $this->tpl->subtype = $subtype;

        $out = $this->fetch('report/index.tpl');

        if($subtype) {

            switch($subtype){
                case 'study':
                    $out .= $this->get_report_staff_study();
                    break;
                case 'rotation':
                    $out .= $this->get_report_staff_rotation();
                    break;
                case 'lifecycle':
                    $out .= $this->get_report_staff_lifecycle();
                    break;
                case 'performance':
                    $out .= $this->get_report_staff_performance();
                    break;
            }
        }

        return $out;
    }


    public function get_report_staff_study(){
        global $CFG;

        $partnerid = isset($this->report->filter['partner']) ? $this->report->filter['partner']: 0;
        $userid = isset($this->report->filter['user']) ? $this->report->filter['user']: 0;
        $partner = lm_partner::i($partnerid);

        $isrep = lm_user::is_rep();
        if($isrep){
            // Контактное лицо может просматривать отчеты только по своим партнерам
            if($partnerid && !isset($partners[$partnerid])){
                return 'Вы не имеете доступа для просмотра этого отчета!';
            }
        }

        $partners = get_partners_menu();

        $out = html_writer::select($partners, 'partner', $partnerid, 'Выберите партнера...', array('class' => 'filtermenu'));
        $members = $partner->get_staffers_menu();
        $out .= html_writer::select($members, 'user', $userid, 'Выберите сотрудника...', array('class' => 'filtermenu'));

        if(!$partnerid){
            return $out.'<h3 class="chart-placeholder">Выберите партнера для просмотра отчета...</h3>';
        }

        if(!$userid || !$partner->is_staffer($userid)){
            return $out.'<h3 class="chart-placeholder">Выберите сотрудника для просмотра отчета...</h3>';
        }

        $staffer = lm_staffer::i($partnerid, $userid);

        $out .= '<div class="report-header">'.$staffer->link().' - '.$partner->link().'</a><br />';
        $out .= 'Пройдено из назначенных курсов: '.$partner->staffer_progress($userid).'%</div>';

        $table = new html_table();
        $table->attributes['class'] = "generaltable staffer-results-table";
        $table->head = array('№', 'Прошел курсы', 'Дата обучения', 'Время', 'Вид обучения', 'Оставил ли <br> обратную связь?', 'Подробнее');


        if($programs = $staffer->passed_programs()){
            $n = 1;
            foreach($programs as $program){
                $oactivity = lm_activity::i($program->activityid);

                $courselink = $oactivity->fullname();
                $feedbacklink = '';
                if(!$oactivity->programid) {
                    $courselink = '<div>В активности не указана программа!</div>';
                    $feedbacklink = '!';
                }
                if($oactivity->courseid) {
                    $courselink = '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $oactivity->courseid . '" target="_blank">' . $oactivity->fullname() . '</a>';
                    $feedbacklink = $staffer->feedback_link($oactivity->courseid);
                }
                if(!$courselink && $oactivity->programid){
                    $courselink = 'Возможно программа была удалена!';
                }

                $courselink .= '<div><a class="btn btn-link btn-mini btn-gotoactivity" href="' . $CFG->wwwroot . '/blocks/manage/?_p=activities&id=' . $oactivity->id . '" target="_blank">
                               <i class="icon icon-share"></i>Посмотреть активность</a></div>';

                $infobtn = '<button data-target="#stafferinfo-modal" data-activityid="' . $oactivity->id . '" class="btn btn-link btn-small btn-result-details" data-toggle="modal">Подробнее</button>';


                $cells = array(
                    new html_table_cell($n),
                    new html_table_cell($courselink),
                    new html_table_cell(userdate($program->passed, '%d.%m.%Y')),
                    new html_table_cell($oactivity->count_hours()),
                    new html_table_cell($oactivity->get_type()),
                    new html_table_cell($feedbacklink),
                    new html_table_cell($infobtn),

                );
                $table->data[] = new html_table_row($cells);

                $n++;

            }
        }

        $out .= '<div id="stafferinfo-modal" class="modal fade" tabindex="-1" aria-hidden="true">
                  <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3 id="myModalLabel">Информация</h3>
                  </div>
                  <div class="modal-body">
                  <div class="alert alert-error hide">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    <div class="content"></div>
                  </div>

                  </div>
                  <div class="modal-footer">
                    <button class="btn" data-dismiss="modal" aria-hidden="true">OK</button>
                  </div>
                </div>';

        $out .= '<div id="chart-staffer-results" class="chart"></div>';

        if($table->data){
            $out .= html_writer::table($table);
        }

        return $out;
    }

    public function get_report_staff_rotation(){
        $out = "";
        $positions = lm_post::post_menu();
        $positionid = $this->report->filter['position'];
        $out .= html_writer::select($positions, 'position', $positionid, 'По всем должностям', array('class' => 'filtermenu'));

        return $out;
    }

    public function get_report_staff_lifecycle(){
        $out = "";
        return $out;
    }

    public function get_report_staff_performance(){
        $out = "";
        return $out;
    }

}