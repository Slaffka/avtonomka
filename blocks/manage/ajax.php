<?php

define('AJAX_SCRIPT', true);

require_once('../../config.php');
require_once('lib.php');

$method = optional_param('ajc', '', PARAM_TEXT);
$au = new AjaxUtil();
$au->$method();


class AjaxUtil{
    public function update_field(){
        global $DB;

        $partnerid = optional_param('partnerid', 0, PARAM_INT);
        $field = optional_param('field', '', PARAM_TEXT);
        $value = optional_param('value', '', PARAM_RAW);

        $value = str_replace('<div>', "\n", $value);
        $value = str_replace('</div>', '', $value);
        $value = strip_tags($value);

        switch($field){
            case 'name':
            case 'type':
            case 'comment':
            case 'companyid':
            case 'regionid':
                $dataobj = new StdClass();
                $dataobj->id = $partnerid;
                $dataobj->$field = $value;
                $DB->update_record('lm_partner', $dataobj);
                break;



            case 'cohortid':
                lm_partner::i($partnerid)->assign_cohort($value);
                break;
        }
    }



    public function appoint_program(){

        $a = new StdClass();
        $a->partnerid = optional_param('partnerid', 0, PARAM_INT);
        $a->programid = optional_param('programid', 0, PARAM_INT);

        if($a->partnerid && $a->programid){
            $partner = lm_partner::i($a->partnerid);
            $a->id = $partner->appoint_program($a->programid);
            $a->period = $partner->get_program_period($a->programid);
        }else{
            $a->id = 0;
        }

        echo json_encode($a);
    }

    public function disappoint_program(){
        $partnerid = optional_param('partnerid', 0, PARAM_INT);
        $appointedid = optional_param('appointedid', 0, PARAM_INT);

        $answer = new StdClass();
        $answer->success = lm_partner::i($partnerid)->disappoint_program($appointedid);

        echo json_encode($answer);
    }

    public function search_partner(){
        global $OUTPUT, $PAGE;

        /**
         * @var block_manage_partners_renderer $renderer
         */
        $renderer = $PAGE->get_renderer('block_manage', 'partners');
        $resetpage = (boolean) optional_param('resetpage', false, PARAM_BOOL);

        if(!$resetpage){
            $renderer->pagenum = optional_param('page', 0, PARAM_INT);
        }

        ob_start();
        echo $OUTPUT->header();
        ob_end_clean();

        $q = optional_param('q', '', PARAM_TEXT);

        echo $renderer->partner_table($q);
    }

    public function search_activity(){
        global $OUTPUT, $PAGE;

        /**
         * @var block_manage_activities_renderer $renderer
         */
        $renderer = $PAGE->get_renderer('block_manage', 'activities');
        $type = optional_param('type', '', PARAM_TEXT);
        $state = optional_param('state', '', PARAM_TEXT);
        $q = optional_param('q', '', PARAM_TEXT);
        $startdate = optional_param('startdate', '', PARAM_TEXT);
        $enddate = optional_param('enddate', '', PARAM_TEXT);

        $startdate = strtotime($startdate);
        $enddate = strtotime($enddate);

        $resetpage = (boolean) optional_param('resetpage', true, PARAM_BOOL);
        if(!$resetpage){
            $renderer->pagenum = optional_param('page', 0, PARAM_INT);
        }

        // Без этой примочки не хочет выводить таблицу
        ob_start();
        echo $OUTPUT->header();
        ob_end_clean();

        echo $renderer->activity_table($q, $type, $state, $startdate, $enddate);
    }

    public function search_users(){
        global $DB;
        $q = optional_param('term', '', PARAM_TEXT);
        $a = array();

        $users = $DB->get_records_select(
            'user', "(firstname LIKE ? OR lastname LIKE ?) AND deleted <> 1",
            array("$q%", "$q%"), '', 'id, firstname, lastname'
        );

        if($users){
            foreach($users as $user){
                $out = new StdClass();
                $out->id = $user->id;
                $out->label = $user->lastname.' '.$user->firstname;
                $out->value = $out->label;

                $a[] = $out;
            }
        }

        echo json_encode($a);
    }

    public function search_courses(){
        global $DB;
        $q = optional_param('q', '', PARAM_TEXT);
        $a = array();

        if($courses = $DB->get_records_select('course', "fullname LIKE ?", array("$q%"), '', 'id, fullname')){
            foreach($courses as $course){
                $a[] = $course;
            }
        }

        echo json_encode($a);
    }



