<?php
require_once("$CFG->libdir/excellib.class.php");

class lm_exel_export{
    public static $i = NULL;

    /**
     * @var MoodleExcelWorkbook
     */
    protected $workbook = NULL;
    protected $format = NULL;


    public static function i(){
        if(self::$i == NULL){
            self::$i = new lm_exel_export();
        }

        return self::$i;
    }

    private function __construct(){
        global $CFG;

        error_reporting($CFG->debug);
    }

    protected function new_workbook(){
        // Creating a workbook
        $this->workbook = new MoodleExcelWorkbook('-');

        //creating the needed formats
        $this->format = new stdClass();
        $this->format->head1 = $this->workbook->add_format(array(
            'bold'=>1,
            'size'=>12));

        $this->format->head2 = $this->workbook->add_format(array(
            'align'=>'left',
            'bold'=>1,
            'bottum'=>2));

        $this->format->head2_center = $this->workbook->add_format(array(
            'align'=>'center',
            'v_align'=>'center',
            'bold'=>1,
            'text_wrap'=>true));

        $this->format->default = $this->workbook->add_format(array(
            'align'=>'left',
            'v_align'=>'top'));

        $this->format->center = $this->workbook->add_format(array(
            'align'=>'center',
            'v_align'=>'center'));


        $this->format->bold = $this->workbook->add_format(array(
            'align'=>'left',
            'bold'=>1,
            'v_align'=>'top'));

        $this->format->italic = $this->workbook->add_format(array(
            'align'=>'center',
            'italic'=>1,
            'v_align'=>'center'));

        $this->format->procentbold = $this->workbook->add_format(array(
            'align'=>'center',
            'v_align'=>'center',
            'bold'=>1,
            'num_format'=>'#,##0.00%'));

        $this->format->procent = $this->workbook->add_format(array(
            'align'=>'center',
            'v_align'=>'center',
            'num_format'=>'#,##0.00%'));

        $this->format->italicprocent = $this->workbook->add_format(array(
            'align'=>'center',
            'v_align'=>'center',
            'italic' => 1,
            'num_format'=>'#,##0.00%'));
    }


    /**
     * Выгружает список партнеров в Excel
     *
     * @param $q
     */
    public function partners($q){
        $this->new_workbook();

        $filename = "Отчет по партнерам.xls";
        $this->workbook->send($filename);

        // Creating the worksheets
        $worksheet1 = $this->workbook->add_worksheet('Отчет по партнерам');
        $worksheet1->hide_gridlines();
        $worksheet1->set_column(0, 0, 10);
        $worksheet1->set_column(1, 1, 30);
        $worksheet1->set_column(2, 20, 15);

        //writing the table header
        $row_offset = 1;
        $worksheet1->write_string($row_offset, 0, userdate(time()), $this->format->head1);
        $row_offset ++;

        $worksheet1->write_string($row_offset, 0, '№', $this->format->head1);
        $worksheet1->write_string($row_offset, 1, 'Компания', $this->format->head1);
        $worksheet1->write_string($row_offset, 2, 'Филиал', $this->format->head1);
        $worksheet1->write_string($row_offset, 3, 'Город', $this->format->head1);
        $worksheet1->write_string($row_offset, 4, 'Контактное лицо', $this->format->head1);
        $worksheet1->write_string($row_offset, 5, 'Телефон', $this->format->head1);
        $worksheet1->write_string($row_offset, 6, get_string('responsibleperson', 'block_manage'), $this->format->head1);
        $worksheet1->write_string($row_offset, 7, 'Кол-во сотрудников в системе', $this->format->head1);
        $worksheet1->write_string($row_offset, 8, 'Кол-во программ обучения', $this->format->head1);
        $worksheet1->write_string($row_offset, 9, '% Обученных', $this->format->head1);
        $row_offset++;

        if($partners = get_partners($q)){
            $n = 1;
            foreach($partners as $partner){
                $opartner = lm_partner::i($partner);

                //$resp_person = $opartner->get_resp();

                $worksheet1->write_string($row_offset, 0, $n, $this->format->default);
                $worksheet1->write_string($row_offset, 1, $opartner->company_name(), $this->format->default);
                $worksheet1->write_string($row_offset, 2, $opartner->name, $this->format->default);
                $worksheet1->write_string($row_offset, 3, $opartner->get_region_name(), $this->format->default);
                $worksheet1->write_string($row_offset, 4, $opartner, $this->format->default);
                //$worksheet1->write_string($row_offset, 5, $resp_person->phone2, $this->format->default);
                //$worksheet1->write_string($row_offset, 6, $resp_person->lastname.' '.$resp_person->firstname, $this->format->default);
                $worksheet1->write_string($row_offset, 7, $opartner->count_staffers(), $this->format->default);
                $worksheet1->write_string($row_offset, 8, $opartner->count_appointed_programs(), $this->format->default);
                $worksheet1->write_string($row_offset, 9, $opartner->trained_percent(), $this->format->default);

                $n++;
                $row_offset++;
            }
        }

        $this->workbook->close();
        exit;
    }

