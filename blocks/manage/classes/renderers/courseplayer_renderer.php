<?php

class block_manage_courseplayer_renderer extends block_manage_renderer{
    public $pageurl = '/blocks/manage/?_p=courseplayer';
    public $pagename = 'Курс';
    public $type = 'manage_courseplayer';
    public $pagelayout = "base";
    public $content = NULL;
    public $courseid = 0;

    public function init_page(){
        global $PAGE, $CFG, $USER, $OUTPUT;

        parent::init_page();

        if( !$this->courseid = optional_param('courseid', 0, PARAM_INT) ){
            echo 'Нет идентификатора курса';
            die();
        }


        if( !$course = lm_course::i($this->courseid) ){
            echo 'Курс не найден';
            die();
        }

        $OUTPUT->notification(get_string('windowclosing'), 'notifysuccess');

        $forcejs = get_config('scorm', 'forcejavascript');
        if (!empty($forcejs)) {
            $PAGE->add_body_class('forcejavascript');
        }
        $this->page->requires->js("/blocks/manage/yui/courseplayer.js");

        $choice = optional_param('lmchoice', NULL, PARAM_TEXT);

        //$course->clear_all_attempts($USER->id);
        if( $lastfinishedcm = $course->get_last_finished_module() ){
            if( $lastfinishedcm->modname == 'scorm' ){
                 // После прохождения SCORM сотруднику показывается результат – сколько он набрал монет и сколько
                 // мог бы набрать максимально и предлагается выбор:
                 // 1. Перейти к следующему модулю (сдать тест)
                 // 2. Пройти SCORM заново – тогда он возвращается на модуль предшествующий скорму, если последний
                 //    является модулем userverifier, либо на сам SCORM

                if( $choice === NULL ){
                    // Показываем результат выполнения SCROM и выбор
                    list($this->tpl->coins, $this->tpl->maxcoins) = $course->calculate_scorm_coins($lastfinishedcm->instance);
                    $this->tpl->reattempt_link = "{$CFG->wwwroot}/blocks/manage/?_p=courseplayer&courseid={$course->id}&lmchoice=reattempt";
                    $this->tpl->next_link = "{$CFG->wwwroot}/blocks/manage/?_p=courseplayer&courseid={$course->id}&lmchoice=next";
                    $this->content = $this->fetch("courseplayer/postscorm.tpl");
                }else if( $choice == 'reattempt' ){
                    $prevfinished = $course->get_prev_finished_module();

                    // Начинаем новую попытку прохождения SCROM
                    // Обнуляем последнюю попытку SCORM
                    $course->clear_module_attempt($lastfinishedcm);

                    if( $prevfinished->modname == 'userverifier' ){
                        // Если перед SCORM был модуль "сфотографигуй себя", обнулим его прохождение
                        // и перебросим пользователя на него, после того, как он сфоткается - запустится SCORM

                        // обнуляем userverivier
                        $course->clear_module_attempt($prevfinished);

                        // переходим к фотографированию
                        header("Location: " . $CFG->wwwroot . $prevfinished->url->out_as_local_url());
                        die();
                    }else{
                        // Просто запускаем SCORM
                        $this->content = $this->get_scorm_content($lastfinishedcm->id, $lastfinishedcm->instance);
                    }
                }else if( $choice == 'next' ){
                    // Ничего не делаем, пользователь хочет перейти к следующему модулю
                }
            }else if( $lastfinishedcm->modname == 'quiz' ){
                // Если результат выполнения последнего теста в курсе (итоговый) меньше проходного балла, установленного
                // для этого теста, то появляется всплывающее окно:
                // «Ваш результат неудовлетворительный.Вам необходимо заново пройти весь курс» и выбор:
                // 1. Пройти заново
                // 2. Пройти позже
                //
                // Если сотрудник набрал проходной балл, то появляется сообщение об успешно пройденном курсе и
                // запускается окно с обратной связью

                $lastquiz = $course->get_last_quize();
                // Если это последний тест в курсе
                if($lastquiz && $lastquiz->instance == $lastfinishedcm->instance){

                    $this->tpl->quizresult = lm_course::calculate_quiz_result($lastfinishedcm->instance, $USER->id);
                    if ($course->is_module_passed($lastfinishedcm)) {
                        $nextcm = $course->get_next_module();
                        if ($nextcm && $nextcm->modname == 'feedback') {
                            if (!$choice) {
                                $this->tpl->feedbackurl = $this->pageurl . "&courseid={$course->id}&lmchoice=next";
                                $this->content = $this->fetch('courseplayer/finished/requirefeedback.tpl');
                            } else if ($choice == 'next') {
                                // Ничего не нужно делать, дальше запустится модуль обратной связи
                            }
                        }
                    }else{
                        if (!$choice) {
                            $this->tpl->laterurl = $CFG->wwwroot . "/blocks/manage/?_p=mycourses";
                            $this->tpl->newattempturl = $this->pageurl . "&lmchoice=newattempt&courseid=" . $this->courseid;
                            $this->content = $this->fetch('courseplayer/finished/failed.tpl');
                        } else if ($choice == 'newattempt') {
                            // Обнуляем прохождение всех модулей в курсе
                            $course->clear_all_attempts($USER->id);
                        }
                    }
                }
            }else if( $lastfinishedcm->modname == 'userverifier' ){
                // Уведомляем начальника о необходимости подтверждения фото
                $pos = lm_position::i();
                if( $pos->get_id() ){
                    $chief = $pos->get_my_chief();
                    if( $chief->id ){
                        $data = (object) array('userid'=>$USER->id, 'courseid'=>$course->id);
                        lm_notification::add('manage:verifyphoto:'.$USER->id, false, $chief->id, $data);
                    }
                }
            }
        }else if( $lastfinishedcm === NULL ){
            // Курс пуст
            $link = html_writer::link($CFG->wwwroot."/blocks/manage/?_p=mycourses", "вернуться к списку курсов");
            $this->content = "Курс пуст! {$link}";
        }

        $nextcm = $course->get_next_module();
        if( !$this->content && $nextcm ) {
            if ($nextcm->modname == 'scorm') {
                $this->content = $this->get_scorm_content($nextcm->id, $nextcm->instance);
            } else if ($nextcm->modname == 'quiz') {
                $url = $CFG->wwwroot."/mod/quiz/startattempt.php?cmid={$nextcm->id}&sesskey=".sesskey();
                header("Location: " . $url);
                die();
            } else if ($nextcm->modname == 'feedback') {
                $url_params = array('id'=>$nextcm->id, 'courseid'=>$this->courseid, 'gopage'=>0);
                $completeurl = new moodle_url('/mod/feedback/complete.php', $url_params);
                header("Location: " . $completeurl->out(false) );
                die();
            } else {
                header("Location: " . $CFG->wwwroot . $nextcm->url->out_as_local_url());
                die();
            }
        }else if ( !$this->content && $nextcm === false) {

            // Курс выполнен, начисляем монеты и выдаем сообщение
            $coins = $course->calculate_course_coins();
            $this->tpl->link = $CFG->wwwroot . "/blocks/manage/?_p=mycourses";
            if($coins) {
                if ($programid = lm_program::get_id_by_courseid($this->courseid)) {
                    if (!$award = lm_bank::i($USER->id)->get_sum('program', $programid)) {
                        if (lm_bank::i($USER->id)->debit($coins, 'program', $programid, 'За прохождение курса')) {
                            $this->tpl->coins = $coins;
                            $this->content = $this->fetch('courseplayer/finished/passed_awarded.tpl');
                        }
                    } else {
                        // Уже было начисление по этому курсу
                        $this->tpl->coins = (int)$award;
                        $this->content = $this->fetch('courseplayer/finished/alreadypassed.tpl');
                    }
                } else {
                    //TODO: ошибка в лог администратору
                }
            }else{
                $this->content = $this->fetch('courseplayer/finished/passed.tpl');
            }
        }
    }