    public function remove_partner(){
        if($partnerid = optional_param('partnerid', 0, PARAM_INT)){
            lm_partner::i($partnerid)->remove();
        }
    }



    public function delete_program(){
        global $DB;

        $programid = optional_param('programid', 0, PARAM_INT);

        $a = new StdClass();
        $a->success = false;

        //TODO: Проверка прав доступа
        if($DB->delete_records('lm_program', array('id'=>$programid)) ){
            $DB->delete_records('lm_partner_program', array('programid'=>$programid));
            //TODO: Чистить activity
            $a->success = true;
        }

        echo json_encode($a);
    }

    public function get_activity_info(){
        global $PAGE, $DB, $USER;

        /**
         * @var block_manage_activities_renderer $renderer
         */
        $renderer = $PAGE->get_renderer('block_manage', 'activities');
        $activityid = optional_param('activityid', 0, PARAM_INT);
        $type = optional_param('type', 'auditory', PARAM_RAW);

        if(!$activityid){
            $dataobj = new StdClass();
            $dataobj->trainerid = $USER->id;
            $dataobj->type = $type;
            $dataobj->auditory = 'partner';
            $activityid = $DB->insert_record('lm_activity', $dataobj);
        }

        $a = new StdClass();
        $a->html = $renderer->get_activity_info($activityid);
        $a->activityid = $activityid;
        echo json_encode($a);
    }

    public function remove_activity(){
        if($activityid = optional_param('activityid', 0, PARAM_INT)){
            lm_activity::i($activityid)->remove();
        }
    }

    public function appoint_trainer(){
        global $DB;
        $activityid = optional_param('activityid', 0, PARAM_INT);
        $userid = optional_param('userid', 0, PARAM_INT);

        if($activityid && $userid){
            $dataobj = new StdClass();
            $dataobj->id = $activityid;
            $dataobj->trainerid = $userid;

            $DB->update_record('lm_activity', $dataobj);
        }
    }

    public function update_activity_field(){
        global $DB;

        $activityid = optional_param('activityid', 0, PARAM_INT);
        $field = optional_param('field', '', PARAM_TEXT);
        $value = optional_param('value', '', PARAM_RAW);

        $value = str_replace('<div>', "\n", $value);
        $value = str_replace('</div>', '', $value);
        $value = strip_tags($value);

        $fields = array('activitytype'=>'type', 'partnertype'=>'auditory');
        if(isset($fields[$field])){
            $field = $fields[$field];
        }

        switch($field){
            case 'comment':
            case 'comment2':
            case 'type':
            case 'auditory':
            case 'maxmembers':
            case 'programid':
            case 'placeid':
                $dataobj = new StdClass();
                $dataobj->id = $activityid;
                $dataobj->$field = $value;
                $DB->update_record('lm_activity', $dataobj);

                break;
        }
    }

    public function set_activity_date(){
        $activityid = optional_param('activityid', 0, PARAM_INT);
        $datefrom = optional_param('datefrom', 0, PARAM_INT);
        $dateto = optional_param('dateto', 0, PARAM_INT);
        $dateid = optional_param('dateid', 0, PARAM_INT);

        $a = new StdClass();
        $a->success = false;

        if(!$activityid || !$datefrom || !$dateto){
            echo json_encode($a);
            die();
        }

        $activity = lm_activity::i($activityid);
        $activity->set_date($datefrom, $dateto, $dateid);
        $a->dateid = $activity->count_dates();
        $a->success = true;

        echo json_encode($a);
    }

    public function remove_activity_date(){
        $activityid = optional_param('activityid', 0, PARAM_INT);
        $dateid = optional_param('dateid', 0, PARAM_INT);

        lm_activity::i($activityid)->remove_date($dateid);
    }