    /**
     * Выгружает список активностей в Excel
     *
     * @param string $type
     * @param string $state
     * @param string $q
     * @param int $startdate
     * @param int $enddate
     */
    public function activities($type="", $state="", $q="", $startdate=0, $enddate=0){
        $this->new_workbook();

        $filename = "Отчет по активностям.xls";
        $this->workbook->send($filename);

        // Creating the worksheets
        $worksheet1 = $this->workbook->add_worksheet('Отчет по активностям');
        $worksheet1->hide_gridlines();
        $worksheet1->set_column(0, 0, 10);
        $worksheet1->set_column(1, 1, 30);
        $worksheet1->set_column(2, 20, 15);

        //writing the table header
        $row_offset = 1;
        $worksheet1->write_string($row_offset, 0, userdate(time()), $this->format->head1);
        $row_offset ++;

        $worksheet1->write_string($row_offset, 0, '№', $this->format->head1);
        $worksheet1->write_string($row_offset, 1, 'Компания', $this->format->head1);
        $worksheet1->write_string($row_offset, 2, 'Филиал', $this->format->head1);
        $worksheet1->write_string($row_offset, 3, 'Город', $this->format->head1);
        $worksheet1->write_string($row_offset, 4, 'Место проведения', $this->format->head1);
        $worksheet1->write_string($row_offset, 5, 'Дата начала', $this->format->head1);
        $worksheet1->write_string($row_offset, 6, 'Дата завершения', $this->format->head1);
        $worksheet1->write_string($row_offset, 7, 'Тренер', $this->format->head1);
        $worksheet1->write_string($row_offset, 8, 'Программа', $this->format->head1);
        $worksheet1->write_string($row_offset, 9, 'Часы', $this->format->head1);
        $worksheet1->write_string($row_offset, 10, 'Кол-во заявок', $this->format->head1);
        $worksheet1->write_string($row_offset, 11, 'Кол-во обученных', $this->format->head1);

        $row_offset++;

        if($activities = get_activities($type, $state, $q, $startdate, $enddate)){
            $n = 1;
            foreach($activities as $activity){
                $oactivity = lm_activity::i($activity);
                $place = lm_place::i($activity->placeid);
                $company = '';
                $partner = '';
                if($place->partnerid){
                    $opartner = lm_partner::i($place->partnerid);
                    $partner = $opartner->name();
                    $company = $opartner->company_name();
                }

                $worksheet1->write_string($row_offset, 0, $n, $this->format->default);
                $worksheet1->write_string($row_offset, 1, $company, $this->format->default);
                $worksheet1->write_string($row_offset, 2, $partner, $this->format->default);
                $worksheet1->write_string($row_offset, 3, $place->city_name(), $this->format->default);
                $worksheet1->write_string($row_offset, 4, $place->name(), $this->format->default);
                $worksheet1->write_string($row_offset, 5, $oactivity->date_start(), $this->format->default);
                $worksheet1->write_string($row_offset, 6, $oactivity->date_end(), $this->format->default);
                $worksheet1->write_string($row_offset, 7, $oactivity->trainer_fullname(), $this->format->default);
                $worksheet1->write_string($row_offset, 8, $activity->name, $this->format->default);
                $worksheet1->write_string($row_offset, 9, $oactivity->count_hours(), $this->format->default);
                $worksheet1->write_string($row_offset, 10, $oactivity->count_members(), $this->format->default);
                $worksheet1->write_string($row_offset, 11, $oactivity->count_trained_members(), $this->format->default);

                $n++;
                $row_offset++;
            }
        }

        $this->workbook->close();
        exit;
    }

