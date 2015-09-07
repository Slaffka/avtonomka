<?php

class block_lm_rating_renderer extends block_manage_renderer
{
    /**
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=lm_rating';
    public $pagename = 'Рейтинги';
    public $type = 'lm_rating';
    public $pagelayout = "grid";
    public $details = '';
    public $rolecustomblocks = true;
    public $hg = 600;


    public $filter = "";
    public $post_param = 0;
    public $p = '1';
    public $date = '';


    public function init_page()
    {
        global $CFG;

        $this->details_url = $CFG->wwwroot.'/blocks/manage/?_p=lm_rating';
        $this->p = optional_param('p', '1', PARAM_INT);
        $this->filter = optional_param('f', '', PARAM_TEXT);
        $this->post_param = optional_param('post', 0, PARAM_INT);
        $this->date = optional_param('date', "", PARAM_TEXT);
        $this->hg = optional_param('hg', 600, PARAM_INT);

        parent::init_page();

        $this->page->requires->js("/blocks/lm_rating/js/script.js");

        $this->page->requires->jquery_plugin('months_range', 'theme_tibibase');
    }


    public function navigation(){

        $subpages = array('index' => "Текущий рейтинг", 'top' => "Зал славы");
        return $this->subnav($subpages);
    }

    public function main_content(){
        global $CFG, $DB, $OUTPUT, $USER;


        $tpl = $this->tpl;
        $userid = $USER->id;
        //$userid = 630;
        $pos = lm_position::i($userid);
        $where = array();

        $date = explode("-", $this->date);
        if ( isset($date[1]) ) {
            $startmonth = explode("/", trim($date[0]));
            $start = $startmonth[1].'-'.$startmonth[0].'-01';

            $endmonth = explode("/", trim($date[1]));
            $days = cal_days_in_month(CAL_GREGORIAN, $endmonth[0], $endmonth[1]);
            $end = $endmonth[1].'-'.$endmonth[0].'-'.$days;
            $w = "v.date BETWEEN '{$start}' AND '{$end}'";
        } else {
            //$date = '2015-04-07'; // дата последнего импорта в бд
            $date = $DB->get_field_sql("SELECT date FROM {lm_rating_metric_value} WHERE 1 GROUP BY date ORDER BY date DESC LIMIT 1");
            $date = explode("-", $date);
            $days = cal_days_in_month(CAL_GREGORIAN, $date[1], $date[0]);
            $w = "v.date BETWEEN '{$date[0]}-{$date[1]}-01' AND '{$date[0]}-{$date[1]}-{$days}'";
        }

        $posts_filter = $CFG->block_lm_rating_posts_filter;
        if ( $posts_filter ) {
            $posts_filter = explode(",",$posts_filter);
            foreach ( $posts_filter as $post ) {
                $tpl->posts[$post] = $DB->get_field_select("lm_post", "name", "id = {$post}" );
            }
        }

        $where[] = $w;
        $tpl->region = $tpl->city = $tpl->country = $tpl->sv = $tpl->tm = $tpl->tp = $tpl->newbies = $tpl->staff = false;
        $where_filter = $where_post = $join = "";

        if ( lm_user::is_admin($userid) ) {
            $moder = 1; // включение интерфейса модератора
        } else {
            $moder = 0;
        }

        $tpl->moder = $moder;
        if ( $tpl->moder ) {
            /* Фильтр по должностям для модератора */
            if ( in_array($this->post_param, $posts_filter) ) { // если Get_postid есть в массиве доступных постов
                $where_post = "p.postid = {$this->post_param}";
                $tpl->active_post = $this->post_param;
            } else { // ТП
                $where_post = "p.postid = {$posts_filter[0]}";
                $tpl->active_post = $posts_filter[0];
            }