    public function create_user(){
        global $PAGE;

        $partnerid = optional_param('partnerid', 0, PARAM_INT);
        $ttid = optional_param('ttid', 0, PARAM_INT);
        $type = optional_param('type', '', PARAM_TEXT);
        $feedbacktype = optional_param('feedbacktype', '', PARAM_TEXT);
        $issendemail = optional_param('issendemail', true, PARAM_BOOL);

        //TODO: Проверка прав!
        $staffer = new StdClass();
        $partner = lm_partner::i($partnerid);
        $result = false;

        if($type == 'newuser'){
            $staffer->firstname = optional_param('firstname', '', PARAM_TEXT);
            $staffer->lastname = optional_param('lastname', '', PARAM_TEXT);
            $staffer->password = optional_param('password', '', PARAM_TEXT);
            $staffer->email = $staffer->username = optional_param('email', '', PARAM_TEXT);
            $result = $staffer->userid = $partner->create_staffer($staffer, $issendemail);
        }else if($type == 'existsuser'){
            $staffer->userid = optional_param('userid', 0, PARAM_INT);
            $result = $partner->add_staffer($staffer->userid, $issendemail);
        }



        $a = new StdClass();
        $a->success = false;
        $a->html = "";

        if($result == 'already_exists') {
            $a->html = "Такой пользователь уже существует!";
        }else if($result == 'already_partners_staffer'){
            $a->html = "Этот пользователь уже был добавлен партнеру ранее!";
        }elseif($staffer->userid){
            $partner->relocate_staffer($staffer->userid, 0, $ttid);

            if($feedbacktype == 'full') {
                /**
                 * @var block_manage_partners_renderer $renderer
                 */
                $renderer = $PAGE->get_renderer('block_manage', 'partners');
                $a->html = $renderer->get_staffer_view($staffer->userid, false, $partner->has_capability_edit());
            }else if($feedbacktype == 'optionitem'){
                $fullname = lm_staffer::i($partnerid, $staffer->userid)->fullname();
                $a->html = '<option value="'.$staffer->userid.'">'.$fullname.'</option>';
            }

            $a->id = $staffer->userid;
            $a->success = true;
        }

        echo json_encode($a);
    }

    public function action_user(){
        $partnerid = optional_param('partnerid', 0, PARAM_INT);
        $userid = optional_param('userid', 0, PARAM_INT);
        $action = optional_param('action', '', PARAM_TEXT);

        $partner = lm_partner::i($partnerid);

        switch($action){
            case 'archive':
                $partner->archive_staffer($userid);
                break;

            case 'remove':
                $partner->remove_staffer($userid);
                break;

            case 'finaly-remove':
                $partner->finaly_remove_staffer($userid);
                break;
        }
    }

    public function get_members_list(){
        global $PAGE;

        /**
         * @var block_manage_activities_renderer $renderer
         */
        $renderer = $PAGE->get_renderer('block_manage', 'activities');
        $partnerid = optional_param('partnerid', 0, PARAM_INT);

        $a = new StdClass();
        $a->success = true;
        $a->html = $renderer->select_members($partnerid);

        echo json_encode($a);
    }

    public function add_member(){
        global $PAGE;
        $activityid = optional_param('activityid', 0, PARAM_INT);
        $users = optional_param('users', '', PARAM_SEQUENCE);
        $partnerid = optional_param('partnerid', 0, PARAM_INT);

        $a = new StdClass();
        $a->success = false;
        $a->html = $a->error = "";

        $activity = lm_activity::i($activityid);
        if($users = explode(',', $users)) {
            foreach($users as $userid) {
                if ($activity->is_member_exists($userid, $partnerid)) {
                    $a->error = 'Такой пользователь уже записан!';
                    break;
                } else if ($activity->maxmembers > $activity->count_members()) {
                    $memberid = $activity->add_member($userid, $partnerid);
                    if ($memberid) {
                        $a->success = true;
                        /**
                         * @var block_manage_activities_renderer $renderer
                         */
                        $renderer = $PAGE->get_renderer('block_manage', 'activities');
                        $a->html[] = $renderer->get_member_view($userid, $partnerid, $activity->has_capability_edit());
                    } else {
                        $a->error = 'Ошибка!';
                        break;
                    }
                } else {
                    $a->error = 'Превышено допустимое количество участников!';
                    break;
                }
            }
        }
        echo json_encode($a);
    }

    public function process_member(){
        $activityid = optional_param('activityid', 0, PARAM_INT);
        $memberid = optional_param('memberid', 0, PARAM_INT);
        $action = optional_param('action', '', PARAM_TEXT);

        if($action == 'passed'){
            lm_activity::i($activityid)->set_mark_member($memberid, true);
        }else if($action == 'notpassed'){
            lm_activity::i($activityid)->set_mark_member($memberid, false);
        }
    }

    public function get_training_results()
    {
        global $PAGE;
        /**
         * @var block_manage_partners_renderer $renderer
         */
        $renderer = $PAGE->get_renderer('block_manage', 'partners');
        $partnerid = optional_param('partnerid', 0, PARAM_INT);
        $programid = optional_param('programid', 0, PARAM_INT);

        $a = new StdClass();
        $a->success = true;
        $a->html = $renderer->result_panel($partnerid, $programid);

        echo json_encode($a);
    }