    public function report_partner($filter, $datefrom=0, $dateto=0){
        global $DB, $CFG;

        $partnerid = $filter['partner'];

        $this->new_workbook();

        $filename = "Отчет по партнеру.xls";
        $this->workbook->send($filename);

        // Creating the worksheets
        $worksheet1 = $this->workbook->add_worksheet('Отчет по партнеру');
        $worksheet1->hide_gridlines();
        $worksheet1->set_column(0, 0, 5);
        $worksheet1->set_column(1, 1, 25);
        $worksheet1->set_column(2, 2, 10);
        $worksheet1->set_column(3, 20, 20);

        //writing the table header
        $row_offset = 1;

        $worksheet1->write_string($row_offset, 0, $this->get_period_str($datefrom, $dateto), $this->format->head2);
        $row_offset ++;

        $n = 0;
        $worksheet1->set_row($row_offset, 50);
        $worksheet1->set_row($row_offset+1, 40);
        $worksheet1->write_string($row_offset, $n++, '№', $this->format->head2_center);
        $worksheet1->write_string($row_offset, $n++, 'ФИО', $this->format->head2_center);
        $worksheet1->write_string($row_offset, $n++, 'Ссылка на профиль', $this->format->head2_center);

        $partner = lm_partner::i($partnerid);
        if($programs = $partner->get_appointed_programs()){
            foreach($programs as $program){
                if($program->courseid) {
                    $link = $CFG->wwwroot . '/course/view.php?id=' . $program->courseid;
                    $worksheet1->write_url($row_offset + 1, $n, $link, $this->format->default);
                }
                $worksheet1->write_string($row_offset, $n++, $program->name, $this->format->head2_center);
            }
        }
        $row_offset += 2;

        if($staffers = $partner->get_staffers()){
            $row_num = 1;
            foreach($staffers as $staffer){
                $n = 0;
                $link = $CFG->wwwroot.'/user/view.php?id='.$staffer->id;
                $worksheet1->write_string($row_offset, $n++, $row_num, $this->format->default);
                $worksheet1->write_string($row_offset, $n++, $staffer->lastname.' '.$staffer->firstname, $this->format->default);
                $worksheet1->write_url($row_offset, $n++, $link);

                $sql = "SELECT lp.courseid, lar.passed
                            FROM {lm_activity_request} lar
                            JOIN {lm_activity} la ON la.id=lar.activityid
                            JOIN {lm_program} lp ON lp.id=la.programid
                            WHERE lar.userid={$staffer->id}";

                $marks = $DB->get_records_sql_menu($sql);

                foreach($programs as $program){
                    if(isset($marks[$program->courseid]) && $marks[$program->courseid] > 0) {
                        $worksheet1->write_string($row_offset, $n++, 'Прошел', $this->format->center);
                    }else if($marks[$program->courseid] = 0){
                        $worksheet1->write_string($row_offset, $n++, 'В процессе', $this->format->center);
                    }else{
                        $worksheet1->write_string($row_offset, $n++, '-', $this->format->center);

                    }
                }

                $row_offset ++;
                $row_num++;
            }
        }

        $this->workbook->close();
        exit;
    }