            /* фильтр по новичкам / сотрудникам */
            if ( $this->filter == 'newbies' ) { // новички
                $tpl->newbies = true;
                $where_filter = "lu.hiredate > DATE_ADD(NOW(),Interval -3 MONTH)";
            } else { // сотрудники
                $tpl->staff = true;
                $where_filter = "((lu.hiredate < DATE_ADD(NOW(),Interval -3 MONTH)) OR (lu.hiredate IS NULL)) ";
            }
        } else {
            // есть ли пользователь в орг структуре
            if ( !$pos->id ) {
                return $this->fetch('/blocks/lm_rating/tpl/no_data.tpl');
            }
            /* фильтр для обычного сотрудника */
            if ($this->filter == 'city') { // юзеры из одного города
                $tpl->city = true;
                $where_filter = "p.cityid = {$pos->cityid}";
            } elseif ($this->filter == 'country') { // юзеры по всей стране
                $tpl->country = true;
                $where_filter = "";
            } else {
                //$where_filter = "p.parentid = {$pos->parentid}"; // юзеры из региона
                if ( $region = $DB->get_field_select("lm_region", "parentid", "id = {$pos->cityid}") ) {
                    // TODO: город равен 0 - вероятно это ошибка, ее записывать в лог
                    $where_filter = "r.parentid = {$region}";
                }
                $join = "LEFT JOIN {$CFG->prefix}lm_region as r ON r.id = p.cityid";
                $tpl->region = true;
            }
            $where_post = "p.postid = {$pos->postid}";
        }


        $p  = $f = $pt = "";
        if ( $this->p ) {
            $p = "&p={$this->p}";
        }
        if ( $this->filter ) {
            $f = "&f={$this->filter}";
        }
        if ( $this->post_param ) {
            $pt = "&post={$this->post_param}";
        }

        if ( $this->date ) {
            $get_date = "&date={$this->date}";
        } else {
            $get_date = "";
        }

        // прописываем ссылки
        /* фильтр по пост'у */
        $tpl->link = $this->pageurl."{$get_date}{$f}&p=1";

        /* фильтр по новичкам / сотрудника */
        $tpl->link_newbies = $this->pageurl."{$pt}&f=newbies&p=1{$get_date}";
        $tpl->link_staff = $this->pageurl."{$pt}&f=staff&p=1{$get_date}";

        /* фильтр по месту: команда / страна / город */
        $tpl->link_city = $this->pageurl."&f=city&p=1{$get_date}";
        $tpl->link_region = $this->pageurl."&f=region&p=1{$get_date}";
        $tpl->link_country = $this->pageurl."&f=country&p=1{$get_date}";

        // ссылка для страничек навигации
        $tpl->link_navig = $this->delete_GET($_SERVER['REQUEST_URI'], "p");

        $limit = round(($this->hg - 300) / 75);
        $start = $used_point = 0;

        if ( $this->p && $this->p != 1 ) {
            $start = $this->p * $limit - $limit ;
        }

        $select = "
                /* юзер ид */
                v.posid, v.userid as id,
                u.firstname, u.lastname,
                /* его средневзвешенный бал */
                SUM(m.weight*v.bal) as avg,
                GROUP_CONCAT(
                   CONCAT_WS(0x1F, v.metricid, v.bal*m.weight, v.id)
                    ORDER BY v.metricid ASC
                    SEPARATOR 0x1E
                ) AS metrics";
        $tpl->data = $tpl->title_metrics = array();
        // генерируем массив юзеров, которых нужно зафиксировать
        $fixed_users = array($userid);

        if ( $where_filter ) {
            $where[] = $where_filter;
        }
        if ( $where_post ) {
            $where[] = $where_post;
        }
        $sql_limit = " LIMIT {$start}, {$limit}";

        $users = $this->get_users($select, $join, $where, $sql_limit);
        $all_users = count($this->get_users($select, $join, $where, ""));

        $title_metrics = 1;
        foreach ( $fixed_users as $fixed_user ) {
            $user = $this->get_fixed_users($select, $join, $where, $fixed_user); // получаем данные по фиксированному юзеру
            if ( $user ) {
                $u = $DB->get_record('user', array('id' => $user->id));
                if ( !$u ) {
                    continue;
                }

                $place = lm_rating::i($pos->id, $user->id)->query_point($where, $join);


                $tpl->data[$user->posid]->point = $place->point;
                $user->metrics = explode(chr(0x1E), $user->metrics);
                $tpl->data[$user->posid]->ava = $OUTPUT->user_picture($u, array('size' => 50, 'alttext' => false ) );
                $tpl->data[$user->posid]->fio = lm_user::short_name($user);
                $tpl->data[$user->posid]->id = $user->id;
                $tpl->data[$user->posid]->avg = round($user->avg, 2);

                foreach ($user->metrics as &$metric) {
                    list($id, $bal, $mvid) = explode(chr(0x1F), $metric);
                    $m = array('id' => (int)$id, 'bal' => round($bal, 2), 'mvid' => (int)$mvid);
                    $tpl->data[$user->posid]->metrics[$m['id']] = $m;
                    if ( $title_metrics ) {
                        $metricname = $DB->get_field_select("lm_rating_metric", "name", "id = {$id}");
                        $tpl->title_metrics['metric' . $id] = $metricname;
                    }
                }
                $used_point = $tpl->data[$user->posid]->point; // места, которые уже были использованы
                $tpl->fixed = true;
            }
            $title_metrics = false;
        }
        // скорее всего придется вместо user->id использовать $pos->id - чтобы не было совпадений по юзерам, ибо косячат метрики
        // $tpl->data[$user->id] - TODO: лог для админов
        $title_metrics = true;
        if ( $users ) {
            $place = 1;
            if ( $this->p && $this->p > 1 ) {
                $place = $limit * ($this->p - 1) + 1;
            }
            foreach ( $users as $user ) {
                if ( in_array($user->id, $fixed_users ) ) {

                    continue;
                }

                $u = $DB->get_record('user', array('id' => $user->id));
                if ( !$u ) {
                    // TODO: помещать инфу в лог для админа
                    continue;
                }
                if ( $place == $used_point ) {
                    $place++;
                }
                $tpl->data[$user->posid]->point = $place;
                $user->metrics = explode(chr(0x1E), $user->metrics);

                $tpl->data[$user->posid]->ava = $OUTPUT->user_picture($u, array('size' => 50, 'alttext' => false ) );
                $tpl->data[$user->posid]->fio = lm_user::short_name($user);
                $tpl->data[$user->posid]->id = $user->id;
                $tpl->data[$user->posid]->avg = round($user->avg, 2);

                foreach ($user->metrics as &$metric) {
                    // $mvid - metric_value_id - нужно будет для выбора параметров
                    list($id, $bal, $mvid) = explode(chr(0x1F), $metric);
                    $m = array('id' => (int)$id, 'bal' => round($bal, 2), 'mvid' => (int)$mvid);
                    $tpl->data[$user->posid]->metrics[$m['id']] = $m;

                    if ( $title_metrics ) {
                        $metricname = $DB->get_field_select("lm_rating_metric", "name", "id = {$id}");
                        $tpl->title_metrics['metric' . $id] = $metricname;
                    }
                }
                $title_metrics = false;
                $place++;
            }
        }

        $tpl->count_metrics = count($tpl->title_metrics);
        $count_pages = round($all_users / $limit);      // сколько всего страниц
        $active = $this->p;                             // текущая страница
        $count_show_pages = $limit;                     // лимит показываемых страниц в пагинации
        if ( $count_pages > 1 ) {
            $left = $active - 1;
            if ( $left < floor($count_show_pages / 2) ) {
                $start = 1;
            } else {
                $start = $active - floor($count_show_pages / 2);
            }
            $end = $start + $count_show_pages - 1;
            if ( $end > $count_pages ) {
                $start -= ($end - $count_pages);
                $end = $count_pages;
                if ( $start < 1 ) {
                    $start = 1;
                }
            }
        }
        $tpl->start = $start;
        $tpl->active = $active;
        $tpl->end = $end;
        $tpl->count_pages = $count_pages;
        $tpl->limit = $count_show_pages;
        $tpl->all_users = $all_users;

        lm_notification::delete('lm_rating');
        return $this->fetch('/blocks/lm_rating/tpl/details.tpl');
    }


    public function get_users($select, $join, $where, $limit)
    {
        global $DB, $CFG;

        $where = implode(" AND ", $where);
        $sql = "
            SELECT
                {$select}
            FROM
                {$CFG->prefix}lm_rating_metric_value as `v`
                LEFT JOIN {$CFG->prefix}lm_rating_metric as `m` ON `m`.`id` = `v`.`metricid`
                LEFT JOIN {$CFG->prefix}lm_position as p ON p.id = v.posid AND p.cityid != 0
                LEFT JOIN {$CFG->prefix}lm_position_xref as px ON px.posid = p.id AND px.archive = 0
                LEFT JOIN {$CFG->prefix}user as `u` ON `u`.`id` = `v`.`userid`
                LEFT JOIN {$CFG->prefix}lm_user as lu ON lu.userid = v.userid
                {$join}
            WHERE
                /* фильтр по времени */
                /* фильтр по должности (пост'у) */
                /* фильтр по городу, стране, команде */
                {$where} AND v.userid != 0
            GROUP BY v.posid
            ORDER BY
                 avg DESC,
                 m.weight DESC,
                 v.bal DESC,
                 u.lastname DESC,
                 v.userid DESC
            {$limit}
        ";

        $users = $DB->get_records_sql($sql);

        return $users;
    }

    public function get_fixed_users($select, $join, $where, $userid)
    {
        global $DB, $CFG;

        $where = implode(" AND ", $where);
        $sql = "
            SELECT
                {$select}
            FROM
                {$CFG->prefix}lm_rating_metric_value as v
                LEFT JOIN {$CFG->prefix}lm_rating_metric as m ON m.id = v.metricid
                LEFT JOIN {$CFG->prefix}lm_position as p ON p.id = v.posid AND p.cityid != 0
                LEFT JOIN {$CFG->prefix}lm_position_xref as px ON px.posid = p.id AND px.archive  = 0
                LEFT JOIN {$CFG->prefix}user as u ON u.id = v.userid
                LEFT JOIN {$CFG->prefix}lm_user as lu ON lu.userid = v.userid
                {$join}
            WHERE
                /* фильтр по времени */
                /* фильтр по должности (пост'у) */
                /* фильтр по городу, стране, команде */
                {$where} AND v.userid = {$userid}
            GROUP BY v.posid
            ORDER BY
                 avg DESC,
                 m.weight DESC,
                 v.bal DESC,
                 u.lastname DESC,
                 v.userid DESC
            LIMIT 0, 1
        ";

        $users = $DB->get_record_sql($sql);

        return $users;
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

    public function ajax_get_rating_by_month(){
        $data = array();
        if( list($dates, $rating) = lm_rating::me()->avg_by_last_months() ){
            $n = 1;
            $months = array("Янв", "Фев", "Март", "Апр", "Май", "Июнь", "Июль", "Авг", "Сент", "Окт", "Ноя", "Дек");
            foreach($dates as $date){
                $monthnum = date( "n", strtotime(str_replace('.','-',$date)) );
                $bal = isset($rating[$n-1]) ? $rating[$n-1]: 0;
                $data[] = (object) array('caption'=>$months[$monthnum-1], 'value'=> $bal);//0.3 + 3.5*rand(0, 1));
                $n++;
            }
        }

        return $data;
    }
}