    public function create_company()
    {
        $name = optional_param('name', '', PARAM_TEXT);

        $a = new StdClass();
        $a->success = false;
        $a->values = null;
        $company = lm_company::i(0)->setName($name)->create();
        if($company->id){
            $a->success = true;
            $a->values = $company;
            $a->values->typename = $company->type_name();
        }

        echo json_encode($a);
    }

    public function update_company(){
        $companyid = optional_param('companyid', 0, PARAM_INT);
        $name = optional_param('name', '', PARAM_TEXT);
        $type = optional_param('type', 'partner', PARAM_TEXT);

        $a = new StdClass();
        $a->success = false;
        $a->values = null;
        $company = lm_company::i($companyid);
        if($company->setName($name)->setType($type)->update()){
            $a->success = true;
            $a->values = $company;
            $a->values->typename = $company->type_name();
        }

        echo json_encode($a);
    }



    public function charts_program_get_results(){

        $report = lm_report::i();

        $data = array();
        if($count = $report->program_count('passed')){
            $data[] = (object) array('name'=>'Прошли', 'y'=>$count, 'sliced'=> true, 'selected'=> true);
        }

        if($count = $report->program_count('inprocess')){
            $data[] = array('В процессе', $count);
        }

        if($count = $report->program_count('notpassed')){
            $data[] = array('Не прошли', $count);
        }

        if(!$data){
            $data = (object) array('emptytext'=>'<h3 class="chart-placeholder">Нет данных</h3>');
        }

        echo json_encode($data);
    }

    public function charts_tm_results(){

        $data = array();
        $programslist = array();
        if($result = lm_report::i()->get_report_tm_count()){
            foreach($result->programslist as $fullname) {
                if(mb_strlen($fullname, 'utf-8') > 20){
                    $programslist[] = mb_substr($fullname, 0, 20, 'utf-8').'...';
                }else{
                    $programslist[] = $fullname;
                }
            }

            foreach($result->percents as $info) {
                $user = $info['user'];
                $programs = $info['programs'];

                $datapercent = array();
                foreach ($programs as $percent) $datapercent[] = $percent;
                $data[] = (object)array('name' => $user->fullname, 'data' => $datapercent);
            }
        }

        if(!$data){
            $data = (object) array('emptytext'=>'<h3 class="chart-placeholder">Нет данных</h3>');
        }else{
            $data = (object) array('programs'=>$programslist, 'data'=>$data);
        }

        echo json_encode($data);
    }

    public function charts_partner_results(){

        $data = array();
        $result = lm_report::i()->count_by_partner();
        if($result->passedcount){
            $data[] = (object) array('name'=>'Обучены', 'y'=>$result->passedcount, 'sliced'=> true, 'selected'=> true);
        }

        if($result->nottrainedcount){
            $data[] = array('Не обучены', $result->nottrainedcount);
        }

        if(!$data){
            $data = (object) array('emptytext'=>'<h3 class="chart-placeholder">Нет данных</h3>');
        }

        echo json_encode($data);
    }

    public function charts_trainer_results(){

        $data = array();
        $result = lm_report::i()->count_by_trainer();
        foreach($result as $r){
            $data[] = (object) array('name'=>$r->name, 'y'=>$r->count);
        }

        if(!$data){
            $data = (object) array('emptytext'=>'<h3 class="chart-placeholder">Нет данных</h3>');
        }

        echo json_encode($data);
    }

    public function charts_staffer_results(){
        $filter = optional_param('filter', 0, PARAM_RAW);
        $partnerid = isset($filter['partner']) ? $filter['partner'] : 0;
        $userid = isset($filter['user']) ? $filter['user'] : 0;

        $data = array();
        $result = lm_staffer::i($partnerid, $userid)->passed_programs_stat();
        $types = lm_activity::types();
        foreach($result as $type=>$hours){
            if($hours) {
                $type = isset($types[$type]) ? $types[$type] : '';
                $hoursformatted = lm_activity::float2hours($hours);
                $data[] = (object)array('name' => $type, 'y' => $hours, 'formatted' => $hoursformatted);
            }
        }

        if(!$data){
            $data = (object) array('emptytext'=>'<h3 class="chart-placeholder">Нет данных</h3>');
        }

        echo json_encode($data);
    }

