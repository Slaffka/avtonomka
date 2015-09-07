<?php
class block_lm_myteam_renderer extends block_manage_renderer {
    public $pageurl = '/blocks/manage/?_p=lm_myteam';
    public $pagename = 'Моя команда';
    public $type = 'lm_myteam';
    public $pagelayout = "base";

    public $graph = TRUE;
    public $view = NULL;

    private $members;
    
    public function init_page()
    {
        global $CFG, $USER;

        parent::init_page();

        $this->members = lm_position::i($USER->id)->get_my_team();

        // поднимаем юзера вверх
        uasort($this->members, function(&$a, &$b) {
            global $USER;
            if ($a->id === $USER->id) return -1;
            elseif ($b->id === $USER->id) return 1;
            else return 0;
        });

        // бывает 2 вида отчета: табличный и графический
        $this->view = optional_param('view', 'graph', PARAM_TEXT);
        $tpl_path = $CFG->dirroot . "/blocks/lm_myteam/tpl/subpage/{$this->subpage}";
        if ($this->graph && !file_exists($tpl_path . '-graph.tpl') ) $this->view = 'table';
        $this->page->add_body_class($this->view.'-view');


        $this->graph = $this->view === 'graph';

        switch ($this->subpage) {
            case 'index':
                if ($this->graph) {
                    $this->page->requires->js("/blocks/manage/yui/base.js");
                    $this->page->requires->js("/blocks/lm_kpi/js/donut/donut.js");

                    $this->page->requires->css("/blocks/lm_kpi/js/rangeinput/rangeinput.css");
                    $this->page->requires->js("/blocks/lm_kpi/js/rangeinput/rangeinput.js");

                    $this->page->requires->jquery_plugin('chart.column', 'theme_tibibase');
                    $this->page->requires->js("/blocks/lm_kpi/js/index.js");

                    $this->require_css('kpi');
                    $this->page->requires->css("/theme/tibibase/blocks/lm_myteam/css/kpi.css");
                }
                break;
            case 'rating':
                if ($this->graph) $this->page->requires->js("/theme/tibibase/jquery/chart/line.js");
                $this->page->requires->js("/blocks/lm_myteam/js/rating.js");
                break;
            case 'tutoring':
                if ($this->graph) $this->page->requires->js("/theme/tibibase/jquery/chart/wave.js");
                $this->page->requires->js("/blocks/lm_myteam/js/tutoring.js");
                break;
        }
    }

    public function navigation(){
        $subparts = array(
            array(
                'code' => 'index',
                'name' => 'KPI`s',
                'url'  => '/blocks/manage/?_p=lm_myteam&subpage=kpi&view='.$this->view,
                'alerts' => lm_notification::get_count('lm_myteam:kpi', TRUE)
            ),
            array(
                'code' => 'rating',
                'name' => 'Рейтинг',
                'url'  => '/blocks/manage/?_p=lm_myteam&subpage=rating&view='.$this->view,
                'alerts' => lm_notification::get_count('lm_myteam:rating', TRUE)),
            array(
                'code' => 'practics',
                'name' => 'Практики',
                'url'  => '/blocks/manage/?_p=lm_myteam&subpage=practics&view='.$this->view,
                'alerts' => lm_notification::get_count('lm_myteam:practics', TRUE)
            ),
            array(
                'code' => 'tutoring',
                'name' => 'Обучение',
                'url'  => '/blocks/manage/?_p=lm_myteam&subpage=tutoring&view='.$this->view,
                'alerts' => lm_notification::get_count('lm_myteam:tutoring', TRUE)
            )
        );
        return $this->subnav($subparts);
    }

