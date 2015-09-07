<?php

class block_lm_bank_renderer extends block_manage_renderer
{
    /**
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=lm_bank';
    public $pagename = 'Мой банк';
    public $pagelayout = "base";
    public $details = '';
    public $rolecustomblocks = true;
    public $type = "lm_bank";
    public $moder = 0; // убрать когда будет все готово
    public $isadmin = 0;
    public $limit = 10;

    public $p = '1';
    public $date = '';
    public $stat = "income";
    public $where_amount = "AND amount > 0";

    public function init_page()
    {
        global $USER;

        $this->p = optional_param('p', '1', PARAM_INT);
        $this->date = optional_param('date', "", PARAM_TEXT);
        $this->stat = optional_param('stat', "income", PARAM_TEXT);

        $this->moder = 1; // убрать когда будет все готово


        $this->isadmin = 0;
        if ( lm_user::is_admin($USER->id) ) {
            $this->isadmin = 1;
        }
        parent::init_page();

        if ( !$this->isadmin ) {
            $this->page->requires->jquery_plugin('datepicker', 'theme_tibibase');
            $this->page->requires->jquery_plugin('months_range', 'theme_tibibase');
            $this->page->requires->jquery_plugin('chart.balls',  'theme_tibibase');
            $this->page->requires->js("/blocks/{$this->type}/js/client.js");
            $this->page->requires->js("/blocks/{$this->type}/js/moment.js");
        } else {
            $this->page->requires->js("/blocks/{$this->type}/js/admin.js");
            $this->page->requires->js('/blocks/manage/yui/base.js');
            $this->page->requires->jquery_plugin('chart.pie', 'theme_tibibase');
            $this->page->requires->jquery_plugin('chart.column', 'theme_tibibase');


        }
    }

    public function navigation()
    {
        if ( $this->isadmin ) {
            $subpages = array('index' => "Динамика монет", 'catalog' => "Товары", 'basket' => "Заказы");
            return $this->subnav($subpages);
        } else {
            $subpages = array('index' => "Инфо", 'catalog' => "Каталог", 'basket' => "Корзина");
            return $this->subnav($subpages);
        }
    }

    public function main_content()
    {
        global $CFG, $DB, $USER, $OUTPUT;

        $tpl = $this->tpl;
        $tpl->isadmin = $this->isadmin;
        $tpl->balance = lm_bank::me()->get_balance();
        $userid = $USER->id;
        $w = "";
        if ( !$tpl->isadmin ) {
            $date = explode("-", $this->date);
            if ( isset($date[0]) ) {
                $startmonth = explode("/", trim($date[0]));
                if ( isset($startmonth[1]) && isset($startmonth[0]) ) {
                    $start = $startmonth[1] . '-' . $startmonth[0] . '-01';
                    if ( isset($date[1]) ) {
                        $endmonth = explode("/", trim($date[1]));
                        $days = cal_days_in_month(CAL_GREGORIAN, $endmonth[0], $endmonth[1]);
                        $end = $endmonth[1] . '-' . $endmonth[0] . '-' . $days;
                        $w = "AND date BETWEEN '{$start}' AND '{$end}'";
                    } else {
                        $w = "AND month(date) = {$startmonth[0]} AND year(date) = {$startmonth[1]}";
                    }
                }
            }

            $sql_debet = "SELECT * FROM {$CFG->prefix}lm_bank_account WHERE userid = {$userid} {$w} AND amount > 0 ORDER BY id";
            $sql_credit = "SELECT * FROM {$CFG->prefix}lm_bank_account WHERE userid = {$userid} {$w} AND amount < 0 ORDER BY id";

            if ( $payments_debet = $DB->get_records_sql($sql_debet) ) {
                $payments_debet = $this->generation_accounts_for_balls($payments_debet, '+');
                $tpl->payments_debet = $payments_debet;
            }

            if ( $payments_credit = $DB->get_records_sql($sql_credit) ) {
                $payments_credit = $this->generation_accounts_for_balls($payments_credit, '-');
                $tpl->payments_credit = $payments_credit;
            }
            
            $burninginfo = lm_bank::me()->burning_info();
            if ( empty($burninginfo) ) {
                $tpl->burning_info = 0;
            } else {
                $tpl->burning_info = 1;
            }

            /*$payments = $DB->get_records_sql($sql);
            $tpl->payments = $payments;*/

        } else {
            $get_userid = optional_param('userid', 0, PARAM_INT);
            if ( !$get_userid ) {
                $tpl->link = $this->pageurl;
                $tpl->income_link = $tpl->expense_link = 0;

                if ( $this->stat == 'income' ) {
                    $this->tpl->income_link = 1;
                    $where_amount = "AND amount > 0";
                } else if ( $this->stat == 'expense' ) {
                    $this->tpl->expense_link = 1;
                    $where_amount = "AND amount < 0";
                }

                $tpl->stat = $this->stat;
                // START Данные для диаграммы
                // текущие данные
                $cur_year = date("Y");
                $cur_month = date("n");
                $diagrams = array();

                for ($i = 0; $i < 6; $i++) {
                    if ($cur_month + $i < 6) {
                        $year = $cur_year - 1;
                        $month = $cur_month - 6 + 13 + $i;
                    } else {
                        $year = $cur_year;
                        $month = $cur_month - 6 + $i + 1;
                    }
                    $sql = "SELECT SUM(amount) as sum FROM {$CFG->prefix}lm_bank_account WHERE month(date) = {$month} AND year(date) = {$year} {$where_amount}";
                    $payment = $DB->get_record_sql($sql);
                    if (!is_null($payment->sum)) {
                        $diagrams[$i]->sum = $payment->sum;
                    } else {
                        $diagrams[$i]->sum = 0;
                    }
                    $diagrams[$i]->month = $month;
                    $diagrams[$i]->year = $year;
                }
                $tpl->whereamount = $this->stat;
                $tpl->diagrams = $diagrams;
                $tpl->payments = false;
                if ( $payments = $DB->get_record_sql("SELECT count(id) as count FROM {lm_bank_account} WHERE 1") ) {
                    $tpl->payments = $payments->count;
                }

                // END Данные для диаграммы
            } else {
                $tpl->userid = $get_userid;
                $user = lm_user::i($get_userid);
                $lm_bank = lm_bank::i($get_userid);
                $tpl->balance = $lm_bank->get_balance();
                $tpl->username = $user->fullname();
                $tpl->userava = $OUTPUT->user_picture($user, array('size' => 60, 'link'=>FALSE, 'alttext'=>FALSE));
                $info_burn = $lm_bank->info_burn();

                if ( is_object($info_burn) ) {
                    $tpl->info_burn = $info_burn;
                }

                $where = $limit = "";
                $start = $end = 0;
                if ( $this->p && $this->p != 1 ) {
                    $start = $this->p * $this->limit - $this->limit ;
                }
                if ( $this->p ) {
                    $limit = " LIMIT ".$start.",".$this->limit;
                }
                $sql = "SELECT * FROM {$CFG->prefix}lm_bank_account WHERE userid = '{$get_userid}' {$limit} ";
                $payments = $DB->get_records_sql($sql);
                $array = array("монета", "монеты", "монет");
                if ( !empty($payments) ) {
                    foreach ($payments as $payment) {
                        $payments[$payment->id]->title_money = $this->get_word($payment->amount, $array);
                    }
                    $tpl->payments = $payments;
                }

                $allpayments = $DB->get_field_select("lm_bank_account", "count(id)", "userid = '{$get_userid}'");

                // START NAVIGATION
                $count_pages = ceil($allpayments / $this->limit);      // сколько всего страниц
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


            }
        }

        lm_notification::delete('lm_bank');
        return $this->fetch("/blocks/{$this->type}/tpl/details.tpl");
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

    /**
     * Генерация счетов для шаров из стакана
     * @param $payments
     */
    public function generation_accounts_for_balls($payments, $type)
    {
        $olddate = "";
        $balls = array();

        if ( count($payments) > 3 ) {
            $limitoneball = count($payments) / 3;
            $limitoneball = number_format($limitoneball, 1, '.', ' ');;
            $limitoneball = ceil($limitoneball); // сколько транзакций будет в каждом шарике
        } else {
            $limitoneball = 1; // сколько транзакций будет в каждом шарике
        }

        $count_balls = 1;
        $key = 1;
        $ball = new StdClass();
        foreach ( $payments as $payment ) {
            if ( $count_balls <= $limitoneball ) {
                $count_balls++;
            } else {
                $count_balls = 2;
                $key++;
                $balls[] = $ball;
                $ball = new StdClass();
            }

            if ( empty($ball->id) ) {
                $ball->id = $payment->id;
            } else {
                $ball->id = $ball->id . ", " . $payment->id;
            }

            $ball->color = $type == '+' ? '#06a709' : '#ff353d';
            $ball->value = $ball->value + $payment->amount;
            $ball->label = number_format($ball->value, 0, ',', "'");
        }
        if ( empty($balls) || !empty($ball) ) {
            $balls[] = $ball;
        }

        return $balls;

    }

    /**
     * Просклонять слово
     * @param $number - ваше число
     * @param $suffix - массив слов вида: array("монета", "монеты", "монет");
     * @return mixed
     */
    public function get_word($number, $suffix) {

        $keys = array(2, 0, 1, 1, 1, 2);
        if ( $number < 0 ) {
            $number = $number * (-1);
        }
        $mod = $number % 100;
        $suffix_key = ($mod > 7 && $mod < 20) ? 2: $keys[min($mod % 10, 5)];

        return $suffix[$suffix_key];
    }


    /////////////////////////////////////////////////////////////
    /////////////////// AJAX Methods ////////////////////////////
    /////////////////////////////////////////////////////////////

    /**
     * Информация о горении денег
     */
    public function ajax_info_payment_burning()
    {
        $burning_info = lm_bank::me()->burning_info();

        echo json_encode($burning_info);
    }


    public function ajax_get_balloons($param)
    {
        global $DB, $USER;

        $w = "";
        $a = new StdClass();

        if ( !empty($param->period) ) {
            $group = $param->period;

            switch ($group) {
                case 'month':
                    $startmonth = $endmonth = date("m");
                    $startyear = $endyear = date("Y");
                    $days = cal_days_in_month(CAL_GREGORIAN, $endmonth, $endyear);
                    $w = "AND month(date) = {$startmonth} AND year(date) = {$startyear}";
                    break;
                case 'quarter':
                    $endmonth = date("m");
                    $endyear = date("Y");
                    $startyear = $endyear;

                    if ( $endmonth <= 2 ) {
                        $startyear = $endyear - 1;
                    }
                    $startmonth = $endmonth - 2;
                    if ( $startmonth < 10 ) {
                        $startmonth = "0{$startmonth}";
                    }
                    $days = cal_days_in_month(CAL_GREGORIAN, $endmonth, $endyear);
                    $w = "AND date BETWEEN '{$startyear}-{$startmonth}-01' AND '{$endyear}-{$endmonth}-{$days}'";
                    break;
                case 'year':
                    $year = date("Y");
                    $w = "AND year(date) = {$year}";
                    break;
                case 'random':
                    $w = "AND date BETWEEN '{$param->startdate}' AND '{$param->enddate}'";
                    break;
                case 'all':
                    $month = date("m");
                    $year = date("Y");
                    $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                    $w = "AND date BETWEEN '2010-01-01' AND '{$year}-{$month}-{$days}'";
                    break;
            }

            $sql_debet = "SELECT * FROM {lm_bank_account} WHERE userid = {$USER->id} {$w} AND amount > 0 ORDER BY id";
            $sql_credit = "SELECT * FROM {lm_bank_account} WHERE userid = {$USER->id} {$w} AND amount < 0 ORDER BY id";

            $balls = array();
            if ( $payments_debet = $DB->get_records_sql($sql_debet) ) {
                $balls[1] = $this->generation_accounts_for_balls($payments_debet, '+');
            }

            if ( $payments_credit = $DB->get_records_sql($sql_credit) ) {
                $balls[2] = $this->generation_accounts_for_balls($payments_credit, '-');
            }
            if ( !empty($balls[1]) && !empty($balls[2]) ) {
                $balls = array_merge($balls[1], $balls[2]);
            } elseif ( !empty($balls[1]) ) {
                $balls = $balls[1];
            }

            $a->balloons = $balls;
        }
        echo json_encode($a);
    }

    /**
     * Информация о шарике в стакане (о счете)
     * @param $param
     * @throws dml_missing_record_exception
     */
    public function ajax_info_payment($param)
    {
        global $USER, $DB;

        $info = array();
        $balance = 0;
        if ( $param->payments ) {
            $payments = explode(",",$param->payments);
            if ( !empty($payments) ) {
                foreach ( $payments as $id ) {
                    $payment = $DB->get_record_select("lm_bank_account", "id = {$id}");
                    if ( $payment->userid == $USER->id ) {
                        $info[$id]->amount = $payment->amount;
                        $info[$id]->comment = $payment->comment;
                        $balance = $balance + $payment->amount;
                    }
                }
            }
        }
        $info['balance'] = number_format($balance, 0, ',', "'");
        echo json_encode($info);
    }

    public function ajax_data_diagrams($param)
    {
        global $DB, $USER;

        $a = new StdClass();
        $a->series = [];
        $a->xaxis = [];

        $months = array("Янв", "Фев", "Март", "Апр", "Май", "Июнь", "Июль", "Авг", "Сент", "Окт", "Ноя", "Дек");

        $where_amount = "";
        if ( $param->stat == 'income' ) {
            $where_amount = "AND amount > 0";
        } else if ( $param->stat == 'expense' ) {
            $where_amount = "AND amount < 0";
        }

        $cur_year = 2015;
        $cur_month = 7;
        $diagrams = array();

        $series = new StdClass();
        $series->type = 'column';
        $series->data = [];
        $count = 0;
        for ($i = 0; $i < 6; $i++) {
            if ($cur_month + $i < 6) {
                $year = $cur_year - 1;
                $month = $cur_month - 6 + 13 + $i;
            } else {
                $year = $cur_year;
                $month = $cur_month - 6 + $i + 1;
            }
            $sql = "SELECT SUM(amount) as sum FROM {lm_bank_account} WHERE month(date) = {$month} AND year(date) = {$year} {$where_amount}";
            $payment = $DB->get_record_sql($sql);
            if ( !is_null($payment->sum) ) {
                $diagrams[$i]->sum = abs($payment->sum);
                $count++;
            } else {
                $diagrams[$i]->sum = 0;
            }

            $a->xaxis[] = $months[ date("n", strtotime("{$year}-{$month}-01")) - 1 ];
            $series->data[] = array("0", $diagrams[$i]->sum, $month, $year, $param->stat);

            $diagrams[$i]->month = $month;
            $diagrams[$i]->year = $year;
        }
        $a->series[] = $series;

        if( $count >= 3 ) {
            $items = array_values($series->data);
            $series = new StdClass();
            $series->type = 'line';

            // Считаем скользящую среднюю за по всем месяцам, кроме первого и последнего
            $m = array();
            for ($i = 0; $i < $count; $i++) {
                if (isset($items[$i][1]) && isset($items[$i+1][1]) && isset($items[$i+2][1])) {
                    $m[$i+1] = ($items[$i][1] + $items[$i+1][1] + $items[$i+2][1])/3;
                }
            }

            $predict = $m[count($m)] + 1/3 * ($items[$count-1][1] - $items[$count-2][1]);

            $start = $count >= 6 ? $count-6: 6 - $count;
            $series->data = [[$start, $items[$start][1]], [6, $predict]];
            $a->series[] = $series;
        }

        return $a;
    }

    /**
     * Данные для пирога
     */
    public function ajax_get_data_for_month($param)
    {
        global $CFG, $DB;
        $s = array();
        $sum = 0;
        if ( $this->isadmin ) {
            if ( isset($param->month) && $param->month && isset($param->year) && $param->year && isset($param->whereamount) && $param->whereamount ) {
                if ( $param->whereamount == 'income' ) {
                    $where_amount = "AND ba.amount > 0";
                } else if ( $param->whereamount == 'expense' ) {
                    $where_amount = "AND ba.amount < 0";
                }
                $sql = "SELECT DISTINCT(ba.channelid) as channelid FROM {lm_bank_account} ba WHERE month(ba.date) = {$param->month} AND year(ba.date) = {$param->year} {$where_amount}";
                $channels = $DB->get_records_sql($sql);

                foreach ( $channels as $channel ) {
                    $sql = "SELECT
                              SUM(ba.amount) as sum, bc.code as channel_name
                            FROM {lm_bank_account} ba
                            JOIN {lm_bank_channel} bc ON bc.id = ba.channelid
                            WHERE
                              month(ba.date) = {$param->month} AND year(ba.date) = {$param->year} {$where_amount} AND ba.channelid = '{$channel->channelid}'
                    ";
                    $payment = $DB->get_record_sql($sql);
                    if ( !is_null($payment->sum) ) {
                        $obj = new StdClass();
                        $obj->channelid = intval($channel->channelid);
                        $obj->label    = $payment->channel_name;
                        $obj->value    = intval(number_format($payment->sum, 0, ",", ""));
                        $obj->month    = intval($param->month);
                        $obj->year     = intval($param->year);
                        $obj->wamount  = $param->whereamount;
                        $s['data'][] = $obj;
                        $sum = $sum + $obj->value;
                    }
                }
            }
        }

        $trans = array(
            "January"   => "Январь",
            "February"  => "Февраль",
            "March"     => "Март",
            "April"     => "Апрель",
            "May"       => "Май",
            "June"      => "Июнь",
            "July"      => "Июль",
            "August"    => "Август",
            "September" => "Сентябрь",
            "October"   => "Октябрь",
            "November"  => "Ноябрь",
            "December"  => "Декабрь"
        );
        $eng_month = date("F", mktime(0,0,0,$param->month, 1));
        $rus_month = strtr( $eng_month, $trans);

        $s['month'] = $rus_month;
        $s['total'] = $sum;
        echo json_encode($s);
    }

    /**
     * Статистика по каналу
     * @param $param
     * @throws dml_missing_record_exception
     * @throws dml_multiple_records_exception
     */
    public function ajax_statistics_for_channel($param)
    {
        global $DB, $OUTPUT;
        $tpl = "";

        if ( $this->isadmin ) {
            if ( isset($param->month) && $param->month && isset($param->year) && $param->year && isset($param->channelid) && isset($param->wamount) && $param->wamount ) {
                if ( $param->wamount == 'income' ) {
                    $this->tpl->wamount = 'income';
                    $where_amount = "AND amount > 0";
                } else if ( $param->wamount == 'expense' ) {
                    $this->tpl->wamount = 'expense';
                    $where_amount = "AND amount < 0";
                }
                $sql = "SELECT * FROM {lm_bank_account} WHERE month(date) = {$param->month} AND year(date) = {$param->year} {$where_amount} AND channelid = '{$param->channelid}' ";
                $payments = $DB->get_records_sql($sql);
                $array = array("монета", "монеты", "монет");
                foreach ( $payments as $payment ) {
                    $user = lm_user::i($payment->userid);
                    $payments[$payment->id]->username = lm_user::short_name($user);
                    $payments[$payment->id]->userava = $OUTPUT->user_picture($user, array('size' => 39, 'link'=>FALSE, 'alttext'=>FALSE));
                    $payments[$payment->id]->title_money = $this->get_word($payment->amount, $array);
                }
                $this->tpl->payments = $payments;
                $tpl = $this->fetch("/blocks/{$this->type}/tpl/subpage/stat_by_channel.tpl");
            }
        }
        echo $tpl;
    }



    /**
     * Создать транзакцию
     * @param $param
     */
   /* public function ajax_create_transaction($param)
    {
        global $USER;

        $a = new StdClass();
        $a->error = true;
        $a->text  = "Ошибка! Повторите запрос!";

        $lexa = 5;
        $k2 = 2;
        lm_bank::i($lexa)->send($k2 , 10, 'просто так' );

        if ( $this->admin ) {
            if ( isset($param->channel) && $param->channel && isset($param->money) && $param->money && isset($param->userid) && $param->userid && isset($param->instance) && $param->instance ) {
                $lm_bank = lm_bank::i($param->userid);

                if ( $lm_bank->transaction($param->money, $param->channel, $param->instance, $USER->id, $param->comment) ) {
                    if ( $param->money < 0 ) {
                        $a->text  = "Снятие средств прошло успешно!";
                    } else {
                        $a->text  = "Счет пользователя успешно пополнен!";
                    }
                    $a->error = false;
                }
            }
        }
        echo json_encode($a);
    }*/


    public function ajax_add_coins($param)
    {
        if ( !empty($param->userid) ) {
            $tpl = $this->tpl;
            $tpl->channels = lm_bank_channel::get_list();
            $tpl->userid = $param->userid;
            $form = $this->fetch("/blocks/{$this->type}/tpl/subpage/add_coins.tpl");
            echo $form;
        } else {
            echo 'Не указан userid! Обновите страницу и повторите попытку!';
        }
    }

    public function ajax_take_coins($param)
    {
        if ( !empty($param->userid) ) {
            $tpl = $this->tpl;
            $tpl->channels = lm_bank_channel::get_list();
            $tpl->userid = $param->userid;
            $form = $this->fetch("/blocks/{$this->type}/tpl/subpage/take_coins.tpl");
            echo $form;
        } else {
            echo 'Не указан userid! Обновите страницу и повторите попытку!';
        }
    }

    /**
     * Начислить юзеру монет
     * @param $param
     */
    public function ajax_debit($param)
    {
        global $USER;

        $a = new StdClass();
        $a->error = true;
        $a->text  = "Ошибка! Повторите запрос!";

        if ( $this->isadmin ) {
            if ( !empty($param->channel) && isset($param->money) && $param->money &&
                (float)$param->money && !empty($param->userid) && !empty($param->instance)
            ) {

               /* $lexa = 2;
                $k2 = 723;
                if ( $lexa_bank = lm_bank::i($lexa) ) {
                    if ( $lexa_bank->send($k2, $param->money, 'просто так') ) {
                        $a->text = "Счет пользователя успешно пополнен!";
                        $a->error = false;
                    }
                }*/

                $lm_bank = lm_bank::i($param->userid);
                $param->money = (float)$param->money;
                if ( $lm_bank->debit($param->money, $param->channel, $param->instance, $param->comment) ) {
                    $a->text  = "Счет пользователя успешно пополнен!";
                    $a->error = false;
                }
            }
        }
        echo json_encode($a);
    }

    /**
     * Начислить юзеру монет
     * @param $param
     */
    public function ajax_credit($param)
    {
        $a = new StdClass();
        $a->error = true;
        $a->text  = "Ошибка! Повторите запрос!";

        if ( $this->isadmin ) {
            if ( isset($param->channel) && $param->channel && isset($param->money) && $param->money && (float)$param->money && isset($param->userid) && $param->userid && isset($param->instance) && $param->instance ) {
                $lm_bank = lm_bank::i($param->userid);
                $param->money = (float)$param->money;
                if ( $lm_bank->credit($param->money, $param->channel, $param->instance, $param->comment) ) {
                    $a->text  = "Снятие средств прошло успешно!";
                    $a->error = false;
                }
            }
        }
        echo json_encode($a);
    }


    public function ajax_get_instance($param)
    {
        $a = new StdClass();
        $a->error = true;
        $a->text  = "Ошибка! Повторите запрос!";

        if ( $this->isadmin ) {
            if ( isset($param->channel) && $param->channel ) {
                if ( $instances = lm_bank_channel::i($param->channel)->get_instances($param->userid) ) {
                    if ( !empty($instances->data) ) {
                        $a->error = false;
                        $a->text = $instances->text;
                    }
                }
            }
        }
        echo json_encode($a);
    }

    public function ajax_get_list_instance($param)
    {
        $list = array();

        if ( $this->isadmin ) {
            if ( isset($param->code) && $param->code) {
                $list = lm_bank_channel::i($param->code)->get_instances($param->userid);
            }
        }
        return $list;
    }

    public function ajax_testdate($param)
    {
        global $DB, $USER;

        $sql = "SELECT * FROM mdl_user ORDER BY RAND() LIMIT 50";
        $dates = array("2014-12-02", "2015-01-02", "2015-02-02", "2015-03-02", "2015-04-02", "2015-05-02", "2015-06-02", "2015-07-02");
        foreach ( $dates as $date ) {
            $users = $DB->get_records_sql($sql);
            foreach ( $users as $user ) {
                for ( $i = 1; $i < 7; $i++ ) {
                    $money = rand(5, 80);
                    $type = rand (1,2);
                    $channelid = rand(2, 4);
                    $channel = $DB->get_field_select("lm_bank_channel", "code", "id = {$channelid}");
                    $instanceid = rand(1, 100);
                    $comment = "Начисление монет в размере {$money} по каналу {$channel}";
                    if ( $type == 2 ) {
                        $money = -$money;
                        $comment = "Списание монет в размере {$money} по каналу {$channel}";
                    }
                    $result = true;
                    $transaction = $DB->start_delegated_transaction();
                    try {
                        $lm_bank = lm_bank::i($user->id);
                        $dbdata = new StdClass();
                        $dbdata->operatorid = (int)$USER->id;
                        $dbdata->channelid = (int)$channelid;
                        $dbdata->instanceid = (int)$instanceid;
                        $dbdata->date = $date . " 00:00:0{$i}";
                        $dbdata->comment = $comment;

                        $dbdata->userid = $user->id;
                        $dbdata->correspondent = 0;
                        $dbdata->amount = $money;
                        $dbdata->balance = $lm_bank->balance + $money;

                        $lm_bank->balance = $dbdata->balance;

                        $result = $result && $DB->insert_record("lm_bank_account", $dbdata);

                        $transaction->allow_commit();
                    } catch (dml_write_exception $e) {
                        try {
                            $transaction->rollback($e);
                        } catch (dml_write_exception $e) {
                            $e->getMessage();
                            $result = false;
                        }
                    }
                }
            }
        }
        echo true;
    }

}