    public function main_content(){
        return $this->content;
    }

    public function get_scorm_content($cmid, $scormid){
        global $CFG, $DB, $USER, $SESSION, $OUTPUT, $PAGE;

        if( !$scoid = $DB->get_field("scorm", "launch", array("id" => $scormid)) ){
            return NULL;
        }

        require_once($CFG->dirroot.'/mod/scorm/locallib.php');
        require_once($CFG->libdir . '/completionlib.php');

        $mode = 'normal';
        $currentorg = '';
        $newattempt = 'off';

        if (! $cm = get_coursemodule_from_id('scorm', $cmid)) {
            print_error('invalidcoursemodule');
        }
        if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
            print_error('coursemisconf');
        }
        if (! $scorm = $DB->get_record("scorm", array("id" => $cm->instance))) {
            print_error('invalidcoursemodule');
        }

        // If new attempt is being triggered set normal mode and increment attempt number.
        $attempt = scorm_get_last_attempt($scorm->id, $USER->id);

        // Check mode is correct and set/validate mode/attempt/newattempt (uses pass by reference).
        scorm_check_mode($scorm, $newattempt, $attempt, $USER->id, $mode);

        if ( !$cm->visible && !has_capability('moodle/course:viewhiddenactivities', context_module::instance($cm->id)) ) {
            notice(get_string("activityiscurrentlyhidden"));
            die;
        }

        // Check if scorm closed.
        $timenow = time();
        if ($scorm->timeclose != 0) {
            if ($scorm->timeopen > $timenow) {
                return $OUTPUT->box(get_string("notopenyet", "scorm", userdate($scorm->timeopen)), "generalbox boxaligncenter");
            } else if ($timenow > $scorm->timeclose) {
                return $OUTPUT->box(get_string("expired", "scorm", userdate($scorm->timeclose)), "generalbox boxaligncenter");
            }
        }