    public function report_tm()
    {
        global $DB;

        $start = microtime();

        $this->new_workbook();

        $filename = "Отчет по ТМ.xls";
        $this->workbook->send($filename);

        // Creating the worksheets
        $worksheet1 = $this->workbook->add_worksheet('Отчет по ТМ');
        $worksheet1->hide_gridlines();
        $worksheet1->set_column(0, 0, 40);
        $worksheet1->set_column(1, 30, 10);


        $row_offset = 1;

        $n = 0;
        $worksheet1->set_row($row_offset, 100);
        $worksheet1->write_string($row_offset, $n++, 'ФИО', $this->format->head2_center);

        $programslist = lm_report::i()->tm_get_programlist();
        $tms = lm_report::i()->tm_get_data_from(array('lm_exel_export', 'get_report_tm_count_data'));


        if($tms) {
            $n=1;
            foreach($programslist['list'] as $pid=>$fullname){
                $worksheet1->write_string($row_offset, $n++, $fullname, $this->format->head2_center);
            }
            $row_offset ++;

            foreach ($tms as $tm) {
                $n=0;
                // Записываем ФИО ТМ
                $worksheet1->write_string($row_offset, $n++, $tm['user']->fullname, $this->format->bold);

                // И его результаты по программам (процент обученности сотрудников)
                foreach($tm['programs'] as $programid=>$percent){
                    $worksheet1->write_string($row_offset, $n++, $percent.'%', $this->format->procentbold);
                }
                $row_offset++;

                // Генерируем более развернутую информацию по партнерам и сотрудникам
                //$partners = $DB->get_records_menu('lm_partner', array('respid'=>$tm['user']->id), '', 'id, id as partnerid');
                $sql = "SELECT lp.id, lp.id as partnerid
                      FROM {lm_partner} lp
                      JOIN {lm_place} lpl ON lpl.partnerid=lp.id
                      JOIN {lm_company} lc ON lc.id=lp.companyid
                      WHERE /*lc.type='own' AND*/ lpl.tmid={$tm['user']->id}";
                $partners = $DB->get_records_sql_menu($sql);

                if($partners) {
                    foreach ($partners as $partnerid) {
                        $n = 0;

                        // Записываем наименование партнера
                        $partner = lm_partner::i($partnerid);
                        $worksheet1->write_string($row_offset, $n++, $partner->fullname(), $this->format->italic);

                        // Теперь записываем в линию по порядку процент обученности сотрудников по программам
                        foreach ($tm['programs'] as $programid => $percent) {
                            // Если этому партнеру назначена программа
                            if (isset($programslist['appointed'][$partnerid][$programid])) {
                                $trainedpercent = $partner->trained_percent($programid);
                                $worksheet1->write_string($row_offset, $n++, $trainedpercent . '%', $this->format->italicprocent);
                            } else {
                                $worksheet1->write_string($row_offset, $n++, ' - ', $this->format->center);
                            }
                        }
                        $row_offset++;

                        if ($staffers = $partner->get_staffers()) {
                            foreach ($staffers as $staffer) {
                                $n = 0;

                                // Записываем ФИО сотрудника
                                $staffername = $staffer->lastname . ' ' . $staffer->firstname;
                                $worksheet1->write_string($row_offset, $n++, $staffername, $this->format->default);

                                // Теперь записываем в линию по порядку проценты его обученности по программам
                                foreach ($tm['programs'] as $programid => $percent) {
                                    // Если программа назначена партнеру, у которого пользователь является сотрудником
                                    if (isset($programslist['appointed'][$partnerid][$programid])) {
                                        $progress = $partner->staffer_progress($staffer->id, $programid);
                                        $worksheet1->write_string($row_offset, $n++, $progress . '%', $this->format->procent);
                                    } else {
                                        $worksheet1->write_string($row_offset, $n++, ' - ', $this->format->center);
                                    }
                                }
                                $row_offset++;
                            }
                        }
                    }
                }
            }
        }

        $delta = microtime_diff($start, microtime());
        $worksheet1->write_string($row_offset+5, 0, 'Сгенерировано за: '.$delta.' сек', $this->format->default);

        $this->workbook->close();
        exit;
    }

