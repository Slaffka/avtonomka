<?php

/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 09.07.2015
 * Time: 15:38
 */
class block_lm_tma_renderer extends block_manage_renderer
{
    public $pageurl = '/blocks/manage/?_p=lm_tma';
    public $pagename = 'ТМА';
    public $type = "lm_tma";

    public function init_page()
    {
        global $CFG;

        $this->details_url = $CFG->wwwroot . '/blocks/manage/?_p=lm_tma';
        parent::init_page();

        $this->page->requires->js('/blocks/manage/yui/bootstrap-editable/bootstrap-editable.min.js');
        $this->page->requires->css('/blocks/manage/yui/bootstrap-editable/bootstrap-editable.css');
        $this->page->requires->js("/blocks/{$this->type}/js/script.js");
        $this->page->requires->js("/blocks/{$this->type}/js/admin.js");
        $this->page->requires->js('/blocks/manage/yui/base.js');
        $this->page->requires->jquery_plugin('app', 'theme_tibibase');

        // TODO: Колян, у тебя без подключения стилей банка криво работает ТМА ))) Сделай че-нибудь
        $this->require_css('style', 'lm_bank');
    }

    public function navigation()
    {
        $subparts = array(
            array('code' => 'index', 'name' => 'Текущие', 'alerts' => false),
            array('code' => 'future', 'name' => 'Предстоящие', 'alerts' => false),
            array('code' => 'past', 'name' => 'Прошедшие', 'alerts' => false)
        );
        return $this->subnav($subparts);
    }

    public function main_content()
    {
        global $DB, $USER;

        $tpl = $this->tpl;
        $tpl->tmas = array();
        $pos = lm_position::i();

        // смотрим в каком разделе находимся, чтобы взять соответствующие акции
        $time = date("Y-m-d", time());
        if ( $this->subpage == 'index' ) { // Текущие акции
            $wheredate = "lt.start <= '{$time}' AND lt.end > '{$time}'";
        } else if ( $this->subpage == 'future' ) { // Будущие акции
            $wheredate = "lt.start > '{$time}'";
        } else if ( $this->subpage == 'past' ) { // Прошедшие акции
            $wheredate = "lt.end < '{$time}'";
        }
        $tpl->isadmin = 0;


        if ( lm_user::is_admin() ) {
            $tpl->isadmin = 1;
            // all tma for admin
            $sql = "SELECT DISTINCT(lt.id), lt.*
                    FROM `mdl_lm_tma` lt
                    WHERE {$wheredate}";
        } else {
            if ( $pos->areaid ) {
            // ищем акции, принадлежащие зоне, в которой находится пользователь
            $sql = "SELECT DISTINCT(lt.id), lt.*, ltr.fact, ltr.plan
                    FROM `mdl_lm_tma` lt
                    JOIN `mdl_lm_tma_area` lta ON lta.tmaid = lt.id AND lta.areaid = {$pos->areaid}
                    LEFT JOIN `mdl_lm_tma_results` ltr ON ltr.tmaid = lt.id AND ltr.posxrefid = {$pos->posxrefid}
                    WHERE {$wheredate}";
            } else {
                return $this->fetch('/blocks/lm_tma/tpl/no_data.tpl');
            }
        }

        if ( $sql && $tmas = $DB->get_records_sql($sql) ) {
            foreach ( $tmas as $key => $tma ) {
                $tmas[$key]->days = strval (floor((strtotime($tma->end)-time())/(60*60*24)));
                $tmas[$key]->hour = strval (floor ( ( (strtotime($tma->end)-time()) - ($tmas[$key]->days*24*3600) ) / 3600 ));

                $start = explode("-", $tma->start);
                $end = explode("-", $tma->end);
                $startyear = substr($start[0],2,2);
                $endyear = substr($end[0],2,2);
                $tmas[$key]->start = "{$start[2]}.{$start[1]}.{$startyear}";
                $tmas[$key]->end = "{$end[2]}.{$end[1]}.{$endyear}";
                $tmas[$key]->start = str_replace("-", ".", $tma->start);
                $tmas[$key]->end = str_replace("-", ".", $tma->end);
            }
            $tpl->tmas = $tmas;
            return $this->fetch('/blocks/lm_tma/tpl/details.tpl');
        }

        return $this->fetch('/blocks/lm_tma/tpl/no_data.tpl');
    }


