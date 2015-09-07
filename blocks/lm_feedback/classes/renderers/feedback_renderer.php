<?php

class block_lm_feedback_renderer extends block_manage_renderer
{
    public $pageurl = '/blocks/manage/?_p=lm_feedback';
    public $pagename = 'Обращения';


    public $subj = 0;
    public $limit = 10;
    public $p = 1;
    public $type = "lm_feedback";
    public $rolecustomblocks = true;
    public $pagelayout = "grid";

    public function init_page()
    {
        global $CFG;

        $this->details_url = $CFG->wwwroot.'/blocks/manage/?_p=lm_feedback';
        $this->subj = optional_param("subj", 0, PARAM_INT);
        $this->p = optional_param("p", 1, PARAM_INT);

        parent::init_page();

        $this->page->requires->js("/blocks/{$this->type}/js/script.js");
        $this->page->requires->js("/blocks/{$this->type}/js/select.js");
        $this->page->requires->js("/blocks/manage/yui/textareaelastic/jquery.elastic.source.js");
    }

    public function navigation(){
        $subparts = array();
        if( lm_user::is_admin() ) {
            $subparts = array(
                array('code' => 'index', 'name' => 'Свежие', 'alerts' => false),
                array('code' => 'archive', 'name' => 'Архив', 'alerts' => false)
            );
        }

        return $this->subnav($subparts);
    }

    public function main_content()
    {
        global $CFG, $DB, $USER, $OUTPUT;

        $tpl = $this->tpl;
        $moder = $where = $limit = "";

        $archive = 0;
        if($this->subpage == 'archive') $archive = 1;

        if ( lm_user::is_admin() ) {
            $moder = 1;
            if ( $this->subj ) {
                $where .= " AND subjectid = {$this->subj}";
            }
        } else {
            $where .= " AND userid = {$USER->id}";
        }
        $where .= " AND send = 1"; // тикет не находится в стадии создания (еще не создан)

        $start = $end = 0;
        if ( $this->p && $this->p != 1 ) {
            $start = $this->p * $this->limit - $this->limit ;
        }
        if ( $this->p ) {
            $limit = " LIMIT ".$start.",".$this->limit;
        }

        $sql = "SELECT
                  SQL_CALC_FOUND_ROWS f.*, fs.name
                  FROM
                    {$CFG->prefix}lm_feedback f
                  LEFT JOIN {$CFG->prefix}lm_feedback_subjects fs ON fs.id = f.subjectid
                  WHERE
                    arhive = {$archive} {$where} {$limit}
        ";
        $tickets = $DB->get_records_sql($sql);
        if ( !empty($tickets) ) {
            $alltickets = $DB->get_field_select("lm_feedback", "count(id)", "arhive = {$archive} {$where}");

            foreach ($tickets as $key => $ticket) {
                $user = lm_user::i($ticket->userid);
                $tickets[$key]->username = lm_user::short_name($user);
                $tickets[$key]->userava = $OUTPUT->user_picture($user, array('size' => 39, 'link'=>FALSE, 'alttext'=>FALSE));
                $tickets[$key]->time = date("d/m/Y H:i", $ticket->time);
            }

            // START NAVIGATION
            $count_pages = round($alltickets / $this->limit);      // сколько всего страниц
            $active = $this->p;                           // текущая страница
            $count_show_pages = $this->limit;             // лимит показываемых страниц в пагинации
            if ($count_pages > 1) {
                $left = $active - 1;
                if ($left < floor($count_show_pages / 2)) {
                    $start = 1;
                } else {
                    $start = $active - floor($count_show_pages / 2);
                }
                $end = $start + $count_show_pages - 1;
                if ($end > $count_pages) {
                    $start -= ($end - $count_pages);
                    $end = $count_pages;
                    if ($start < 1) {
                        $start = 1;
                    }
                }
            }
            $tpl->start = $start;
            $tpl->active = $active;
            $tpl->end = $end;
            $tpl->count_pages = $count_pages;
            $tpl->limit = $count_show_pages;
            $tpl->link_navig = $this->delete_GET($_SERVER['REQUEST_URI'], "p");
            // END NAVIGATION

            $tpl->tickets = $tickets;
        }
        $tpl->arhive = $archive;
        $tpl->subj = $this->subj;
        $tpl->moder = $moder;

        return $this->fetch('/blocks/lm_feedback/tpl/details.tpl');
    }

    public function delete_GET($url, $name, $amp = false)
    {
        $url = str_replace("&amp;", "&", $url); // Заменяем сущности на амперсанд, если требуется
        list($url_part, $qs_part) = array_pad(explode("?", $url), 2, ""); // Разбиваем URL на 2 части: до знака ? и после
        parse_str($qs_part, $qs_vars); // Разбиваем строку с запросом на массив с параметрами и их значениями
        unset($qs_vars[$name]); // Удаляем необходимый параметр
        if (count($qs_vars) > 0) { // Если есть параметры
            $url = $url_part."?".http_build_query($qs_vars); // Собираем URL обратно
            if ($amp) $url = str_replace("&", "&amp;", $url); // Заменяем амперсанды обратно на сущности, если требуется
        }
        else $url = $url_part; // Если параметров не осталось, то просто берём всё, что идёт до знака ?

        return $url; // Возвращаем итоговый URL
    }