    public function get_staffers_results(){
        global $OUTPUT;

        $filter = optional_param('filter', 0, PARAM_RAW);
        $partnerid = isset($filter['partner']) ? $filter['partner'] : 0;
        $userid = isset($filter['user']) ? $filter['user'] : 0;
        $activityid = optional_param('activityid', 0, PARAM_INT);

        $staffer = lm_staffer::i($partnerid, $userid);
        $activity = lm_activity::i($activityid);
        $partner = lm_partner::i($partnerid);

        $data = new StdClass();
        $data->html = '<h4>Программу назначил:</h4>';
        if($activity->programid) {
            if(!$assignedby = $partner->who_assigned_program($activity->programid)){
                $data->html .= 'Этой программы нет в назначенных партнеру!';
            }else if(!$assignedby->id){
                $data->html .= 'Нет информации.';
            }else {
                $data->html .= $OUTPUT->user_picture($assignedby);
                $data->html .= ' <a href="/user/view.php?id=' . $assignedby->id . '"> ' . fullname($assignedby) . '</a>';
            }
        }else{
            $data->html .= 'Ошибка! В этой активности не указана програма!';
        }

        $data->html .= '<h4>На активность записал:</h4>';
        if(!$requestedby = $staffer->who_requested_activity($activity->id)){
            $data->html .= 'Ошибка! Такой записи на активность нет!';
        }else if(!$requestedby->id){
            $data->html .= 'Нет информации.';
        }else{
            $data->html .= $OUTPUT->user_picture($requestedby);
            $data->html .= ' <a href="/user/view.php?id='.$requestedby->id.'"> '.fullname($requestedby).'</a>';
        }


        if($activity->courseid) {
            $data->html .= '<h4>Результаты по тестам:</h4>';
            if ($activity->programid) {
                if ($attempts = $staffer->last_attempt_grades($activity->courseid)) {
                    $data->html .= '<ul>';
                    foreach ($attempts as $attempt) {
                        $data->html .= '<li>' . $attempt->name . ' - ';
                        if ($attempt->grade) {
                            $data->html .= '<a href="/mod/quiz/review.php?attempt=' . $attempt->id . '" target="_blank">' . $attempt->grade . '%</a>';
                        } else {
                            $data->html .= 'не проходил';
                        }

                        $data->html .= '</li>';
                    }
                    $data->html .= '</ul>';
                } else {
                    $data->html .= 'В этом курсе нет тестов';
                }
            } else {
                $data->html .= 'Ошибка! В этой активности не указана програма!';
            }
        }

        echo json_encode($data);
    }

    public function charts_indexstudy_results(){
        $report = lm_report::i();

        $data = array();
        $periods = block_manage_trainer::define_periods('week', strtotime($report->datefrom), strtotime($report->dateto));
        if($trainers = get_trainers_menu()) {
            $i = 0;
            $dynamics = array();
            foreach($trainers as $trainerid=>$fullname) {
                $trainer = block_manage_trainer::i($trainerid);
                $dynamics[$i] = $trainer->index_study_dynamic($periods);
                $data[] = (object)array('name' => $fullname, 'data' => $dynamics[$i]);
                $i ++;
            }

            // Добавляем в граффик кривую со средними значениями
            if($dynamics)
                $data[] = (object) array('name'=>'Среднее значение', 'data'=>$this->calculate_average_dynamic($dynamics));
        }

        if (!$data) {
            $data = (object)array('emptytext' => '<h3 class="chart-placeholder">Нет данных</h3>');
        } else {
            $labels = array();
            if($periods) {
                foreach ($periods as $period) {
                    $labels[] = $period->name;
                }
            }
            $data = (object)array('periods' => $labels, 'data' => $data);
        }


        echo json_encode($data);
    }