    public function get_report_tm_count_data($user, $programs, $tm, $n){
        $tm['partners'][$user->partnerid] = $user->partnerid;

        if ($programs) {
            foreach ($programs['list'] as $pid=>$fullname) {
                // Если партнеру назначена эта программа
                if(isset($programs['appointed'][$user->partnerid][$pid])) {
                    $percent = round($tm['sum'][$pid] / $tm['count'][$pid], 2);
                    $tm['programs'][$pid] = $percent;
                }else{
                    $tm['programs'][$pid] = 0;
                }
            }
        }

        return $tm;
    }

    /**
     * Выгрузка по тренеру
     *
     * @param $filter
     * @param $datefrom
     * @param $dateto
     */
    public function report_trainer($filter, $datefrom, $dateto){
        global $DB, $CFG;

        $trainerid = $filter['trainer'];
        $trainer = block_manage_trainer::i($trainerid);

        $this->new_workbook();

        $filename = "Отчет по тренеру.xls";
        $this->workbook->send($filename);

        // Creating the worksheets
        $worksheet1 = $this->workbook->add_worksheet('Отчет по тренеру');
        $worksheet1->hide_gridlines();
        $worksheet1->set_column(0, 0, 5);
        $worksheet1->set_column(1, 1, 15);
        $worksheet1->set_column(2, 2, 25);
        $worksheet1->set_column(3, 3, 8);
        $worksheet1->set_column(4, 4, 25);
        $worksheet1->set_column(5, 5, 8);
        $worksheet1->set_column(6, 20, 20);

        //writing the table header
        $row_offset = 1;
        $worksheet1->write_string($row_offset, 2, $trainer->fullname(), $this->format->head1);
        $worksheet1->write_string($row_offset++, 4, 'Общее кол-во часов проведенного обучения', $this->format->head2);

        if($types = lm_activity::types()){
            foreach($types as $type=>$name){
                $worksheet1->write_string($row_offset, 4, $name, $this->format->default);
                $hours = lm_activity::float2hours($trainer->count_hours($type, null, $datefrom, $dateto));
                $worksheet1->write_string($row_offset++, 5, $hours, $this->format->default);
            }
        }


        $row_offset += 2;
        $worksheet1->write_string($row_offset, 0, $this->get_period_str($datefrom, $dateto), $this->format->head2);
        $row_offset ++;

        $n = 0;
        $worksheet1->set_row($row_offset, 50);
        $worksheet1->set_row($row_offset+1, 40);
        $worksheet1->write_string($row_offset, $n++, '№', $this->format->head2_center);
        $worksheet1->write_string($row_offset, $n++, 'Регион', $this->format->head2_center);
        $worksheet1->write_string($row_offset, $n++, 'Партнер', $this->format->head2_center);
        $worksheet1->write_string($row_offset, $n++, 'Ссылка на партнера', $this->format->head2_center);
        $worksheet1->write_string($row_offset, $n++, 'ФИО', $this->format->head2_center);
        $worksheet1->write_string($row_offset, $n++, 'Ссылка на профиль', $this->format->head2_center);


        if($programs = $trainer->get_my_programs()){
            foreach($programs as $program){
                if($program->courseid) {
                    $link = $CFG->wwwroot . '/course/view.php?id=' . $program->courseid;
                    $worksheet1->write_url($row_offset + 1, $n, $link, $this->format->default);
                }
                $worksheet1->write_string($row_offset, $n++, $program->name, $this->format->head2_center);
            }
        }
        $row_offset += 2;

        if($listeners = $trainer->get_listeners($datefrom, $dateto)){
            $row_num = 1;

            foreach($listeners as $listener){
                $n = 0;
                $worksheet1->write_string($row_offset, $n++, $row_num, $this->format->default);

                $partner = lm_partner::i($listener->partnerid);
                $worksheet1->write_string($row_offset, $n++, $partner->get_region_name(), $this->format->default);

                $worksheet1->write_string($row_offset, $n++, $partner->fullname(), $this->format->default);
                $link = $CFG->wwwroot.'/blocks/manage/?_p=partners&id='.$partner->id;
                $worksheet1->write_url($row_offset, $n++, $link, $this->format->default);

                $worksheet1->write_string($row_offset, $n++, $listener->lastname.' '.$listener->firstname, $this->format->default);
                $link = $CFG->wwwroot.'/user/view.php?id='.$listener->id;
                $worksheet1->write_url($row_offset, $n++, $link);

                $where = $this->get_date_where("lar.userid={$listener->id}", $datefrom, $dateto);

                $sql = "SELECT lp.id, lar.passed
                            FROM {lm_activity_request} lar
                            JOIN {lm_activity} la ON la.id=lar.activityid
                            JOIN {lm_program} lp ON lp.id=la.programid
                            WHERE $where";

                $marks = $DB->get_records_sql_menu($sql);

                foreach($programs as $program){
                    if(isset($marks[$program->id]) && $marks[$program->id] > 0) {
                        $worksheet1->write_string($row_offset, $n++, 'Прошел', $this->format->center);
                    }else if($marks[$program->id] = 0){
                        $worksheet1->write_string($row_offset, $n++, 'В процессе', $this->format->center);
                    }else{
                        $worksheet1->write_string($row_offset, $n++, '-', $this->format->center);

                    }
                }

                $row_offset ++;
                $row_num++;
            }
        }

        $this->workbook->close();
        exit;
    }