    public function ajax_all_tmas($p)
    {
        global $DB;
        $data = array();
        $time = date("Y-m-d", time());
        $wheredate = "lt.start <= '{$time}' AND lt.end > '{$time}'";
        $pos = lm_position::i();

        $sql = "SELECT DISTINCT(lt.id), lt.*, ltr.fact, ltr.plan
                    FROM `mdl_lm_tma` lt
                    JOIN `mdl_lm_tma_area` lta ON lta.tmaid = lt.id AND lta.areaid = {$pos->areaid}
                    LEFT JOIN `mdl_lm_tma_results` ltr ON ltr.tmaid = lt.id AND ltr.posxrefid = {$pos->posxrefid}
                    WHERE {$wheredate}";
        if ( $tmas = $DB->get_records_sql($sql) ) {
            foreach ( $tmas as $key => $tma ) {
                $fact = $tma->fact ? $tma->fact : 0;
                $plan = $tma->plan ? $tma->plan : 0;
                $data[$key]['id'] = $tma->id;
                $data[$key]['name'] = substr($tma->title,0,strripos(substr($tma->title,0,45),' '));

                $time = (strtotime($tma->end) - time()) / 60; // разница в минутах
                $hour = floor($time / 60); // сколько часов осталось
                $min = $time % 60; // сколько минут осталось
                $sec = '00';

                $data[$key]['time_remaining'] = "Времени осталось: {$hour}:{$min}:{$sec}";
                $data[$key]['timevalue'] = (time() - strtotime($tma->start))/(strtotime($tma->end) - strtotime($tma->start))*100;

                $data[$key]['progress_tma'] = "Прогресс: {$fact} из {$plan}";
                $data[$key]['progressvalue'] = $fact * 100 / $plan;
                $data[$key]['reward'] = (int)$tma->reward;
            }
        }
        echo json_encode($data);
    }

    public function ajax_get_data_widget($p)
    {
        global $DB;
        $data = array();
        $time = date("Y-m-d", time());
        $wheredate = "lt.start <= '{$time}' AND lt.end > '{$time}'";
        $pos = lm_position::i();
        if ( $p->tmaid ) {
            $wheredate .= " AND lt.id = {$p->tmaid}";
        }
        $sql = "SELECT DISTINCT(lt.id), lt.*, ltr.fact, ltr.plan
                    FROM `mdl_lm_tma` lt
                    JOIN `mdl_lm_tma_area` lta ON lta.tmaid = lt.id AND lta.areaid = {$pos->areaid}
                    LEFT JOIN `mdl_lm_tma_results` ltr ON ltr.tmaid = lt.id AND ltr.posxrefid = {$pos->posxrefid}
                    WHERE {$wheredate} LIMIT 1";
        if ( $tma = $DB->get_record_sql($sql) ) {
            $fact = $tma->fact ? $tma->fact : 0;
            $plan = $tma->plan ? $tma->plan : 0;
            $data['id'] = $tma->id;
            $data['name'] = substr($tma->title,0,strripos(substr($tma->title,0,45),' '));

            $time = (strtotime($tma->end) - time())/60; // разница в минутах
            $hour = floor($time / 60); // сколько часов осталось
            $min = $time % 60; // сколько минут осталось
            $sec = '00';

            $data['time_remaining'] = "Времени осталось: {$hour}:{$min}:{$sec}";
            $data['timevalue'] = (time() - strtotime($tma->start))/(strtotime($tma->end) - strtotime($tma->start))*100;

            $data['progress_tma'] = "Прогресс: {$fact} из {$plan}";
            $data['progressvalue'] = $fact * 100 / $plan;
            $data['reward'] = (int)$tma->reward;
        }

        echo json_encode($data);
    }

    public function ajax_get_list_users($p)
    {
        global $OUTPUT;

        $data = array();

        if ( !isset($p->q) ) {
            $p->q = "";
        }
        if ( isset($p->id) && $p->id ) {
            $users = lm_tma::i($p->id)->get_list_users($p->q);
            foreach ($users as $user) {
                $data[] = (object)array('id' => $user->id, 'html' => $OUTPUT->user_picture($user) . ' ' . fullname($user));
            }
        }

        return (object) array('data'=>$data);
    }

    public function ajax_get_list_all_tt($p)
    {
        $data = array();

        if ( !isset($p->q) ) {
            $p->q = "";
        }
        if ( isset($p->id) && $p->id ) {
            $tts = lm_tma::i($p->id)->get_list_all_tt($p->q);
            foreach ($tts as $tt) {
                $data[] = (object)array('id' => $tt->id, 'html' => $tt->name);
            }
        }

        return (object) array('data'=>$data);
    }

    public function ajax_save_action($p)
    {
        global $DB;
        $a = new StdClass();
        $a->status = 'error';
        if ( isset($p->pk) && $p->pk && isset($p->value) && $p->value && isset($p->name) && $p->name ) {
            if ( $p->name == 'title' || $p->name == 'descr' || $p->name == 'reward' ) {
                if ( $p->name == 'title' ) {
                    if ( iconv_strlen($p->value) > 50 ) {
                        $a->text = 'Символов может быть не больше 50!';
                        $a->title = lm_tma::i($p->pk)->title;
                        echo json_encode($a);
                        return false;
                    }
                }
                $data = new StdClass();
                $data->id = $p->pk;
                $data->{$p->name} = $p->value;
                $DB->update_record("lm_tma", $data);
                $a->status = 'success';
                echo json_encode($a);
            }
        }
    }
}