    public function charts_indexquality_results(){
        $report = lm_report::i();

        $data = array();
        $periods = block_manage_trainer::define_periods('week', strtotime($report->datefrom), strtotime($report->dateto));
        if($trainers = get_trainers_menu()) {
            $i = 0;
            $dynamics = array();
            foreach ($trainers as $trainerid => $fullname) {
                $trainer = block_manage_trainer::i($trainerid);

                $dynamic = $tmp = array();
                $result = $trainer->index_quality();
                // Преобразуем значения index_quality из дискретных значений (дата - результат), в накопительные, т.е.
                // суммирую предыдущие значения (для нахождения среднего значения). В итоге по этим данным можно
                // постоить граффик динамики изменения показателей качества работы тренера.
                foreach ($periods as $period) {
                    $passedcount = $feedbackcount = $scoressum = $scorescount = 0;
                    if ($result->passedcount) {
                        foreach ($result->passedcount as $time => $count) {
                            if ($time < $period->end && $time > $period->start) {
                                $passedcount += $count;

                                if (isset($result->feedbackcount[$time])) $feedbackcount += $result->feedbackcount[$time];

                                if (isset($result->averagescores[$time])) {
                                    $scoressum += $result->averagescores[$time];
                                    $scorescount ++;
                                }
                            }
                        }
                    }

                    $tmp['passedcount'][$period->end] = $passedcount;
                    $tmp['feedbackcount'][$period->end] = $feedbackcount;
                    $tmp['scoressum'][$period->end] = $scoressum;
                    $tmp['scorescount'][$period->end] = $scorescount;
                }

                // Рассчитываем итоговые индексы и коэфициенты
                foreach($tmp['passedcount'] as $time=>$passedcount){
                    $feedbackcoef = 0;
                    if($passedcount)
                        $feedbackcoef = round($tmp['feedbackcount'][$time] / $passedcount, 2);

                    $scorecoef = round($tmp['scoressum'][$time]/10, 2);
                    $dynamic[] = $feedbackcoef + $scorecoef;
                }

                $dynamics[$i] = $dynamic;
                $data[] = (object)array('name' => $fullname, 'data' => $dynamic);
                $i++;
            }

            // Добавляем в граффик кривую со средними значениями
            if($dynamics)
                $data[] = (object) array('name'=>'Среднее значение', 'data'=>$this->calculate_average_dynamic($dynamics));

        }

        if (!$data) {
            $data = (object)array('emptytext' => '<h3 class="chart-placeholder">Нет данных</h3>');
        } else {
            $labels = array();
            if($periods) {
                foreach ($periods as $period) {
                    $labels[] = $period->name;
                }
            }
            $data = (object)array('periods' => $labels, 'data' => $data);
        }

        echo json_encode($data);
    }

    public function charts_indexsale_results(){
        $report = lm_report::i();

        $data = array();
        $periods = block_manage_trainer::define_periods('decade', strtotime($report->datefrom), strtotime($report->dateto));
        if($trainers = get_trainers_menu()) {
            $i = 0;
            $dynamics = array();
            foreach($trainers as $trainerid=>$fullname) {
                $trainer = block_manage_trainer::i($trainerid);
                $dynamics[$i] = $trainer->index_sale_dynamic($periods);
                $data[] = (object)array('name' => $fullname, 'data' => $dynamics[$i]);
                $i ++;
            }

            // Добавляем в граффик кривую со средними значениями
            if($dynamics)
                $data[] = (object) array('name'=>'Среднее значение', 'data'=>$this->calculate_average_dynamic($dynamics));
        }

        if (!$data) {
            $data = (object)array('emptytext' => '<h3 class="chart-placeholder">Нет данных</h3>');
        } else {
            $labels = array();
            if($periods) {
                foreach ($periods as $period) {
                    $labels[] = $period->name;
                }
            }
            $data = (object)array('periods' => $labels, 'data' => $data);
        }


        echo json_encode($data);
    }


    /**
     * Вычисляет среднюю динамику на основании массива $dynamics, который содержит в себе массивы числовых значений.
     * Результат работы - массив со средними значениями.
     *
     * @param $dynamics
     * @return array
     */
    protected function calculate_average_dynamic($dynamics){
        $averagedynamic = array();
        if($dynamics) {
            $indexescount = count($dynamics[0]);
            for($i=0; $i < $indexescount; $i++) {
                $sum = $count = 0;
                foreach ($dynamics as $dynamic) {
                    if($dynamic[$i]){
                        $sum += $dynamic[$i];
                        $count ++;
                    }
                }

                $averagedynamic[$i] = 0;
                if($count) {
                    $averagedynamic[$i] = $sum / $count;
                }
            }
        }

        return $averagedynamic;
    }


    public function reload_report(){
        global $PAGE;

        /**
         * @var block_manage_report_renderer $renderer
         */
        $renderer = $PAGE->get_renderer('block_manage', 'report');

        $a = new StdClass();
        $a->html = '';
        $type = explode('_', $renderer->report->type);
        switch($type[0]){
            case 'program':
                $a->html = $renderer->get_report_program();
                break;

            case 'partner':
                $a->html = $renderer->get_report_partner();
                break;

            case 'trainer':

                $subtype = isset($type[1]) ? $type[1]: '';
                $a->html = $renderer->get_report_trainer($subtype);
                break;

            case 'tm':
                $a->html = $renderer->get_report_tm();
                break;

            case 'staffer':
                $a->html = $renderer->get_report_staff();
                break;
        }

        echo json_encode($a);
    }