    public function main_content(){
        global $CFG, $OUTPUT, $USER;

        $tpl = $this->tpl;
        $tpl->user = $USER;

        $memberid = optional_param('member', $USER->id, PARAM_TEXT);

        $current_url = new moodle_url($_SERVER['REQUEST_URI']);

        foreach($this->members as $member){
            if( $pos = lm_position::i($member->id) ) {
                $member->upic = $OUTPUT->user_picture( $member, array("size"=>30, "alttext"=>false) );
                $member->fullname = fullname($member);
                switch ($this->subpage) {
                    case 'index':
                        $kpilist = new lm_kpi_list($pos->id, $member->id);
                        $member->kpiitems = $kpilist->get_latest();
                        if ( ! $member->kpiitems ) $member->kpiitems = $kpilist->items_by_pos();
                        $current_url->param('member', $member->id);
                        $member->url = $current_url->out_as_local_url();
                        break;

                    case 'rating':
                        $rating = new lm_rating($pos->id, $member->id);
                        list($tpl->dates, $member->avg) = $rating->avg_by_last_months();
                        $member->avg_old   = $rating->avg_by_previous_month();
                        $member->incity    = $rating->incity();
                        $member->inregion  = $rating->inregion();
                        $member->incountry = $rating->incountry();
                        break;

                    case 'practics':

                        break;

                    case 'tutoring':
                        //TODO: move partnertid to position
                        if ($parentid = lm_staffer::get_partnerid($member->id)) {
                            $staffer = lm_staffer::i($parentid, $member->id);
                            $member->programs = $staffer->programs();
                            $member->total = 0;
                            foreach ($member->programs as $id => $program) {
                                $member->total += $program->progress;
                            }
                            $member->total /= count($member->programs);
                        }
                        break;
                }
            }
        }

        switch ($this->subpage) {
            case 'index':
                // total
                $total = new stdClass();
                $total->id = 'total';
                $total->fullname = 'Все вместе';
                $total->kpiitems = array();
                foreach ($this->members as $member) {
                    foreach ($member->kpiitems as $kpi) {
                        if ( ! isset($total->kpiitems)) $total->kpiitems[$kpi->id] = new stdClass();
                        foreach($kpi as $param => $value) {
                            if ($param !== 'id' && is_numeric($value)) {
                                $total->kpiitems[$kpi->id]->$param += $value;
                            } else {
                                $total->kpiitems[$kpi->id]->$param = $value;
                            }
                        }
                    }
                }
                $current_url->param('member', 'total');
                $total->url = $current_url->out_as_local_url();

                $this->members['total'] = $total;

                reset($this->members);
                if ($this->members[$memberid]) $member = $this->members[$memberid];
                else $member = current($this->members);

                if ($pos) {
                    $active_kpiid = optional_param('kpi', 0, PARAM_INT);
                    if ( ! $active_kpiid || !isset($member->kpiitems[$active_kpiid])) {
                        reset($member->kpiitems);
                        $active_kpiid = key($member->kpiitems);
                    }
                    $tpl->kpiitems = $member->kpiitems;
                    $tpl->activekpi = $member->kpiitems[$active_kpiid];
                    $tpl->activekpi->isactive = true;

                    $current_url->param('member', $member->id);
                    $tpl->url = $current_url->out_as_local_url();
                }

                lm_notification::delete('lm_kpi');
                lm_notification::delete('lm_myteam:kpi');
                break;
            case 'rating':
                $total = new stdClass();
                $total->fullname = 'Все вместе';
                $total->avg        = array();
                $total->incity    = (object) array('point' => '—', 'total' => '—');
                $total->inregion  = (object) array('point' => '—', 'total' => '—');
                $total->incountry = (object) array('point' => '—', 'total' => '—');
                foreach ($this->members as $member) {
                    foreach ($member->avg as $key => $value) {
                        $total->avg[$key] += $value;
                    }
                }
                foreach ($total->avg as &$avg) {
                    $avg /= count($this->members);
                }
                $this->members['total'] = $total;
                lm_notification::delete('lm_myteam:rating');
                break;
            case 'practics':
                break;
            case 'tutoring':
                $programs = array();

                $total = new stdClass();
                $total->fullname = 'Все вместе';
                $total->programs = array();
                $total->total = 0;
                foreach ($this->members as &$member) {
                    foreach ($member->programs as $id => $program) {
                        $total->programs[$id]->id = $program->id;
                        $total->programs[$id]->name = $program->name;
                        $total->programs[$id]->progress += $program->progress;
                        if ( ! isset($programs[$id])) {
                            $programs[$id] = new stdClass();
                            $programs[$id]->courseid = $program->courseid;
                            $programs[$id]->name = $program->name;
                        }
                        $total->total += $program->progress;
                    }
                }
                $member_count = count($this->members);

                // считаем среднее значение по программам
                foreach ($programs as $id => &$program) {
                    $total->programs[$id]->progress /= $member_count;
                }

                // итого (среднее)
                $total->total /= $member_count;
                $this->members['total'] = $total;

                $tpl->programs = $programs;

                break;
            case 'mistakes':
                $this->mistakes();
                break;
        }

        $tpl->users = $this->members;
        $tpl->user = $member;

        $tpl->memberid = $member->id;

        $tpl_path = "/blocks/lm_myteam/tpl/subpage/{$this->subpage}";
        if ($this->graph && file_exists($CFG->dirroot . $tpl_path . '-graph.tpl')) $tpl_path .= '-graph';
        return $this->fetch($tpl_path.'.tpl');
    }