    /**
     * Выгрузка по сотруднику
     *
     * @param $partnerid
     * @param $userid
     */
    public function report_staffer($partnerid, $userid){
        global $CFG;

        $staffer = lm_staffer::i($partnerid, $userid);
        $partner = lm_partner::i($staffer->partnerid);

        $passedprograms = $staffer->passed_programs_stat();

        $this->new_workbook();

        $filename = "Отчет по сотруднику.xls";
        $this->workbook->send($filename);

        // Creating the worksheets
        $worksheet1 = $this->workbook->add_worksheet('Отчет по тренеру');
        $worksheet1->hide_gridlines();
        $worksheet1->set_column(0, 0, 5);
        $worksheet1->set_column(1, 1, 30);
        $worksheet1->set_column(2, 2, 10);
        $worksheet1->set_column(3, 3, 25);
        $worksheet1->set_column(4, 4, 15);
        $worksheet1->set_column(5, 5, 15);



        //writing the table header
        $row_offset = 1;
        $worksheet1->write_string($row_offset, 2, $staffer->fullname(), $this->format->head1);
        $url = $CFG->wwwroot.'/user/view.php?id='.$staffer->userid;
        $worksheet1->write_url($row_offset+1, 2, $url, $this->format->default);
        $worksheet1->write_string($row_offset++, 4, 'Общее кол-во часов проведенного обучения', $this->format->head2);

        if($types = lm_activity::types()){
            foreach($types as $type=>$name){
                $worksheet1->write_string($row_offset, 4, $name, $this->format->default);
                $hours = lm_activity::float2hours($passedprograms[$type]);
                $worksheet1->write_string($row_offset++, 5, $hours, $this->format->default);
            }
        }


        $row_offset += 2;


        $n = 0;
        $head_row_offset = $row_offset;
        $worksheet1->set_row($row_offset, 50);

        $worksheet1->write_string($row_offset, $n++, '№', $this->format->head2_center);
        $worksheet1->write_string($row_offset, $n++, 'Прошел курсы', $this->format->head2_center);
        $worksheet1->write_string($row_offset, $n++, 'Ссылка на курс', $this->format->head2_center);
        $worksheet1->write_string($row_offset, $n++, 'Дата обучения', $this->format->head2_center);
        $worksheet1->write_string($row_offset, $n++, 'Время', $this->format->head2_center);
        $worksheet1->write_string($row_offset, $n++, 'Вид обучения', $this->format->head2_center);
        $worksheet1->write_string($row_offset, $n++, 'Оставил ли обратную связь?', $this->format->head2_center);
        $worksheet1->write_string($row_offset, $n++, 'Курс назначил', $this->format->head2_center);
        $worksheet1->write_string($row_offset, $n++, 'На активность записал', $this->format->head2_center);

        $row_offset ++;

        if($programs = $staffer->passed_programs()){
            $i = 1;
            $res_start_from = 0;

            foreach($programs as $program){
                $n = 0;
                $oactivity = lm_activity::i($program->activityid);

                $programname = $oactivity->fullname();


                $worksheet1->write_string($row_offset, $n++, $i, $this->format->default);
                $worksheet1->write_string($row_offset, $n++, $programname, $this->format->default);
                if($oactivity->courseid) {
                    $url = $CFG->wwwroot . '/course/view.php?id=' . $oactivity->courseid;
                    $worksheet1->write_url($row_offset, $n++, $url, $this->format->default);
                }else{
                    $n++;
                }
                $worksheet1->write_string($row_offset, $n++, userdate($program->passed, '%d.%m.%Y'), $this->format->center);
                $worksheet1->write_string($row_offset, $n++, $oactivity->count_hours(), $this->format->center);
                $worksheet1->write_string($row_offset, $n++, $oactivity->get_type(), $this->format->center);

                $feedback = "";
                if($oactivity->courseid) {
                    $feedback = $staffer->feedback_plain($oactivity->courseid);
                    $worksheet1->set_column($n, $n, 20);
                }
                $worksheet1->write_string($row_offset, $n++, $feedback, $this->format->center);

                $requestedby = $staffer->who_requested_activity($program->activityid);
                $worksheet1->set_column($n, $n, 30);
                $fullname = isset($requestedby->id) ? fullname($requestedby): '';
                $worksheet1->write_string($row_offset, $n++, $fullname, $this->format->center);

                $assignedby = $partner->who_assigned_program($oactivity->programid);
                $worksheet1->set_column($n, $n, 30);
                $fullname = isset($assignedby->id) ? fullname($assignedby): '';
                $worksheet1->write_string($row_offset, $n++, $fullname, $this->format->center);

                if($oactivity->courseid && $grades = $staffer->last_attempt_grades($oactivity->courseid)){
                    if(!$res_start_from){
                        $res_start_from = $n;
                    }

                    foreach($grades as $grade){
                        $worksheet1->set_column($res_start_from, $res_start_from, 15);
                        $name = 'Результат по тесту "'.$grade->name.'"';
                        $worksheet1->write_string($head_row_offset, $res_start_from, $name, $this->format->head2_center);

                        if($grade->grade){
                            $worksheet1->write_string($row_offset, $res_start_from++, $grade->grade.'%', $this->format->procent);
                        }else{
                            $worksheet1->write_string($row_offset, $res_start_from++, ' - ', $this->format->center);
                        }
                    }
                }

                $row_offset++;
                $i++;
            }


        }

        $this->workbook->close();
        exit;
    }

    protected function get_date_where($where = '', $datestart=0, $dateend=0){
        $wheresql = array();
        if($datestart) {
            $wheresql[] = "startdate > $datestart";
        }

        if($dateend) {
            $wheresql[] = "enddate < $dateend";
        }

        foreach($wheresql as $cond){
            if($where)
                $where .= ' AND ';

            $where .= $cond;
        }

        return $where;
    }

    protected function get_period_str($datefrom, $dateto){
        if(!$datefrom && !$dateto){
            $str = "Период: за все время";
        }else {
            if ($datefrom) {
                $datefrom = userdate($datefrom, '%d.%m.%Y');
            } else {
                $datefrom = 'начала';
            }

            if ($dateto) {
                $dateto = userdate($dateto, '%d.%m.%Y');
            } else {
                $dateto = 'сегодня';
            }
            $str = "Период: с $datefrom по $dateto";
        }

        return $str;
    }
}