        // TOC processing
        $scorm->version = strtolower(clean_param($scorm->version, PARAM_SAFEDIR));
        if (!file_exists($CFG->dirroot.'/mod/scorm/datamodels/'.$scorm->version.'lib.php')) {
            $scorm->version = 'scorm_12';
        }
        require_once($CFG->dirroot.'/mod/scorm/datamodels/'.$scorm->version.'lib.php');

        if (file_exists($CFG->dirroot.'/mod/scorm/datamodels/'.$scorm->version.'.php')) {
            include_once($CFG->dirroot.'/mod/scorm/datamodels/'.$scorm->version.'.php');
        } else {
            include_once($CFG->dirroot.'/mod/scorm/datamodels/scorm_12.php');
        }

        $result = scorm_get_toc($USER, $scorm, $cm->id, TOCJSLINK, $currentorg, $scoid, $mode, $attempt, true, true);
        $sco = $result->sco;

        // Stop if no attempts left
        if ($scorm->lastattemptlock == 1 && $result->attemptleft == 0) {
            return $OUTPUT->notification(get_string('exceededmaxattempts', 'scorm'));
        }

        // Mark session. No iformation how it used, but let's it keep a live
        $SESSION->scorm = new stdClass();
        $SESSION->scorm->scoid = $sco->id;
        $SESSION->scorm->scormstatus = 'Not Initialized';
        $SESSION->scorm->scormmode = $mode;
        $SESSION->scorm->attempt = $attempt;

        // Mark module viewed.
        $completion = new completion_info($course);
        $completion->set_module_viewed($cm);

        // Some JS requirements. Don't know about it, but seems it needed
        $PAGE->requires->data_for_js('scormplayerdata', Array('launch' => false,
            'currentorg' => '',
            'sco' => 0,
            'scorm' => 0,
            'courseid' => $scorm->course,
            'cwidth' => $scorm->width,
            'cheight' => $scorm->height,
            'popupoptions' => $scorm->options), true);
        $PAGE->requires->js('/mod/scorm/request.js', true);
        $PAGE->requires->js('/lib/cookies.js', true);
        $PAGE->requires->js('/blocks/lm_report/js/statistics_include.js', true);
        if (file_exists($CFG->dirroot.'/mod/scorm/datamodels/'.$scorm->version.'.js')) {
            $PAGE->requires->js('/mod/scorm/datamodels/'.$scorm->version.'.js', true);
        } else {
            $PAGE->requires->js('/mod/scorm/datamodels/scorm_12.js', true);
        }

        // Init JS-call to put iframe with scorm media into #scorm_content
        $scoes = scorm_get_toc_object($USER, $scorm, $currentorg, $sco->id, $mode, $attempt);
        $adlnav = scorm_get_adlnav_json($scoes['scoes']);

        $scorm->nav = intval($scorm->nav);
        if (!isset($result->toctitle)) $result->toctitle = get_string('toc', 'scorm');
        $jsmodule = array('name' => 'mod_scorm', 'fullpath' => '/mod/scorm/module.js', 'requires' => array('json'));
        $args = array($scorm->nav, $scorm->navpositionleft, $scorm->navpositiontop,
            $scorm->hidetoc, 767, $result->toctitle, false, $sco->id, $adlnav);
        $PAGE->requires->js_init_call('M.mod_scorm.init', $args, false, $jsmodule);


        // Set the start time of this SCO.
        scorm_insert_track($USER->id, $scorm->id, $scoid, $attempt, 'x.start.time', time());


        // Content generation
        // TODO: Remove the code below if you recode /mod/scorm/module.js
        ///////////////////////////////////////////////////////////
        $content = html_writer::start_div('yui3-g-r', array('id' => 'scorm_layout', 'style'=>'display:none'));
        $content .= html_writer::start_div('yui3-u-1-5', array('id' => 'scorm_toc'));
        $content .= html_writer::div('', '', array('id' => 'scorm_toc_title'));
        $content .= html_writer::start_div('', array('id' => 'scorm_tree'));
        $scoes = scorm_get_toc_object($USER, $scorm, $currentorg, $scoid, $mode, $attempt, true, null);

        $treeview = scorm_format_toc_for_treeview($USER, $scorm, $scoes['scoes'][0]->children, $scoes['usertracks'], $cm->id,
            TOCJSLINK, $currentorg, $attempt, true, null, false);

        $content .= $treeview->toc;
        $content .= html_writer::end_div();
        $content .= html_writer::end_div();
        $content .= html_writer::start_div('', array('id' => 'scorm_toc_toggle'));
        $content .= html_writer::tag('button', '', array('id' => 'scorm_toc_toggle_btn'));
        $content .= html_writer::end_div();
        $content .= html_writer::end_div();

        $content .= html_writer::div('', '', array('id' => 'scorm_navpanel'));
        /////////////////////////////////////////////////////////

        $content .= html_writer::div('', '', array('id' => 'scorm_content'));


        return $content;
    }
}