    public function get_content_message($user_avatar, $user_shortname, $date, $text)
    {
        $tpl = $this->tpl;
        $tpl->user_avatar = $user_avatar;
        $tpl->user_shortname = $user_shortname;
        $tpl->date = $date;
        $tpl->message = $text;
        return $this->fetch('/blocks/lm_feedback/tpl/subpage/message.tpl');
    }

    ////////////////////////////////////////////////////////////
    ///////////// AJAX Methods /////////////////////////////////
    ////////////////////////////////////////////////////////////

    public function ajax_add_ticket($param)
    {
        echo json_encode(lm_feedback::i()->create($param));
    }

    public function ajax_update_ticket($param)
    {
        echo json_encode(lm_feedback::i()->update($param));
    }


    public function ajax_uploadfile($param)
    {
        $lm_feedback = lm_feedback::i(0);
        $file = $lm_feedback->upload_file_to_draft($param);
        echo json_encode($file);
    }


    public function ajax_get_data_ticket($param)
    {
        global $USER, $OUTPUT;

        $ticket = new StdClass();
        if ( $param->ticketid ) {
            if ( lm_user::is_admin() || lm_feedback::i($param->ticketid)->check($USER->id) ) {
                $ticket = lm_feedback::i($param->ticketid);
                $user = lm_user::i($ticket->userid);
                $ticket->avatar = $OUTPUT->user_picture($user, array('size' => 70, 'link'=>FALSE, 'alttext'=>FALSE));
                $ticket->date = $ticket->get_date();
                $ticket->username = lm_user::short_name($user);
                $ticket->files = $ticket->get_files();

                $messages = $ticket->get_messages();
                if ( !empty($messages) ) {
                    foreach ( $messages as $message ) {
                        $user = lm_user::i($message->userid);
                        $avatar = $OUTPUT->user_picture($user, array('size' => 70, 'link'=>FALSE, 'alttext'=>FALSE));
                        $name = lm_user::short_name($user);
                        $date = date("d/m/Y H:i", $message->time);
                        $ticket->messages .= $this->get_content_message($avatar, $name, $date, $message->message);
                    }
                }
            }
        }

        echo json_encode($ticket);
    }

    public function ajax_send_message($param)
    {
        global $USER, $DB, $OUTPUT;
        $a = new StdClass();
        if ( lm_user::is_admin() && $param->ticketid && $param->message ) {
            $dbdata = new StdClass();
            $dbdata->userid = $USER->id;
            $dbdata->feedbackid = $param->ticketid;
            $dbdata->message = $param->message;
            $dbdata->time = time();
            $DB->insert_record("lm_feedback_messages", $dbdata);

            $avatar = $OUTPUT->user_picture($USER, array('size' => 70, 'link'=>FALSE, 'alttext'=>FALSE));
            $name = lm_user::short_name($USER);
            $date = date("d/m/Y H:i", $dbdata->time);
            $a->message  = $this->get_content_message($avatar, $name, $date, $param->message);

            $a->send = lm_feedback::i($param->ticketid)->send_message($param->message);
        }

        echo json_encode($a);
    }

    public function ajax_change_status_ticket($param)
    {
        global $USER, $DB;
        $answer = new StdClass();
        $save = 0;
        $alert = "";
        if ( lm_user::is_admin() && $param->ticketid && $param->status ) {
            $data = new StdClass();
            $data->id = $param->ticketid;
            $arhive = 0;
            if ( $param->status == 'new' ) {
                $arhive = 0;
                $answer->title = 'Перенести в архив';
                $answer->status = 'arhive';
                $alert = 'Обращение успешно перенесено в свежие';
            } else if ( $param->status == 'arhive' ) {
                $arhive = 1;
                $answer->title = 'Перенести в свежие';
                $answer->status = 'new';
                $alert = 'Обращение успешно перенесено в архив';
            }
            $data->arhive = $arhive;
            $save = $DB->update_record("lm_feedback", $data);
        }

        if ( $save ) {
            $answer->success = $alert;
            $answer->ticketid = $param->ticketid;
        } else {
            $answer->error = "Ошибка во входных параметрах!";
        }
        echo json_encode($answer);
    }

    public function ajax_get_subjects($param)
    {
        global $USER, $DB;

        $subjects = array();
        if ( lm_user::is_admin() ) {
            $where = " 1";
            if ( $param->q ) {
                $where = "name LIKE '%{$param->q}%'";
            }
            $sql = "SELECT id, name FROM {lm_feedback_subjects} WHERE {$where}";
            $subjects = $DB->get_records_sql($sql);
        }
        echo json_encode($subjects);

    }

}