    public function get_assinged_trainers(){
        global $DB, $CFG;

        $a = new StdClass();
        $a->html = '';
        $a->success = false;

        $cityid = optional_param('cityid', 0, PARAM_INT);
        if($cityid){
            $sql = "SELECT u.id, u.firstname, u.lastname
                          FROM {lm_region_trainer} lrt
                          JOIN {user} u ON u.id=lrt.trainerid
                          WHERE lrt.regionid = ?
                          ORDER BY u.lastname ASC";

            $params = array($cityid);
            $trainers = $DB->get_records_sql($sql, $params);

            $table = new html_table();
            $table->id = 'trainerlist';
            $table->head[] = '';
            $table->head[] = '';
            $table->head[] = '';

            $actions = '<a href="#" class="btn btn-mini removetrainer"><i class="icon icon-remove"></i></a>';
            $cells = array( new html_table_cell(''), new html_table_cell(''), new html_table_cell($actions) );
            $row = new html_table_row($cells);
            $row->attributes['class'] = 'clone hide trainer';
            $table->data[] = $row;

            if($trainers){
                $n = 1;
                foreach($trainers as $trainer){
                    $link = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$trainer->id.'">'.$trainer->lastname.' '.$trainer->firstname.'</a>';
                    $cells = array( new html_table_cell($n), new html_table_cell($link), new html_table_cell($actions) );
                    $row = new html_table_row($cells);
                    $row->id = 'trainer-'.$trainer->id;
                    $row->attributes['class'] = 'trainer visible';
                    $table->data[] = $row;

                    $n++;
                }
            }

            $a->html = html_writer::table($table);

            $trainers = get_trainers_menu();

            $a->html .= '<div>'.
                html_writer::select($trainers, 'trainer', 0, 'Добавьте тренера...', array('class'=>'assigntrainerlist')).
                '</div>';

            $a->success = true;
        }

        echo json_encode($a);
    }

    public function assign_region_to_trainer(){
        global $DB, $CFG;

        $a = new StdClass();
        $a->success = false;

        $cityid = optional_param('cityid', 0, PARAM_INT);
        $trainerid = optional_param('trainerid', 0, PARAM_INT);
        $trainer = $DB->get_record('user', array('id'=>$trainerid), 'id, firstname, lastname');
        if($cityid && $trainer){
            $dataobj = new StdClass();
            $dataobj->regionid = $cityid;
            $dataobj->trainerid = $trainerid;
            $a->fullname = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$trainer->id.'">'.$trainer->lastname.' '.$trainer->firstname.'</a>';
            $a->success = (boolean) $DB->insert_record('lm_region_trainer', $dataobj);
        }

        echo json_encode($a);
    }

    public function remove_trainer_from_city(){
        global $DB;

        $cityid = optional_param('cityid', 0, PARAM_INT);
        $trainerid = optional_param('trainerid', 0, PARAM_INT);

        if($cityid && $trainerid){
            $DB->delete_records('lm_region_trainer', array('regionid'=>$cityid, 'trainerid'=>$trainerid));
        }
    }

    public function update_region_name(){
        global $DB;

        $regionid = optional_param('regionid', 0, PARAM_INT);
        $name = optional_param('name', '', PARAM_TEXT);

        $a = new StdClass();
        $a->success = false;

        if($regionid && $name){
            $dataobj = new StdClass();
            $dataobj->id = $regionid;
            $dataobj->name = $name;

            $a->success = (boolean) $DB->update_record('lm_region', $dataobj);
        }

        echo json_encode($a);
    }

    public function region_add_city(){
        global $DB;

        $regionid = optional_param('regionid', 0, PARAM_INT);
        $name = optional_param('name', '', PARAM_TEXT);

        $a = new StdClass();
        $a->success = false;

        if($regionid && $name){
            $dataobj = new StdClass();
            $dataobj->parentid = $regionid;
            $dataobj->name = $name;

            $a->success = (boolean) $DB->insert_record('lm_region', $dataobj);
        }

        echo json_encode($a);
    }



    public function get_place_info(){
        global $PAGE, $DB;

        /**
         * @var block_manage_places_renderer $renderer
         */
        $renderer = $PAGE->get_renderer('block_manage', 'places');
        $placeid = optional_param('placeid', 0, PARAM_INT);

        if(!$placeid){
            $place = lm_place::i(0);
            $place->set('type', 'class');
            $place->create();
        }

        $a = new StdClass();
        $a->html = $renderer->get_place_info($placeid);
        $a->placeid = $placeid;
        echo json_encode($a);
    }