    public function mistakes() {
        $this->page->requires->js("/blocks/lm_myteam/js/mistakes.js");

        if ($this->graph) $this->page->requires->js("/theme/tibibase/jquery/chart/pie.js");

        $programid = (int) optional_param('programid', 0, PARAM_INT);

        if ($programid < 1) return "Не указан номер программы";

        $courseid = lm_programs::get_courseid($programid);
        $course = lm_course::i($courseid);

        if ( ! $course) return FALSE;

        $tpl = $this->tpl;

        $tpl->title = 'Статистика ошибок по курсу "'.$course->fullname.'"';

        $member_count = count($this->members);
        if ( ! $member_count) return "Команда пуста";

        $categories = array();

        $total = new stdClass();
        $total->fullname = 'Все вместе';
        $total->progress = array();
        foreach ($this->members as &$member) {
            //TODO: move partnertid to position
            if ($parentid = lm_staffer::get_partnerid($member->id)) {
                $staffer = lm_staffer::i($parentid, $member->id);
                $member->progress = $staffer->get_program_mistakes($programid, 0, true);
                foreach ($member->progress as $category => $mistakes) {
                    if ( ! isset($categories[$category])) $categories[$category] = $category;
                    $member->total += $mistakes;
                    $total->progress[$category] += $mistakes;
                    $total->total += $mistakes;
                }
            }
        }
        $this->members['total'] = $total;
        $tpl->categories = $categories;
    }

    public function ajax_course_statistic($p)
    {
        global $USER, $OUTPUT;

        $programid = (int)$p->program;

        if ($programid <= 0) return FALSE;

        $courseid = lm_programs::get_courseid($programid);
        $course = lm_course::i($courseid);

        if (!$course) return FALSE;

        $tpl = $this->tpl;

        $current_staffer = NULL;
        if ($this->members = lm_position::i($USER->id)->get_my_team()) {
            foreach ($this->members as &$member) {
                if ($partnerid = lm_staffer::get_partnerid($member->id)) {
                    $member->upic = $OUTPUT->user_picture($member, array("size" => 30, "alttext" => false));
                    $member->fullname = fullname($member);

                    $staffer = lm_staffer::i($partnerid, $member->id);
                    if ($member->id == $USER->id) $current_staffer = $staffer;

                    // время прохождения последнего теста (время в SCORM-пакете)
                    $member->attempt->duration = $staffer->get_program_duration($programid);

                    // кол-во монет, полученных после прохождения курса (время в SCORM-пакете)
                    $member->attempt->coins = $staffer->get_program_coins($programid);

                    // кол-во допущенных ошибок (в SCORM-пакете)
                    $member->attempt->mistakes = $staffer->get_program_mistakes($programid);
                }
            }
        }
        // поднимаем юзера вверх
        uasort($this->members, function (&$a, &$b) {
            global $USER;
            if ($a->id === $USER->id) return -1;
            elseif ($b->id === $USER->id) return 1;
            else return 0;
        });

        // берем текущий регион
        $partner = lm_partner::i($current_staffer->partnerid);
        $city = lm_city::i($partner->regionid);
        $region = $city->get_parent();

        // получаем округ
        //while ($r = $region->get_parent()) $region = $r;


        $tpl->title = 'Статистика по курсу "' . $course->fullname . '"';

        $tpl->city_duration_avg = lm_program::get_duration_avg($programid, 0, $city->id);
        $tpl->region_duration_avg = lm_program::get_duration_avg($programid, 0, $region->id);
        $tpl->total_duration_avg = lm_program::get_duration_avg($programid);

        $tpl->city_coins_avg = lm_program::get_coins_avg($programid, 0, $city->id);
        $tpl->region_coins_avg = lm_program::get_coins_avg($programid, 0, $region->id);
        $tpl->total_coins_avg = lm_program::get_coins_avg($programid);

        $tpl->city_mistakes_avg = round(10 * lm_program::get_mistakes_avg($programid, 0, $city->id)) / 10;
        $tpl->region_mistakes_avg = round(10 * lm_program::get_mistakes_avg($programid, 0, $region->id)) / 10;
        $tpl->total_mistakes_avg = round(10 * lm_program::get_mistakes_avg($programid)) / 10;

        $tpl->members = $this->members;

        $tpl->detail_url = '?_p=lm_myteam&subpage=mistakes&programid=' . $programid;
        return $this->fetch('/blocks/lm_myteam/tpl/subpage/course-statistic.tpl');
    }

}