    public function remove_place(){
        if($placeid = optional_param('placeid', 0, PARAM_INT)){
            lm_place::i($placeid)->remove();
        }
    }

    public function place_update_field(){
        $placeid = optional_param('pk', 0, PARAM_INT);
        $value = optional_param('value', '', PARAM_TEXT);
        $field = optional_param('name', '', PARAM_TEXT);

        $place = lm_place::i($placeid);

        $field = explode('-', $field);
        if(!$place->id || !isset($field[1])){
            return false;
        }

        $field = $field[1];
        return $place->set($field, $value)->update();
    }

    public function place_update_address(){
        $placeid = optional_param('pk', 0, PARAM_INT);
        $adressfields = array();
        if(isset($_POST['value'])){
            $adressfields = $_POST['value'];
        }

        $place = lm_place::i($placeid);
        if($adressfields && $place->id){
            foreach($adressfields as $field=>$value){
                if($field == 'cityid'){
                    $value = $value['value'];
                }

                $place->set($field, $value);
            }
            $place->update();
        }
    }

    public function place_update_equipment(){
        $placeid = optional_param('pk', 0, PARAM_INT);
        $fieldlist = array();
        if(isset($_POST['value'])){
            $fieldlist = $_POST['value'];
        }

        $place = lm_place::i($placeid);
        if($fieldlist && $place->id){
            foreach($place->get_equipment_list() as $field=>$name){
                if(in_array($field, $fieldlist)){
                    $place->set($field, 1);
                }else{
                    $place->set($field, 0);
                }
            }
            $place->update();
        }
    }

    public function get_equip_options(){
        $options = array();
        foreach(lm_place::get_equipment_list() as $code=>$name){
            $options[] = (object) array('value'=>$code, 'text'=>$name);
        }

        echo json_encode($options);
    }

    public function get_address_tpl(){
        $select = html_writer::select(get_regions_list(), 'cityid', 0, 'Выберите город...');

        $a = new StdClass();
        $a->html = '<div class="editable-address"><label><span>Город: </span>'.$select.'</label></div>'.
        '<div class="editable-address"><label><span>Улица: </span><input type="text" name="street" class=""></label></div>'.
        '<div class="editable-address"><label><span>Метро: </span><input type="text" name="metro" class=""></label></div>'.
        '<div class="editable-address"><label><span>Дом: </span><input type="text" name="num" class="input-mini"></label></div>'.
        '<div class="editable-address"><label><span>Строение: </span><input type="text" name="bld" class="input-mini"></label></div>'.
        '<div class="editable-address"><label><span>Корпус: </span><input type="text" name="corp" class="input-mini"></label></div>'.
        '<div class="editable-address"><label><span>Этаж: </span><input type="text" name="floor" class="input-mini"></label></div>';

        echo json_encode($a);
    }



    public function get_partners_list(){
        $partners = get_partners_menu();
        $a = array();
        $a[] = (object) array('value'=>0, 'text'=>'Не выбрано');
        foreach($partners as $code=>$name){
            $a[] = (object) array('value'=>$code, 'text'=>$name);
        }

        echo json_encode($a);
    }

    public function sales_import(){
        global $DB;

        if(!lm_user::is_admin()) {
            return false;
        }

        $a = new StdClass();
        $a->success = false;
        $a->html = '';

        $results = optional_param('result', '', PARAM_RAW);
        $periodstart = optional_param('periodstart', 0, PARAM_INT);
        $periodend = $periodstart + 60*60*24*7;

        if(!$results || !$periodstart){
            $a->html = 'Не задан период или нет данных для импорта!';
            echo json_encode($a);
            die();
        }

        $results = json_decode($results);
        if(empty($results)){
            $a->html = 'Некорректные данные для импорта!';
            echo json_encode($a);
            die();
        }

        foreach($results as $trainer){
            $dataobj = new StdClass();
            $dataobj->trainerid = $trainer->id;
            $dataobj->valsr = $trainer->salessr;
            $dataobj->valpr = $trainer->salespr;
            $dataobj->periodstart = $periodstart;
            $dataobj->periodend = $periodend;

            $DB->insert_record('lm_sales', $dataobj);
        }

        $a->success = true;
        $a->html = 'Импорт успешно завершен!';
        echo json_encode($a);
        return true;
    }
}