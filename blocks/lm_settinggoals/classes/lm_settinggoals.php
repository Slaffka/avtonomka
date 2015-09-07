<?php
/**
 * данный класс обробатывает действия в данном блоке
 *
 * @author   Andrej Schartner <schartner@as-code.eu>
 */
class lm_settinggoals {
    /**
     * константы для статусов
     */
    const STATE_NEW                       = 0; // новый
    const STATE_CORRECT                   = 1; // находятся в корректировке
    const STATE_AGREEMENT                 = 2; // на согласование
    const STATE_AGREEMENT_REJECT          = 3; // отправлено обратно на корректировку
    const STATE_AGREEMENT_SUCCESS         = 4; // согласовано

    /**
     * константы для таблиц в базе
     */
    const TABLE_GOALSETTING_PLAN          = 'lm_settinggoals_plan';
    const TABLE_GOALSETTING_PLAN_KPI      = 'lm_settinggoals_plan_kpi';
    const TABLE_GOALSETTING_TOP           = 'lm_settinggoals_top';
    const TABLE_GOALSETTING_PHASE         = 'lm_settinggoals_phase';
    const TABLE_GOALSETTING_DELAY         = 'lm_goalsetting_delay';
    const TABLE_GOALSETTING_AV_COST       = 'lm_settinggoals_averagekgcost';
    const TABLE_GOALSETTING_EXPORTS       = 'lm_goalsetting_exports';
    const TABLE_GOALSETTING_TOP_LIST      = 'lm_goalsetting_top_list';
    const TABLE_POSITION_XREF             = 'lm_position_xref';
    const TABLE_POST                      = 'lm_post';
    const TABLE_POSITION                  = 'lm_position';
    const TABLE_USER                      = 'user';
    const TABLE_KPI                       = 'lm_kpi';
    const TABLE_TRADE_OUTLETS             = 'lm_trade_outlets';
    const TABLE_NOMENCLATURE              = 'lm_nomenclature';

    /**
     * ид кпи которые не группируются а считаются за одну штуку
     * @var array
     */
    protected static $_dont_group_kpi = [3,4,8,9];

    /**
     * ид кпи которые не группируются а считаются за одну штуку
     * @var array
     */
    protected static $acl = [];

    /**
     * инициализация прав актуального пользователя
     *
     * @param integer ид пользователя
     */
    public static function get_user_acl($userid) {
        global $DB;

        // выдаём сохранённого пользователя если он уже инициализирован
        if (isset(self::$acl[$userid]))
            return self::$acl[$userid];

        // достаём пользователя из базы
        $sql = "SELECT p.postid, p.id AS posid "
             . "FROM {" . self::TABLE_POSITION . "} AS `p`, {" . self::TABLE_POSITION_XREF . "} AS `xref` "
             . "WHERE `xref`.`posid` = `p`.`id` "
             . "AND `xref`.`userid` = ? "
             . "LIMIT 0, 1";
        $res = $DB->get_record_sql($sql, [$userid]);

        // обьект пользователя
        self::$acl[$userid] = [
            'subpage' => [
                'index'      => true,
                'today_plan' => true,
                'today'      => true,
                'cleanup_db' => true,
            ],
            'post' => [
                'userid'  => $userid,
                'posid'   => $res->posid,
                'postid'  => $res->postid,
                'is_sv'   => $res->postid == 3,
                'is_tp'   => $res->postid == 1,
                'is_mod'  => lm_user::is_admin()
            ]
        ];

        // является ли актуальный пользователь супервайзером?
        if (self::$acl[$userid]['post']['is_sv']) {
            // ставим ид позиции актуального акаунта
            self::$acl[$userid]['post']['sv_posid'] = self::$acl[$userid]['post']['posid'];
        } else if (self::$acl[$userid]['post']['is_tp']){
            // тостаём данные о супервайзере актуального торгового представителя
            $sql = "SELECT p.parentid "
                 . "FROM {" . self::TABLE_POSITION . "} AS `p` "
                 . "WHERE `p`.`id` = ? "
                 . "LIMIT 0, 1";
            $res = $DB->get_record_sql($sql, [self::$acl[$userid]['post']['posid']]);
            if ($res) {
                // прописываем ид супервайзера
                self::$acl[$userid]['post']['sv_posid'] = $res->parentid;
            }
        }

        // если ид супервайзера не поставлено прописываем 0
        if (!isset(self::$acl[$userid]['post']['sv_posid']))
            self::$acl[$userid]['post']['sv_posid'] = 0;

        return self::$acl[$userid];
    }

    /**
     * высчитываем процент изменения между двумя параметрами
     *
     * @param float $orig      исходное значение
     * @param float $new_value новое значение
     */
    protected static function calc_percent($orig, $new_value) {
        return (float) $orig ? floor(100 / $orig * $new_value) : 100;
    }

    /**
     * метод достаёт список супервайзеров
     *
     * @param integer $sv_posid  ид текущего супервайзера
     * @param integer $page      номер страници результата
     * @param integer $per_page  количество результатов на одной странице
     */
    public static function get_superviser_list($sv_posid, $page = 1, $per_page = 3) {
        // достаём глобальный необходимый функционал
        global $DB, $OUTPUT;

        // достать ид поста супервеизеров
        $sql = "SELECT `id` FROM {" . self::TABLE_POST . "} WHERE `name` = 'СВ' LIMIT 1";
        $res = $DB->get_record_sql($sql);

        // если пост не найден, выходим
        if (!$res)
            return [[], [], []];

        // ставим пост ид
        $post_id = $res->id;

        // обробатываем информацию о страничках
        $page = $page < 1 ? 1 : $page * 1;
        $per_page = $per_page < 1 ? 3 : $per_page * 1;

        // информация для запросов
        $select_sql = "SELECT `u`.`id`, `u`.`id` AS `userid`, `u`.`firstname`, `u`.`lastname`, `u`.`picture`, `u`.`imagealt`"
             . ", `t`.`count` AS `top_count`, `t`.`current` AS `top_current` "
             . ", `t`.`next` AS `top_next`, `p`.`id` AS `posid`, `p`.`areaid` ";
        $from_sql = "FROM {" . self::TABLE_POSITION . "} AS `p` "
              . "LEFT JOIN {" . self::TABLE_GOALSETTING_TOP . "} AS `t` ON `t`.`posid` = `p`.`id` "
              . ", {" . self::TABLE_POSITION_XREF . "} AS `xref` "
              . ", {" . self::TABLE_USER . "} AS `u` ";
        $where_sql = "WHERE `p`.`postid` = ? AND `xref`.`posid` = `p`.`id` "
               . "AND `xref`.`userid` > 0 "
               . "AND `xref`.`userid` = `u`.`id` ";

        $list = [];
        $fields = [];

        // запрос к базе для получения лучшего
        $sql = $select_sql
             . $from_sql
             . $where_sql
             #. "AND (`t`.`current` > 0 OR `t`.`next` > 0)"
             . "ORDER BY `t`.`current` DESC "
             . "LIMIT 0, 1";
        $res = array_values($DB->get_records_sql($sql, [$post_id]));

        if (isset($res[0]->posid) && $res[0]->posid != $sv_posid) {
            // лучший супервайзер не является тешущем, достаём текущего
            $sql = $select_sql
                 . $from_sql
                 . $where_sql
                 . "AND `p`.`id`  = ? "
                 . "LIMIT 0, 1";
            $r = $DB->get_record_sql($sql, [$post_id, $sv_posid]);
            // если он найден то добавляем в результат
            if ($r)
                $res[] = $r;
        }

        // выходим если нету пользователей
        if (!$res)
            return [[], [], []];

        // проходим результат из базы и формируем информацию как нам нада
        foreach ($res as $user) {
            list($u, $fields) = self::get_user_row($user, $fields);
            $u->ava = $OUTPUT->user_picture($user, array('size' => 45, 'link' => TRUE, 'alttext' => FALSE, 'popup' => TRUE));
            $list[] = $u;
        }
        $res = [];
        $list[count($list)-1]->lead = true;

        // посчитать топ супервайзеров (актуальный и лидер)
        $results_top = count($list);

        // вытягиваем актуального
        $current_superviser = $list[count($list)-1];

        // настройка параметров для пагинатора
        $get_count = $per_page;
        if ($page == 1) {
            // на первой страничке берём с 0
            $from = 0;
            // отнимаем найденых топ супервайзеров
            $get_count -= $results_top;
        } else {
            // посчитываем начало для актуальной страницы и отнимаем из них
            // количество топ супервайзеров с первой страници
            $from = $page * $per_page - $per_page - $results_top;

            // удоляем найденых супервайзеров из списка
            $list = [];
        }

        $sql = "SELECT * FROM {" . self::TABLE_POSITION . "} WHERE id = ?";
        $pos = $DB->get_record_sql($sql, [$current_superviser->posid]);

        // далее – супервайзеры, относящиеся к команде текущего супервайзера в алфавитном порядке их ФИО
        // запрос для коунта в базе
        $sql = "SELECT COUNT(DISTINCT `u`.`id`) AS `count` "
             . $from_sql
             . $where_sql
             . "AND `p`.`parentid` = ? AND p.id != ? ";
        $res_count = $DB->get_record_sql($sql, [$post_id, $pos->parentid, $current_superviser->posid]);
        if ($res_count->count > 0) {
            // достаём из базы найденных
            $sql = $select_sql
                 . $from_sql
                 . $where_sql
                 . "AND `p`.`parentid` = ? AND p.id != ? "
                 . "GROUP BY `u`.`id` ORDER BY `u`.`firstname` ASC, `u`.`lastname` ASC ";
                 #. "LIMIT " . $from . ", " . $get_count;
            $res = $DB->get_records_sql($sql, [$post_id, $pos->parentid, $current_superviser->posid]);
        } else {
            // если таких нет, то выводятся супервайзеры, относящиеся к ФО текущего супервайзера
            $sql = "SELECT COUNT(DISTINCT `u`.`id`) AS `count` "
                 . $from_sql
                 . $where_sql
                 . "AND `p`.`areaid` = ? AND p.id != ?";
            $res_count = $DB->get_record_sql($sql, [$post_id, $current_superviser->areaid, $sv_posid]);
            if ($res_count->count > 0) {
                // достаём из базы найденных
                $sql = $select_sql
                     . $from_sql
                     . $where_sql
                     . "AND `p`.`areaid` = ? "
                     . "GROUP BY `u`.`id` ORDER BY `u`.`firstname` ASC, `u`.`lastname` ASC ";
                     #. "LIMIT " . $from . ", " . $get_count;
                $res = $DB->get_records_sql($sql, [$post_id, $current_superviser->areaid]);
            }
        }

        // проходим результат из базы и формируем информацию как нам нада
        foreach ($res as $user) {
            list($u, $fields) = self::get_user_row($user, $fields);
            $u->ava = $OUTPUT->user_picture($user, array('size' => 45, 'link' => TRUE, 'alttext' => FALSE, 'popup' => TRUE));
            $list[] = $u;
        }

        // готовим даные для пагинатора
        $pager_data = [
            'count' => 1,#ceil(($res_count->count + $results_top) / $per_page),
            'current' => 1,#$page,
        ];
        return [$list, $fields, $pager_data];
    }

    /**
     * метод достаёт детали о кпи по пользователю и формирует пользователя
     *
     * @param object $user   обьект пользователя
     * @param array  $fields поля/кпи список
     */
    protected static function get_user_row($user, $fields) {
        $u            = new StdClass();
        $u->userid    = $user->userid * 1;
        $u->posid     = $user->posid * 1;
        $u->areaid    = $user->areaid * 1;
        $u->name      = trim($user->firstname . ' ' . $user->lastname);
        $u->is_top    = $user->top_current == 1;
        $u->top_count = $user->top_count * 1;
        $u->lead      = $user->top_next == 1;
        list(
            $fields,
            $u->kpis
        )             = self::get_user_kpi($user->posid, $user->userid, $fields);
        return [$u, $fields];
    }

    /**
     * метод достаёт детали о кпи
     *
     * @param integer $posid  позиция пользователя
     * @param integer $userid ид пользователя
     * @param array   $fields поля/кпи список
     */
    protected static function get_user_kpi($posid, $userid, $fields = []) {
        $kpi_list     = new lm_kpi_list($posid, $userid);
        $kpis         = $kpi_list->get_latest();
        $result       = [];
        foreach ($kpis as $kpi) {
            if (!isset($fields[$kpi->id]) && !in_array($kpi->name, $fields))
                $fields[$kpi->id] = $kpi->name;
            switch ($kpi->uom) {
                // case 'руб':
                //     $kpi->plan    = number_format($kpi->plan / 1000, 2, '.', ' ');
                //     $kpi->fact    = number_format($kpi->fact / 1000, 2, '.', ' ');
                //     $kpi->predict = number_format($kpi->predict / 1000, 2, '.', ' ');
                //     $kpi->uom     = 'т.р';
                //     break;
                // case 'кг':
                //     $kpi->plan    = number_format($kpi->plan / 1000, 2, '.', ' ');
                //     $kpi->fact    = number_format($kpi->fact / 1000, 2, '.', ' ');
                //     $kpi->predict = number_format($kpi->predict / 1000, 2, '.', ' ');
                //     $kpi->uom     = 'т.';
                //     break;
                case 'шт':
                    $kpi->plan    = ceil($kpi->plan);
                    $kpi->fact    = ceil($kpi->fact);
                    $kpi->predict = ceil($kpi->predict);
                    break;
                default:
                    break;
            }
            $kpi->fact_percent    = self::calc_percent(
                $kpi->plan,
                $kpi->fact
            );
            $kpi->predict_percent = self::calc_percent(
                $kpi->plan,
                $kpi->predict
            );
            $result[$kpi->name]    = $kpi;
        }
        return [$fields, $result];
    }

    /**
     * метод достаёт список торговых представителей
     *
     * @param integer $posid     ид текущего супервайзера
     * @param integer $page      номер страници результата
     * @param integer $per_page  количество результатов на одной странице
     */
    public static function get_superviser_tp_list($posid, $page = 1, $per_page = 3) {
        // достаём глобальный необходимый функционал
        global $DB, $OUTPUT;

        // обробатываем информацию о страничках
        $page = $page < 1 ? 1 : $page * 1;
        $per_page = $per_page < 1 ? 3 : $per_page * 1;

        // данные для пагинатора актуальной страници
        $get_count = $per_page;
        $from = $page * $per_page - $per_page;

        // запрос к базе для получения ид супервеизера
        $sql = "SELECT `u`.`id` AS `userid`, `p`.`id` AS `posid` "
             . "FROM {" . self::TABLE_POSITION . "} AS `p`, {" . self::TABLE_POSITION_XREF . "} AS `xref` "
             . ", {" . self::TABLE_USER . "} AS `u` "
             . "WHERE `p`.`id` = ? AND `xref`.`posid` = `p`.`id` "
             . "AND `xref`.`userid` > 0 "
             . "AND `xref`.`userid` = `u`.`id` "
             . "LIMIT 0, 1";
        $current_superviser = $DB->get_record_sql($sql, [$posid]);
        if (!$current_superviser) {
            return [[], [], [], []];
        }

        $list                = [];
        $fields              = [];
        $total_kpis          = [];

        list($fields, $kpis) = self::get_user_kpi($posid, $current_superviser->userid, $fields);

        foreach ($kpis as $id => $kpi) {
            $t_kpi                = new StdClass();
            $t_kpi->value         = $kpi->plan;
            $t_kpi->uom           = $kpi->uom;
            $total_kpis[$kpi->id] = $t_kpi;
        }

        // достать ид поста торгового представителя
        $sql = "SELECT `id` FROM {" . self::TABLE_POST . "} WHERE `name` = 'ТП' LIMIT 1";
        $res = $DB->get_record_sql($sql);

        if (!$res)
            return [[], [], [], []];

        // ставим пост ид
        $post_id = $res->id;

        // информация для запросов
        $select_sql = "SELECT `u`.`id`, `u`.`id` AS `userid`, `u`.`firstname`, `u`.`lastname`, `u`.`picture`, `u`.`imagealt` "
             . ", `t`.`count` AS `top_count`, `t`.`current` AS `top_current` "
             . ", `t`.`next` AS `top_next`, `p`.`id` AS `posid`, `p`.`areaid` ";
        $from_sql = "FROM {" . self::TABLE_POSITION . "} AS `p` "
              . "LEFT JOIN {" . self::TABLE_GOALSETTING_TOP . "} AS `t` ON `t`.`posid` = `p`.`id` "
              . ", {" . self::TABLE_POSITION_XREF . "} AS `xref` "
              . ", {" . self::TABLE_USER . "} AS `u` ";
        $where_sql = "WHERE `p`.`postid` = ? AND `xref`.`posid` = `p`.`id` "
               . "AND `xref`.`userid` > 0 "
               . "AND `xref`.`userid` = `u`.`id` "
               . "AND `p`.`parentid` = ? ";

        // считаем количество торговых представителей
        $sql = "SELECT COUNT(DISTINCT `u`.`id`) AS `count` "
             . $from_sql
             . $where_sql;
        $res_count = $DB->get_record_sql($sql, [$post_id, $current_superviser->posid]);

        // достаём торговых представителей
        $sql = $select_sql
             . $from_sql
             . $where_sql
             . "GROUP BY `u`.`id` ORDER BY `u`.`firstname` ASC, `u`.`lastname` ASC ";
             #. "LIMIT " . $from . ", " . $get_count;

        $res = $DB->get_records_sql($sql, [$post_id, $current_superviser->posid]);

        $list = [];
        // проходим резултат из базы и формируем информацию как нам нада
        foreach ($res as $user) {
            list($u, $fields) = self::get_user_row($user, $fields);
            $u->ava = $OUTPUT->user_picture($user, array('size' => 45, 'link' => TRUE, 'alttext' => FALSE, 'popup' => TRUE));
            $list[] = $u;
        }

        // готовим даные для пагинатора
        $pager_data = [
            'count' => 1,#ceil($res_count->count / $per_page),
            'current' => 1#$page,
        ];
        return [$total_kpis, $list, self::sort_fields($fields), $pager_data];
    }

    protected static function sort_fields($fields) {
        $res = [];
        if (in_array("Тоннаж", $fields)) {
            $res[] = "Тоннаж";
        }
        if (in_array("Выручка", $fields)) {
            $res[] = "Выручка";
        }
        if (in_array("ММЛ", $fields)) {
            $res[] = "ММЛ";
        }
        foreach ($fields as $field) {
            if (in_array($field, $res)) {
                continue;
            }
            $res[] = $field;
        }
        return $res;
    }

    /**
     * метод достаёт пользователя из базы
     *
     * @param integer $userid ид пользователя
     * @param integer $postid пост ид
     */
    public static function get_current_user($userid, $postid) {
        // достаём глобальный необходимый функционал
        global $DB;
        $sql = "SELECT `u`.`id` AS `userid`, `p`.`id` AS `posid` "
             . "FROM {" . self::TABLE_POSITION . "} AS `p`, {" . self::TABLE_POSITION_XREF . "} AS `xref` "
             . ", {" . self::TABLE_USER . "} AS `u`, {" . self::TABLE_POST . "} AS `post` "
             . "WHERE `xref`.`posid` = `p`.`id` "
             . "AND `xref`.`userid` = ? AND `p`.`postid` = ? "
             . "AND `xref`.`userid` = `u`.`id` "
             . "LIMIT 0, 1";
        return $DB->get_record_sql($sql, [$userid, $postid]);
    }

    /**
     * метод достаёт среднею стоимость на 1 кг тоннажа из базы
     *
     * @param integer $posid ид позиции
     * @param integer $time  время
     */
    public static function get_pos_av_cost($posid, $time) {
        // достаём глобальный необходимый функционал
        global $DB;

        $sql = "SELECT *
                FROM {" . self::TABLE_GOALSETTING_AV_COST . "}
                WHERE positionid = ?
                AND FROM_UNIXTIME(date, '%Y-%m-%d') = ?
                ORDER BY date DESC";
        $res = $DB->get_record_sql(
            $sql,
            [
                $posid,
                date("Y-m-d", $time)
            ]
        );
        if ($res)
            return $res;

        // если не найден в базе на данное время берётся последняя стоимость
        $sql = "SELECT *
                FROM {" . self::TABLE_GOALSETTING_AV_COST . "}
                WHERE positionid = ?
                ORDER BY date DESC";
        return $DB->get_record_sql( $sql, [$posid] );
    }

    /**
     * метод достаёт кпи одного этапа по ид позиции
     *
     * @param integer $posid ид позиции
     * @param integer $time  время
     * @param string  $stage этап
     */
    public static function get_kpi_stat($posid, $time, $stage) {
        // достаём глобальный необходимый функционал
        global $DB;

        $sql = "SELECT CONCAT(p.id, '-', kpi.id,'-' , p.placeid) AS uid, kpi.name, kpi.id, kpi.uom,
                SUM(IF(kpi.id in (" . implode(",", self::$_dont_group_kpi) .
                    "), IF(pk.value != 0, 1, 0), pk.value)) AS value
                FROM {" . self::TABLE_GOALSETTING_PLAN . "} AS p,
                {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pk,
                {" . self::TABLE_KPI . "} AS kpi
                WHERE pk.planid = p.id
                AND p.positionid = ?
                AND FROM_UNIXTIME(p.date, '%Y-%m-%d') = ?
                AND pk.stage = ?
                AND kpi.id = pk.kpiid
                GROUP BY p.id, pk.kpiid
                ";

        $res_db = $DB->get_records_sql(
            $sql,
            [
                $posid,
                date("Y-m-d", $time),
                $stage
            ]
        );

        $res = [];
        foreach ($res_db as $kpi) {
            if (in_array($kpi->id, self::$_dont_group_kpi) && $kpi->value > 1)
                $kpi->value = 1;
            if (!isset($res[$kpi->name])) {
                unset($kpi->uid);
                $res[$kpi->name] = $kpi;
                continue;
            }
            $res[$kpi->name]->value += $kpi->value;
        }
        // если выручка = 0 то вычисляем по средней стоимости за 1 кг тоннажа
        if (isset($res['Выручка']) && $res['Выручка']->value == 0) {
            $r = self::get_pos_av_cost($posid, $time, $stage);
            if ($r) {
                $res['Выручка']->value = $res['Тоннаж']->value * $r->value;
            }
        }
        return $res;
    }

    /**
     * метод достаёт кпи всех этапов
     *
     * @param integer $posid      ид позиции
     * @param integer $time       время
     * @param boolean $is_sv      является ли ид позиции супервайзером
     * @param integer $plan_state статус плана
     */
    protected static function get_tp_total_stats($posid, $time, $is_sv = false, $plan_state = null){
        // достаём глобальный необходимый функционал
        global $DB;

        $auto_list      = [];
        $correct_list   = [];
        $fact_list      = [];
        $kpi_list       = [];

        // если пользователь супервайзер достаём статистику от всех его торговых представителей
        if ($is_sv) {
            $pos_sql = "AND p.positionid in (SELECT id FROM {" . self::TABLE_POSITION . "} WHERE parentid = ?) ";
        } else {
            $pos_sql = "AND p.positionid = ? ";
        }

        $sql = "SELECT CONCAT(p.id, '-', kpi.id,'-' , p.placeid) AS uid, kpi.name, kpi.id, kpi.uom,
                SUM(ROUND(IF(kpi.id in (" . implode(",", self::$_dont_group_kpi) . "), IF(pk.value > 0,1,0), pk.value), 1)) as plan,
                SUM(ROUND(
                    IF(". (is_null($plan_state) ? '1 = 1' : 'p.state = ' . (int)$plan_state) .",
                    IF(kpi.id in (" . implode(",", self::$_dont_group_kpi) . "),IF(pkc.value IS NULL, IF(p.state = 0, 1, 0), IF(pk.value > 0,1,

                        IF(pko.value IS NULL, 0, IF(pko.value > 0,1,0))
                        )),
                    IF(pkc.value IS NULL, pk.value, pkc.value))
                    ,0)
                ,1)) as correct,
                SUM(ROUND(IF(kpi.id in (" . implode(",", self::$_dont_group_kpi) . "),IF(pkf.value IS NULL, IF(p.state = 0, 1, 0), IF(pkf.value > 0,1,0)),
                    IF(pkc.value IS NULL, 0, pkf.value)),1)) as fact
                , SUM(ac.value) / COUNT(ac.value) AS avc
                FROM {" . self::TABLE_GOALSETTING_PLAN . "} AS p
                LEFT JOIN {" . self::TABLE_GOALSETTING_AV_COST . "} AS ac ON (
                    ac.positionid = p.positionid
                    AND FROM_UNIXTIME(ac.date, '%Y-%m-%d') = FROM_UNIXTIME(p.date, '%Y-%m-%d')
                ),
                {" . self::TABLE_KPI . "} AS kpi,
                {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pk
                LEFT JOIN {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pkc ON
                (pkc.planid = pk.planid AND pkc.kpiid = pk.kpiid
                AND pkc.stage = 'correct')
                LEFT JOIN {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pkf ON
                (pkf.planid = pk.planid AND pkf.kpiid = pk.kpiid
                AND pkf.stage = 'fact')
                LEFT JOIN {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pko ON
                (pko.planid = pk.planid AND pko.kpiid = pk.kpiid
                AND pko.stage = 'old')
                WHERE pk.planid = p.id
                " . $pos_sql . "
                AND FROM_UNIXTIME(p.date, '%Y-%m-%d') = ?
                AND pk.stage = 'plan'
                AND kpi.id = pk.kpiid
                GROUP BY p.id, pk.kpiid";
try {

        $kpi_stat = $DB->get_records_sql(
            $sql,
            [
                $posid,
                date("Y-m-d", $time)
            ]
        );
} catch (Exception $e) {

// error_log(print_r([$e],true));
}
        $kpi_stat_av = self::get_available_kpis($time);

        if (empty($kpi_stat)) {
            $kpi_stat = $kpi_stat_av;
        }

        // обрабатываем результат из базы
        foreach ($kpi_stat as $kpi) {
            // состовляем список доступных кпи
            if (!isset($kpi_list[$kpi->id]) && !in_array($kpi->name, $kpi_list))
                $kpi_list[$kpi->id] = $kpi->name;

            // является ли параметер выручкой или тоннажом?
            if ($kpi->name == 'Выручка' && isset($kpi_stat_av['Тоннаж']->id)) {
                $uid = explode("-", $kpi->uid);
                if (count($uid) >= 3) {
                    // смотрим по параметрам тоннаж ид данного торговой точки
                    $uid[1] = $kpi_stat_av['Тоннаж']->id;
                    $curr_tonage = $kpi_stat[implode("-", $uid)];
                    if (isset($curr_tonage->avc) && $curr_tonage->avc > 0) {
                        // проходим все этапы
                        foreach (['plan', 'correct', 'fact'] as $type) {
                            if (($kpi->{$type} * 1) > 0) {
                                // выходим так как выручка у данной торговой точки уже есть
                                continue;
                            }
                            // вычисляем выручку по средней стоимости
                            $kpi->{$type} = $curr_tonage->{$type} * $curr_tonage->avc;
                        }
                    }
                }
            }

            // приводим кпи в правильную форму
            $kpi = self::get_tp_place_list_format_kpi($kpi);
            unset($kpi->uid);

            // ставит 1 в план если кпи находится в списке кпи которых нельзя группировать и является > 0
            if (in_array($kpi->id, self::$_dont_group_kpi) && $kpi->plan > 1)
                $kpi->plan = 1;

            // ставит 1 в коррекцию если кпи находится в списке кпи которых нельзя группировать и является > 0
            if (in_array($kpi->id, self::$_dont_group_kpi) && $kpi->correct > 1)
                $kpi->correct = 1;

            // формируем обьект для автоматического подсчёта
            $tmp                      = new StdClass();
            $tmp->name                = $kpi->name;
            $tmp->id                  = $kpi->id;
            $tmp->uom                 = $kpi->uom;
            $tmp->value               = $kpi->plan;
            $tmp->avc                 = $kpi->avc;
            if (!isset($auto_list[$kpi->name])) {
                $auto_list[$kpi->name]    = $tmp;
            } else {
                $auto_list[$kpi->name]->value    += $tmp->value;
            }

            // формируем обьект для корректировки
            $tmpc                      = new StdClass();
            $tmpc->name                = $kpi->name;
            $tmpc->id                  = $kpi->id;
            $tmpc->uom                 = $kpi->uom;
            $tmpc->value               = $kpi->correct;
            $tmpc->avc                 = $kpi->avc;
            $tmpc->direction           = null;
            if (!isset($correct_list[$kpi->name])) {
                $correct_list[$kpi->name]    = $tmpc;
            } else {
                $correct_list[$kpi->name]->value    += $tmpc->value;
            }

            // отмечаем изменения с автоматического подсчёта в коррекции
            if ($correct_list[$kpi->name]->value > $auto_list[$kpi->name]->value)
                $correct_list[$kpi->name]->direction = 'up';
            else if ($correct_list[$kpi->name]->value < $auto_list[$kpi->name]->value)
                $correct_list[$kpi->name]->direction = 'down';
            else
                $correct_list[$kpi->name]->direction = null;

            // формируем обьект для факта
            $tmpf                      = new StdClass();
            $tmpf->name                = $kpi->name;
            $tmpf->id                  = $kpi->id;
            $tmpf->uom                 = $kpi->uom;
            $tmpf->value               = $kpi->fact_list;
            $tmpf->avc                 = $kpi->avc;
            $tmpf->direction           = null;
            if (!isset($fact_list[$kpi->name])) {
                $fact_list[$kpi->name]    = $tmpf;
            } else {
                $fact_list[$kpi->name]->value    += $tmpf->value;
            }

            // отмечаем изменения с подсчёта корректировки в факт
            if ($fact_list[$kpi->name]->value > $correct_list[$kpi->name]->value)
                $fact_list[$kpi->name]->direction = 'up';
            else if ($fact_list[$kpi->name]->value < $correct_list[$kpi->name]->value)
                $fact_list[$kpi->name]->direction = 'down';
            else
                $fact_list[$kpi->name]->direction = null;
        }

        // корректируем изменение выручки (корректировка => автоматический подсчёт)
        if ($correct_list['Выручка']->value > $auto_list['Выручка']->value)
            $correct_list['Выручка']->direction = 'up';
        else if ($correct_list['Выручка']->value < $auto_list['Выручка']->value)
            $correct_list['Выручка']->direction = 'down';
        else
            $correct_list['Выручка']->direction = null;

        // корректируем изменение выручки (факт => корректировка)
        if ($fact_list['Выручка']->value > $correct_list['Выручка']->value)
            $fact_list['Выручка']->direction = 'up';
        else if ($fact_list['Выручка']->value < $correct_list['Выручка']->value)
            $fact_list['Выручка']->direction = 'down';
        else
            $fact_list['Выручка']->direction = null;
        return [$auto_list, $correct_list, $fact_list, self::sort_fields($kpi_list)];
    }

    /**
     * метод достаёт список торговых точек к торговому представителем
     *
     * @param integer $userid     ид пользователя
     * @param integer $time       время
     * @param boolean $page       страница
     * @param integer $per_page   количество результата на одну страницу
     * @param integer $show_state какой статус вытащить из базы
     */
    public static function get_tp_place_list($userid, $time, $page = 1,
        $per_page = 8, $show_state = self::STATE_CORRECT) {
        // достаём глобальный необходимый функционал
        global $DB;

        // обробатываем информацию о страничках
        $show_last_page = $page < 0;
        $page           = $page < 1 ? 1 : $page * 1;
        $per_page       = $per_page < 1 ? 8 : $per_page * 1;

        // данные для пагинатора актуальной страници
        $get_count      = $per_page;
        $from           = $page * $per_page - $per_page;
        $from           = $from > 0 ? $from : 0;

        // инициализация пустого резултата
        $auto_list      = [];
        $correct_list   = [];
        $place_list     = [];
        $status         = 0;
        $kpi_list       = [];

        $current_user   = self::get_current_user($userid, 1);

        // выходим если данный пользователь не найден
        if (!$current_user) {
            return [$auto_list, $correct_list, $status, $place_list, $kpi_list, $pager_data];
        }

        // достаём общую статистику для данного пользователя
        list(
            $auto_list,
            $correct_list,
            $fact_list,
            $kpi_list
        ) = self::get_tp_total_stats(
            $current_user->posid,
            $time
        );

        // достаём актуальный статус корректировки
        $sql = "SELECT p.state
                FROM {" . self::TABLE_GOALSETTING_PLAN . "} AS p
                WHERE p.positionid = ?
                AND FROM_UNIXTIME(p.date, '%Y-%m-%d') = ?
                ORDER BY p.state ASC
                LIMIT 0, 1";
        $res_state = $DB->get_record_sql($sql,
            [$current_user->posid, date("Y-m-d", $time)]);

        // если статус не найден ставим что нету плана в базе
        $no_plan = false;
        if (!$res_state) {
            $no_plan = true;
        }

        // прописываем статус
        $status = isset($res_state->state) ? $res_state->state : self::STATE_NEW;

        // достаём количество торговых точек в плане
        $sql = "SELECT COUNT(*) AS count
                FROM {" . self::TABLE_GOALSETTING_PLAN . "} AS p
                WHERE p.positionid = ?
                AND FROM_UNIXTIME(p.date, '%Y-%m-%d') = ?
                AND p.state = ?";
        $res_count = $DB->get_record_sql($sql,
            [$current_user->posid, date("Y-m-d", $time), $show_state]);

        // ставим параметры для последней страници если показ последней страницы назначен
        if($show_last_page) {
            $page = ceil($res_count->count / $per_page);
            $from = $page * $per_page - $per_page;
            $from = $from > 0 ? $from : 0;
        }

        // достаём список торговых точек из базы
        $sql = "SELECT p.id AS planid, pl.name, pl.address, p.comment, p.positionid
                FROM {" . self::TABLE_GOALSETTING_PLAN . "} AS p,
                {" . self::TABLE_TRADE_OUTLETS . "} AS pl
                WHERE p.positionid = ?
                AND p.placeid = pl.id
                AND FROM_UNIXTIME(p.date, '%Y-%m-%d') = ?
                AND p.state = ?
                LIMIT " . $from . ", " . $get_count;
        $res_place_list = $DB->get_records_sql($sql,
            [$current_user->posid, date("Y-m-d", $time), $show_state]);

        // достаём из базы среднею стоимость
        $av_cost = self::get_pos_av_cost($current_user->posid, $time);

        // формируем результат для списка торговых точек
        foreach ($res_place_list as $place) {
            // добавляем в список торговых точек
            $place_list[] = self::get_place_stats($place, $time, $av_cost);
        }

         // готовим даные для пагинатора
        $pager_data = [
            'count' => ceil($res_count->count / $per_page),
            'current' => $page,
        ];
        return [$auto_list, $correct_list, $status, $place_list, self::sort_fields($kpi_list), $no_plan, $pager_data];
    }

    public static function get_place_stats($place, $time, $av_cost)
    {
        global $DB;
        // достаём кпи данные
        $sql = "SELECT pk.id AS pkpiid, kpi.id, kpi.name, kpi.uom, pk.value,
                 pk.stage
                FROM {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pk,
                {" . self::TABLE_KPI . "} AS kpi
                WHERE pk.planid = ?
                AND kpi.id = pk.kpiid
                ORDER BY pk.id";

        $place_kpi_list = $DB->get_records_sql($sql, [$place->planid]);

        // создаём список кпи если он отсутствует на данную торговую точку
        if (empty($place_kpi_list)) {
            $av_kpis = self::get_available_kpis($time);
            foreach ($av_kpis as $akpi) {
                $nkpi = new StdClass();
                $nkpi->planid = $place->planid;
                $nkpi->kpiid  = $akpi->id;
                $nkpi->stage  = 'plan';
                $nkpi->value  = 0;
                $DB->insert_record(self::TABLE_GOALSETTING_PLAN_KPI, $nkpi);
            }
            $place_kpi_list = $DB->get_records_sql($sql, [$place->planid]);
        }

        // формируем список кпи к торговой точке
        $place->kpis = [];

        foreach ($place_kpi_list as $kpi) {
            // формируем список доступных кпи
            if (!isset($kpi_list[$kpi->id]) && !in_array($kpi->name, $kpi_list))
                $kpi_list[$kpi->id] = $kpi->name;

            // формируем данный кпи
            $kpi = self::get_tp_place_list_format_kpi($kpi);

            // добавляем если кпи нету у данной торговой точки
            if (!isset($place->kpis[$kpi->name]))
                $place->kpis[$kpi->name] = $kpi;

            // ставим план = 0 если он не настроен у данного кпи
            if (!isset($place->kpis[$kpi->name]->plan)) {
                $place->kpis[$kpi->name]->plan = 0;
            }

            // добавляем кпи к уже существующему
            if (in_array($kpi->id, self::$_dont_group_kpi)) {
                if ($kpi->value != 0) {
                    $kpi->value = 1;
                }
                if (!isset($place->kpis[$kpi->name]->{$kpi->stage}))
                    $place->kpis[$kpi->name]->{$kpi->stage} = 0;
                $place->kpis[$kpi->name]->{$kpi->stage} += $kpi->value;
            } else {
                $place->kpis[$kpi->name]->{$kpi->stage} = $kpi->value;
            }
            unset($place->kpis[$kpi->name]->value);

            // отмечаем обработку кпи на правильный алгоритм
            $place->kpis[$kpi->name]->editable = null;
            switch ($kpi->name) {
                case 'Выручка':
                    break;
                case 'ММЛ':
                    $place->kpis[$kpi->name]->editable = 2;
                    $place->kpis[$kpi->name]->checked = $place->kpis[$kpi->name]->plan > 0 || $place->kpis[$kpi->name]->old > 0;

                    if ($place->kpis[$kpi->name]->checked)
                        $place->kpis[$kpi->name]->checked = $place->kpis[$kpi->name]->correct > 0;

                    break;
                default:
                    $place->kpis[$kpi->name]->editable = 1;
                    break;
            }

            // прописываем изменения к данному кпи
            $place->kpis[$kpi->name]->current = $place->kpis[$kpi->name]->plan;
            if ($place->kpis[$kpi->name]->current == 0 && $place->kpis[$kpi->name]->old > 0) {
                $place->kpis[$kpi->name]->current = $place->kpis[$kpi->name]->old;
            }
            if (isset($place->kpis[$kpi->name]->plan) && isset($place->kpis[$kpi->name]->correct)) {
                $place->kpis[$kpi->name]->current = $place->kpis[$kpi->name]->correct;
                $place->kpis[$kpi->name]->direction = null;
                if ($place->kpis[$kpi->name]->correct > 0) {
                    if ($place->kpis[$kpi->name]->correct < $place->kpis[$kpi->name]->plan)
                        $place->kpis[$kpi->name]->direction = 'down';

                    if ($place->kpis[$kpi->name]->correct > $place->kpis[$kpi->name]->plan)
                        $place->kpis[$kpi->name]->direction = 'up';
                }
            }
        }

        // корректируем выручку в плане
        if (isset($place->kpis['Выручка']->plan) && $place->kpis['Выручка']->plan == 0) {
            $place->kpis['Выручка']->plan = $place->kpis['Тоннаж']->plan * $av_cost->value;
        }

        // корректируем выручку в актуальном значение
        if (isset($place->kpis['Выручка']->current) && $place->kpis['Выручка']->current == 0) {
            $place->kpis['Выручка']->current = $place->kpis['Тоннаж']->current * $av_cost->value;
        }

        // корректируем выручку в корректировке
        if (isset($place->kpis['Выручка']->correct) && $place->kpis['Выручка']->correct == 0) {
            $place->kpis['Выручка']->correct = $place->kpis['Тоннаж']->correct * $av_cost->value;
        }
        return $place;
    }

    /**
     * метод формирует правильный кпи
     *
     * @param object $kpi кпи
     */
    public static function get_tp_place_list_format_kpi($kpi) {
        switch ($kpi->uom) {
            // case 'руб':
            //     $kpi->value    = $kpi->value / 1000;
            //     $kpi->uom     = 'т.р';
            //     break;
            // case 'кг':
            //     $kpi->value    = $kpi->value / 1000;
            //     $kpi->uom     = 'т.';
            //     break;
            case 'шт':
                if (isset($kpi->value))
                    $kpi->value    = ceil($kpi->value);
                if (isset($kpi->plan))
                    $kpi->plan    = ceil($kpi->plan);
                if (isset($kpi->correct))
                    $kpi->correct    = ceil($kpi->correct);
                break;
            default:
                break;
        }
        return $kpi;
    }

    /**
     * метод достаёт список доступных кпи
     *
     * @param integer $time время
     */
    protected static function get_available_kpis($time) {
        // достаём глобальный необходимый функционал
        global $DB;
        $sql = "SELECT  kpi.name, kpi.id, kpi.uom, 0 AS value, 0 AS avc
                FROM mdl_lm_settinggoals_plan AS p,
                mdl_lm_kpi AS kpi,
                mdl_lm_settinggoals_plan_kpi AS pk
                WHERE pk.planid = p.id
                AND FROM_UNIXTIME(p.date, '%Y-%m-%d') = ?
                AND kpi.id = pk.kpiid
                GROUP BY kpi.id ";
        return $DB->get_records_sql(
            $sql,
            [ date("Y-m-d", $time) ]
        );
    }

    /**
     * метод достаёт список пользователей данного супервайзера
     *
     * @param integer $userid   ид супервайзера
     * @param integer $time     время
     * @param integer $page     актуальная страница
     * @param integer $per_page количество пользователей на одной странице
     */
    public static function get_sv_user_list($userid, $time, $page = 1, $per_page = 8) {
        // достаём глобальный необходимый функционал
        global $DB, $OUTPUT;

        // обробатываем информацию о страничках
        $page         = $page < 1 ? 1 : $page * 1;
        $per_page     = $per_page < 1 ? 8 : $per_page * 1;

        // данные для пагинатора актуальной страници
        $get_count    = $per_page;
        $from         = $page * $per_page - $per_page;

        // инициализация пустого резултата
        $auto_list    = [];
        $correct_list = [];
        $user_list    = [];
        $status       = 0;
        $kpi_list     = [];

        // достаём актуального пользователя
        $current_user = $current_user = self::get_current_user($userid, 3);

        // выходим если актуальный пользователь не найден
        if (!$current_user) {
            return [$auto_list, $correct_list, $status, $user_list, $kpi_list, $pager_data];
        }

        // достаём статистику кпи актуального пользователя
        list(
            $auto_list,
            $correct_list,
            $fact_list,
            $kpi_list) =
        self::get_tp_total_stats(
            $current_user->posid,
            $time,
            true
        );

        // достаём количество пользователей
        $sql = "SELECT COUNT(DISTINCT id) AS count
                FROM {" . self::TABLE_POSITION . "}
                WHERE parentid = ?
                ";
        $res_count = $DB->get_record_sql(
            $sql,
            [
                $current_user->posid
            ]
        );

        // достаём пользователей из базы
        $sql = "SELECT u.id, p.id AS posid, pl.state, u.firstname, u.lastname, u.picture, u.imagealt
                FROM {" . self::TABLE_POSITION . "} AS p
                LEFT JOIN mdl_lm_settinggoals_plan AS pl ON (
                    pl.positionid = p.id
                    AND FROM_UNIXTIME(pl.date, '%Y-%m-%d') = ?
                ),
                {" . self::TABLE_POSITION_XREF . "} AS xref,
                {" . self::TABLE_USER . "} as u
                where p.parentid = ?
                AND xref.posid = p.id
                AND xref.userid = u.id
                GROUP BY p.id
                ORDER BY u.firstname ASC, u.lastname ASC
                LIMIT " . $from . ", " . $get_count;

        $res_tp_list = $DB->get_records_sql(
            $sql,
            [
                date("Y-m-d", $time),
                $current_user->posid
            ]
        );

        // формируем список пользователей
        foreach ($res_tp_list as $tp) {
            // достаём аватарку пользователя
            $tp->ava = $OUTPUT->user_picture($tp, array('size' => 45, 'link' => TRUE, 'alttext' => FALSE, 'popup' => TRUE));
            $tp->kpis = [];

            // достаём статистику торгового представителя
            list($tp_auto_list, $tp_correct_list, $tp_fact_list, $tp_kpi_list) =
            self::get_tp_total_stats($tp->posid, $time);

// echo "<pre>";
// print_r([$tp_auto_list, $tp_correct_list]);
// echo "</pre>";
// exit;


            // формируем список кпи автоматического расчёта
            foreach ($tp_auto_list as $kpi) {
                if (!isset($kpi_list[$kpi->id]) && !in_array($kpi->name, $kpi_list))
                    $kpi_list[$kpi->id] = $kpi->name;
                $tp->kpis[$kpi->name]            = new StdClass();
                $tp->kpis[$kpi->name]->uom       = $kpi->uom;
                $tp->kpis[$kpi->name]->plan      = $kpi->value;
                $tp->kpis[$kpi->name]->correct   = $kpi->value;
                $tp->kpis[$kpi->name]->direction = null;
            }

            // формируем список кпи корректировки
            foreach ($tp_correct_list as $kpi) {
                $tp->kpis[$kpi->name]->correct = $kpi->value;
                $tp->kpis[$kpi->name]->direction = $kpi->direction;
            }

            // формируем список старых значений кпи
            $kpi_stat = self::get_kpi_stat($tp->posid, $time, 'old');
            foreach ($kpi_stat as $kpi) {
                $tp->kpis[$kpi->name]->old = $kpi->value;
            }

            // добавляем в список торговых представителей
            $user_list[] = $tp;
        }

         // готовим даные для пагинатора
        $pager_data = [
            'count' => ceil($res_count->count / $per_page),
            'current' => $page,
        ];
        return [$auto_list, $correct_list, $status, $user_list, self::sort_fields($kpi_list), $pager_data];
    }

    /**
     * метод запускает корректировку у торгового представителя
     *
     * @param integer $posid   ид торгового представителя
     * @param integer $time    время
     * @param boolean $again   повторный запуск?
     */
    public static function tp_start_correct($posid, $time, $again = false) {
        // достаём глобальный необходимый функционал
        global $DB;

        $sql = "SELECT COUNT(*) AS count FROM {" . self::TABLE_GOALSETTING_PLAN . "}
                WHERE positionid = ? AND FROM_UNIXTIME(date, '%Y-%m-%d') = ? AND state = ?";
        $res = $DB->get_record_sql($sql, [$posid, date("Y-m-d", $time),
            $again ? self::STATE_AGREEMENT_REJECT : self::STATE_NEW]);
        if($res->count == 0)
            return;

        $sql = "UPDATE {" . self::TABLE_GOALSETTING_PLAN . "} SET state = ?
                WHERE positionid = ? AND FROM_UNIXTIME(date, '%Y-%m-%d') = ?
                AND state = ?";
        $DB->execute($sql, [self::STATE_CORRECT, $posid, date("Y-m-d", $time),
            $again ? self::STATE_AGREEMENT_REJECT : self::STATE_NEW]);

        // выходим если запуск повторный
        if ($again)
            return;

        $sql = "INSERT INTO {" . self::TABLE_GOALSETTING_PLAN_KPI . "} (planid, kpiid, stage, value, comment)
                SELECT pk.planid, pk.kpiid, 'correct', pk.value, ''
                FROM {" . self::TABLE_GOALSETTING_PLAN . "} AS p,
                {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pk
                LEFT JOIN {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pkc ON
                (pkc.planid = pk.planid AND pkc.kpiid = pk.kpiid
                AND pkc.stage = 'correct')
                WHERE pk.planid = p.id
                AND p.positionid = ?
                AND FROM_UNIXTIME(p.date, '%Y-%m-%d') = ?
                AND pk.stage = 'plan'
                AND pkc.id IS NULL";
        $DB->execute($sql, [$posid, date("Y-m-d", $time)]);
    }

    /**
     * метод достаёт из базы последний этап
     *
     * @param integer $svposid   ид позиции супервайзера
     * @param boolean $is_sv     запускает супервайзер?
     */
    public static function get_last_phase($svposid, $is_sv = false) {
        // достаём глобальный необходимый функционал
        global $DB, $CFG;
        $sql = "SELECT *
                FROM {" . self::TABLE_GOALSETTING_PHASE . "}
                WHERE status = 0 AND svposid = ? AND date >= ?
                ORDER BY date DESC";
        $phase = $DB->get_record_sql(
            $sql,
            [
                $svposid,
                mktime(0, 0, 0, date("n"), date("j"), date("Y"))
            ]);
        $mins = $CFG->block_lm_goalsetting_deadline_1 ? $CFG->block_lm_goalsetting_deadline_1 : 5;
        $mins *= 60;

        // создаём этап если этапа нету в базе и запускает метод супервайзер
        if (!$phase && $is_sv) {
            $sql = "INSERT INTO {" . self::TABLE_GOALSETTING_PHASE . "}
                    (`status`, `phase`, `deadline`, `date`, `svposid`,`comment`)
                    VALUES ('0', '0', ?, ?, ?, '')";
            $DB->execute(
                $sql,
                [
                    time() + $mins,
                    mktime(0, 0, 0, date("n"), date("j"), date("Y")),
                    $svposid
                ]
            );
            $sql = "SELECT *
                    FROM {" . self::TABLE_GOALSETTING_PHASE . "}
                    WHERE status = 0 AND svposid = ? AND date >= ?
                    ORDER BY date DESC";
            $phase = $DB->get_record_sql(
                $sql,
                [
                    $svposid,
                    mktime(0, 0, 0, date("n"), date("j"), date("Y"))
                ]);
        }

        // создаём пустой этап если этап не подгрузился из базы
        if (!$phase) {
            $phase = new StdClass();
            $phase->id = 0;
            $phase->status = 0;
            $phase->phase = 0;
            $phase->deadline = 0;
            $phase->date = time();
            $phase->svposid = $svposid;
            $phase->comment = '';
        }
        return $phase;
    }

    /**
     * метод запускает новый этап
     *
     * @param integer $new_phase     номер этапа
     * @param integer $new_deadline  время завершения этапа
     * @param integer $svposid       позицион ид супервайзера
     */
    public static function set_new_phase($new_phase, $new_deadline, $svposid) {
        $curr_phase = self::get_last_phase($svposid);
        if ($curr_phase->phase == $new_phase)
            return false;
        // достаём глобальный необходимый функционал
        global $DB;
        $sql = "UPDATE {" . self::TABLE_GOALSETTING_PHASE . "} SET deadline = ?, phase = ?
                WHERE id = ?";
        $DB->execute($sql, [$new_deadline, $new_phase, $curr_phase->id]);
        return self::get_last_phase($svposid);
    }

    /**
     * метод закрывает последний этап
     *
     * @param integer $svposid   ид позиции супервайзера
     */
    public static function close_current_phase($svposid) {
        $curr_phase = self::get_last_phase($svposid);
        // достаём глобальный необходимый функционал
        global $DB;
        $sql = "UPDATE {" . self::TABLE_GOALSETTING_PHASE . "} SET state = 1
                WHERE id = ?";
        $DB->execute($sql, [$curr_phase->id]);
        return self::get_last_phase($svposid);
    }

    /**
     * метод достаёт из базы данные кпи
     *
     * @param integer $kpiid   ид кпи
     * @param integer $planid  ид плана
     */
    public static function get_kpi_data($kpiid, $planid) {
        // достаём глобальный необходимый функционал
        global $DB;
        $sql = "SELECT pl.name AS place_name, k.uom, k.name AS kpi_name,
                round(pk.value, 1) AS value, round(pkc.value, 1) AS correct_value
                FROM {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pk
                LEFT JOIN {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pkc ON
                (pkc.planid = pk.planid AND pkc.kpiid = pk.kpiid
                AND pkc.stage = 'correct')
                ,{" . self::TABLE_GOALSETTING_PLAN . "} AS p,
                {" . self::TABLE_TRADE_OUTLETS . "} AS pl,
                {" . self::TABLE_KPI . "} AS k
                WHERE pk.planid = ?
                AND pk.kpiid = ?
                AND pk.stage = 'plan'
                AND p.id = pk.planid
                AND pl.id = p.placeid
                AND k.id = pk.kpiid
                ";
        $kpi = $DB->get_record_sql($sql, [$planid, $kpiid]);
        $kpi->correct_value = $kpi->correct_value > 0 ? $kpi->correct_value : $kpi->value;
        return $kpi;
    }

    /**
     * метод достаёт из базы список продуктов для ммл
     *
     * @param integer $kpiid   ид кпи
     * @param integer $planid  ид плана
     */
    public static function get_kpi_data_list($kpiid, $planid) {
        // достаём глобальный необходимый функционал
        global $DB;
        foreach (['plan', 'correct'] as $type) {
            $sql = "SELECT pk.id, pl.name AS place_name, k.uom, k.name AS kpi_name,
                    pk.value, IF(nc.name IS NULL, pk.value, nc.name) as nc_name
                    FROM {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pk
                    LEFT JOIN {" . self::TABLE_NOMENCLATURE . "} AS nc ON nc.code = pk.value
                    ,{" . self::TABLE_GOALSETTING_PLAN . "} AS p,
                    {" . self::TABLE_TRADE_OUTLETS . "} AS pl,
                    {" . self::TABLE_KPI . "} AS k
                    WHERE pk.planid = ?
                    AND pk.kpiid = ?
                    AND pk.stage = ?
                    AND p.id = pk.planid
                    AND pl.id = p.placeid
                    AND k.id = pk.kpiid
                    ";
            $kpis = $DB->get_records_sql($sql, [$planid, $kpiid, $type]);
            $result = new StdClass();
            $result->list = [];
            $wrong = true;
            foreach ($kpis as $kpi) {
                if (!isset($result->place_name))
                    $result->place_name = $kpi->place_name;

                if (!isset($result->kpi_name))
                    $result->kpi_name = $kpi->kpi_name;
                $result->list[$kpi->value] = $kpi->nc_name;
                if ($kpi->value != 0.0000) {
                    $wrong = false;
                }
            }

            if ($wrong != true) {
                return $result;
            }
        }
        return $result;
    }

    /**
     * метод достаёт план из базы
     *
     * @param integer $planid  ид плана
     */
    public static function get_plan($planid) {
        // достаём глобальный необходимый функционал
        global $DB;
        $sql = "SELECT *
                FROM {" . self::TABLE_GOALSETTING_PLAN . "}
                WHERE id = ?
                ";
        return $DB->get_record_sql($sql, [$planid]);
    }

    /**
     * метод сохраняет новые значение для кпи
     *
     * @param integer $kpiid   ид кпи
     * @param integer $planid  ид плана
     * @param float   $value   новое значение
     */
    public static function save_edit_kpi_value($kpiid, $planid, $value) {
        $value = str_replace(" ", "", $value);
        $value = str_replace(",", ".", $value) * 1;
        global $DB;
        $sql = "SELECT pk.*, k.*
                FROM {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pk,
                {" . self::TABLE_KPI . "} AS k
                WHERE pk.planid = ?
                AND pk.kpiid = ?
                AND pk.kpiid = k.id
                AND pk.stage = 'plan'
                ORDER BY pk.id ASC
                ";
        $kpi = $DB->get_record_sql($sql, [$planid, $kpiid]);

        if (!$kpi)
            return false;

        // remove old values for current kpi
        $sql = "DELETE FROM {" . self::TABLE_GOALSETTING_PLAN_KPI . "} WHERE planid = ?
                AND kpiid = ? AND stage = 'old'";
        $DB->execute($sql, [$planid, $kpiid]);

        $sql = "UPDATE {" . self::TABLE_GOALSETTING_PLAN_KPI . "} SET stage = 'old'
                WHERE planid = ?
                AND kpiid = ?
                AND stage = 'correct'
                ";
        $DB->execute($sql, [$planid, $kpiid]);

        $sql = "INSERT INTO {" . self::TABLE_GOALSETTING_PLAN_KPI . "}
                (planid, kpiid, stage, value, comment)
                VALUES (?,?,?,?,?)";
        $DB->execute($sql, [$planid, $kpiid, 'correct', $value, '']);

        if ($value == 0) {
            $sql = "SELECT pk.*
                    FROM {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pk,
                    {" . self::TABLE_KPI . "} AS k
                    WHERE pk.planid = ?
                    AND pk.kpiid = k.id
                    AND pk.stage = 'correct'
                    AND k.name = 'Фокусный ассортимент'
                    ORDER BY pk.id ASC
                    ";

            $kpi_d = $DB->get_record_sql($sql, [$planid]);
            if ($kpi_d) {
                $sql = "UPDATE {" . self::TABLE_GOALSETTING_PLAN_KPI . "} SET value = 0 WHERE id = ?";
                $DB->execute($sql, [$kpi_d->id]);
            }

            $sql = "SELECT pk.*
                    FROM {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pk,
                    {" . self::TABLE_KPI . "} AS k
                    WHERE pk.planid = ?
                    AND pk.kpiid = k.id
                    AND pk.stage = 'plan'
                    AND k.name = 'ММЛ'
                    ORDER BY pk.id ASC
                    ";

            $kpi_d = $DB->get_record_sql($sql, [$planid]);
            if (!$kpi_d)
                return false;
            self::toggle_kpi_count($kpi_d->kpiid, $planid, false);
        }
        if ($kpi->name != 'Тоннаж') {
            return false;
        }
        $sql = "SELECT pk.*
                FROM {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pk,
                {" . self::TABLE_KPI . "} AS k
                WHERE pk.planid = ?
                AND pk.kpiid = k.id
                AND pk.stage = 'plan'
                AND k.name = 'Выручка'
                ORDER BY pk.id ASC
                ";
        $kpi_d = $DB->get_record_sql($sql, [$planid]);

        if (!$kpi_d)
            return false;

        $kpiid = $kpi_d->kpiid;
        $plan = self::get_plan($planid);

        if (!$plan)
            return false;

        $av_cost = self::get_pos_av_cost($plan->positionid, $plan->date);
        if (!$av_cost)
            return false;

        $avg = $av_cost->value;

        $sql = "UPDATE {" . self::TABLE_GOALSETTING_PLAN_KPI . "} SET stage = 'old'
                WHERE planid = ?
                AND kpiid = ?
                AND stage = 'correct'
                ";
        $DB->execute($sql, [$planid, $kpiid]);

        $sql = "INSERT INTO {" . self::TABLE_GOALSETTING_PLAN_KPI . "}
                (planid, kpiid, stage, value, comment)
                VALUES (?,?,?,?,?)";
        $DB->execute($sql, [$planid, $kpiid, 'correct', $value * $avg, '']);
    }

    /**
     * метод переключает кпи статус
     *
     * @param integer $kpiid   ид кпи
     * @param integer $planid  ид плана
     * @param float   $value   новое значение
     */
    public static function toggle_kpi_count($kpiid, $planid, $value = null) {
        // достаём глобальный необходимый функционал
        global $DB;

        $sql = "SELECT COUNT(*) AS count
                FROM {" . self::TABLE_GOALSETTING_PLAN_KPI . "}
                WHERE planid = ?
                AND kpiid = ?
                AND stage = 'correct'
                ORDER BY id ASC";
        $count = $DB->get_record_sql($sql, [$planid, $kpiid]);

        if ($count->count > 0) {
            $sql = "DELETE
                    FROM {" . self::TABLE_GOALSETTING_PLAN_KPI . "}
                    WHERE planid = ?
                    AND kpiid = ?
                    AND stage = 'correct'";
            $count = $DB->execute($sql, [$planid, $kpiid]);
            return;
        }
        if (!is_null($value) && $value == false) {
            return false;
        }
        $sql = "INSERT INTO {" . self::TABLE_GOALSETTING_PLAN_KPI . "} (planid, kpiid, stage, value, comment)
                SELECT pk.planid, pk.kpiid, 'correct', pk.value, ''
                FROM {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pk
                LEFT JOIN {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pkc ON
                (pkc.planid = pk.planid AND pkc.kpiid = pk.kpiid
                AND pkc.stage = 'correct')
                WHERE pk.planid = ?
                AND pk.kpiid = ?
                AND pk.stage = 'plan'
                AND pk.value > 0
                AND pkc.id IS NULL";
        $DB->execute($sql, [$planid, $kpiid]);

        $sql = "SELECT COUNT(*) AS count
                FROM {" . self::TABLE_GOALSETTING_PLAN_KPI . "}
                WHERE planid = ?
                AND kpiid = ?
                AND stage = 'correct'
                ORDER BY id ASC";
        $count = $DB->get_record_sql($sql, [$planid, $kpiid]);
        if (!$count || $count->count > 0) {
            return true;
        }
        $sql = "INSERT INTO {" . self::TABLE_GOALSETTING_PLAN_KPI . "} (planid, kpiid, stage, value, comment)
                SELECT pk.planid, pk.kpiid, 'correct', pk.value, ''
                FROM {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pk
                LEFT JOIN {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pkc ON
                (pkc.planid = pk.planid AND pkc.kpiid = pk.kpiid
                AND pkc.stage = 'correct')
                WHERE pk.planid = ?
                AND pk.kpiid = ?
                AND pk.stage = 'old'
                AND pkc.id IS NULL";
        $DB->execute($sql, [$planid, $kpiid]);
    }

    /**
     * метод достаёт данные о плане
     *
     * @param integer $planid  ид плана
     */
    public static function get_plan_data($planid) {
        // достаём глобальный необходимый функционал
        global $DB;
        $sql = "SELECT pl.name AS place_name, p.comment
                FROM {" . self::TABLE_GOALSETTING_PLAN . "} AS p,
                {" . self::TABLE_TRADE_OUTLETS . "} AS pl
                WHERE p.id = ?
                AND pl.id = p.placeid
                ";
        return $DB->get_record_sql($sql, [$planid]);
    }

    /**
     * метод достаёт из базы список торговых точек которые отсутствуют в списке у торгового представителя
     *
     * @param integer $posid     позиция торгового представителя
     * @param integer $time      время
     * @param integer $search    поисковое слово
     * @param integer $page      номер страници результата
     * @param integer $per_page  количество результатов на одной странице
     */
    public static function get_tp_outlet_list($posid, $time, $search, $page = 1, $per_page = 16) {
        // достаём глобальный необходимый функционал
        global $DB;

        // обробатываем информацию о страничках
        $page         = $page < 1 ? 1 : $page * 1;
        $per_page     = $per_page < 1 ? 16 : $per_page * 1;

        // данные для пагинатора актуальной страници
        $get_count    = $per_page;
        $from         = $page * $per_page - $per_page;

        $base_sql = "FROM {" . self::TABLE_TRADE_OUTLETS . "} AS pl
            LEFT JOIN {" . self::TABLE_GOALSETTING_PLAN . "} AS p ON (
                pl.id = p.placeid
                AND p.positionid = ?
                AND FROM_UNIXTIME(p.date, '%Y-%m-%d') = ?
            )
            ,{" . self::TABLE_POSITION . "} as pos
            WHERE p.positionid IS NULL
            AND pos.id = ?
            AND pos.areaid = pl.areaid ";

        $params = [$posid, date("Y-m-d", $time), $posid];
        if (!empty($search)) {
            $base_sql .= " AND pl.name LIKE ?";
            $params[] = '%' . $search . '%';
        }
        $sql = "SELECT COUNT(DISTINCT pl.id) AS count " . $base_sql;
        $res_count = $DB->get_record_sql($sql, $params);
        $sql = "SELECT pl.id, pl.name, pl.address, p.positionid " . $base_sql;
        $sql .= "GROUP BY pl.id ";
        $sql .= "LIMIT " . $from . ", " . $get_count;
        $list = array_values(
            $DB->get_records_sql($sql, $params)
        );
         // готовим даные для пагинатора
        $pager_data = [
            'count' => ceil($res_count->count / $per_page),
            'current' => $page,
        ];
        return [$posid, $time, $list, $search, $pager_data];
    }

    /**
     * метод отсылает план на согласование
     *
     * @param integer $time   время
     * @param integer $posid  ид позиции супервайзера
     * @param boolean $force  игнорировать проверку недостатка
     */
    public static function send_agreement($time, $posid, $force = false) {
        // достаём глобальный необходимый функционал
        global $DB;

        if (!$force) {


            // достаём общую статистику для данного пользователя
            list(
                $auto_list,
                $correct_list,
                $fact_list,
                $kpi_list
            ) = self::get_tp_total_stats(
                $posid,
                $time
            );
            $list = [];
            $break = false;
            foreach ((array)$correct_list as $key => $value) {
                $kpi = new StdClass();
                $tmp = (array)$auto_list[$key];
                $kpi->kpi_name = $tmp['name'];
                $kpi->id = $tmp['id'];
                $kpi->uom = $tmp['uom'];
                $kpi->value = $tmp['value'];
                $kpi->value -= $value->value;

                if ($kpi->value < 0) {
                    $kpi->value = 0;
                }

                if ($kpi->value > 0) {
                    $break = true;
                }
                $list[$kpi->kpi_name] = $kpi;
            }


            $av = self::get_pos_av_cost($posid, $time);
            if (isset($list['Выручка']->value) && $list['Выручка']->value == 0
                && isset($list['Тоннаж']->value) && $list['Тоннаж']->value > 0){
                $list['Выручка']->value = $list['Тоннаж']->value * $av->value;
            }

            if ($break)
                return [$break, self::sort_fields(array_keys($list)), $list];
        }

        $sql = "SELECT COUNT(*) AS count FROM {" . self::TABLE_GOALSETTING_PLAN . "}
                WHERE positionid = ? AND FROM_UNIXTIME(date, '%Y-%m-%d') = ? AND state = ?";
        $res = $DB->get_record_sql($sql, [$posid, date("Y-m-d", $time), self::STATE_CORRECT]);
        if($res->count == 0)
            return [false, [],[]];

        $sql = "UPDATE {" . self::TABLE_GOALSETTING_PLAN . "} SET state = ?
                WHERE positionid = ? AND FROM_UNIXTIME(date, '%Y-%m-%d') = ? AND state = ?";
        $DB->execute($sql, [self::STATE_AGREEMENT, $posid, date("Y-m-d", $time), self::STATE_CORRECT]);
        return [false, [],[]];
    }


    /**
     * метод ставит новый статус для плана
     *
     * @param integer $svposid   ид позиции супервайзера
     * @param integer $time      время
     * @param integer $new_state новый статус
     */
    public static function new_correction_state_sv($svposid, $time, $new_state) {
        // достаём глобальный необходимый функционал
        global $DB;

        $sql = "SELECT COUNT(*) AS count FROM {" . self::TABLE_GOALSETTING_PLAN . "}
                WHERE positionid = ? AND FROM_UNIXTIME(date, '%Y-%m-%d') = ? AND state < ?";
        $res = $DB->get_record_sql($sql, [$svposid, date("Y-m-d", $time), $new_state]);
        if($res->count == 0)
            return;

        $sql = "UPDATE {" . self::TABLE_GOALSETTING_PLAN . "} SET state = ?
                WHERE positionid = ? AND FROM_UNIXTIME(date, '%Y-%m-%d') = ? AND state < ?";
        $DB->execute($sql, [$new_state, $svposid, date("Y-m-d", $time), $new_state]);
    }

    /**
     * метод достаёт информацию для итогов дня
     *
     * @param integer $svposid   ид позиции супервайзера
     * @param integer $time      время
     */
    public static function get_daily_result($svposid, $time) {
        // достаём глобальный необходимый функционал
        global $DB, $OUTPUT;

        $kpi_list  = [];
        $auto_list = [];
        $fact_list = [];
        $user_list = [];
        list($auto_list, $correct_list, $fact_list, $kpi_list) = self::get_tp_total_stats($svposid, $time, true, 4);
        $sql = "SELECT u.id, pl.id AS planid, pl.positionid AS posid, pl.state, u.firstname, u.lastname
                FROM {" . self::TABLE_POSITION . "} AS p,
                {" . self::TABLE_GOALSETTING_PLAN . "} AS pl,
                {" . self::TABLE_POSITION_XREF . "} AS xref,
                {" . self::TABLE_USER . "} as u
                where p.parentid = ?
                AND pl.positionid = p.id
                AND xref.posid = p.id
                AND xref.userid = u.id
                AND FROM_UNIXTIME(pl.date, '%Y-%m-%d') = ?
                GROUP BY pl.positionid
                ORDER BY u.firstname ASC, u.lastname ASC";
        $res_tp_list = $DB->get_records_sql(
            $sql,
            [
                $svposid,
                date("Y-m-d", $time)
            ]
        );
        foreach ($res_tp_list as $tp) {
            $tp->ava = $OUTPUT->user_picture($tp, array('size' => 45, 'link' => TRUE, 'alttext' => FALSE, 'popup' => TRUE));
            $tp->kpis = [];

            list($tp_auto_list, $tp_correct_list, $tp_fact_list, $tp_kpi_list) = self::get_tp_total_stats($tp->posid, $time);
            foreach ($tp_correct_list as $kpi) {
                $tp->kpis[$kpi->name]            = new StdClass();
                $tp->kpis[$kpi->name]->uom       = $kpi->uom;
                $tp->kpis[$kpi->name]->plan      = $kpi->value;
                $tp->kpis[$kpi->name]->fact      = 0;
                $tp->kpis[$kpi->name]->direction = null;
            }
            foreach ($tp_fact_list as $kpi) {
                $tp->kpis[$kpi->name]->fact     = $kpi->value;
                $tp->kpis[$kpi->name]->direction = $kpi->direction;
            }
            $user_list[] = $tp;
        }
        return [self::sort_fields($kpi_list), $correct_list, $fact_list, $user_list];
    }

    /**
     * метод достаёт детальную информацию для итогов дня
     *
     * @param integer $posid   ид позиции торгового представителя
     * @param integer $time    время
     */
    public static function get_daily_result_details($posid, $time) {
        // достаём глобальный необходимый функционал
        global $DB;

        $kpi_list   = [];
        $total_kpis = [];
        $tp_list    = [];

        list(
            $auto_list,
            $correct_list,
            $fact_list,
            $kpi_list
        ) = self::get_tp_total_stats(
            $posid,
            $time
        );

        $kpi_stat = self::get_kpi_stat($posid, $time, 'correct');

        foreach ($correct_list as $kpi) {
            if (!isset($kpi_list[$kpi->id]) && !in_array($kpi->name, $kpi_list))
                $kpi_list[$kpi->id] = $kpi->name;
            $kpi = self::get_tp_place_list_format_kpi($kpi);
            $total_kpis[$kpi->name]            = new StdClass();
            $total_kpis[$kpi->name]->uom       = $kpi->uom;
            $total_kpis[$kpi->name]->plan      = $kpi->value;
            $total_kpis[$kpi->name]->fact      = 0;
            $total_kpis[$kpi->name]->direction = 'down';
        }

        $kpi_stat = self::get_kpi_stat($posid, $time, 'fact');
        foreach ($kpi_stat as $kpi) {
            if (!isset($kpi_list[$kpi->id]) && !in_array($kpi->name, $kpi_list))
                $kpi_list[$kpi->id] = $kpi->name;
            $kpi = self::get_tp_place_list_format_kpi($kpi);
            $total_kpis[$kpi->name]->fact = $kpi->value;
            $total_kpis[$kpi->name]->direction = null;
            if ($total_kpis[$kpi->name]->fact > 0) {
                if ($total_kpis[$kpi->name]->fact < $kpi->plan)
                    $total_kpis[$kpi->name]->direction = 'down';

                if ($total_kpis[$kpi->name]->fact > $kpi->plan)
                    $total_kpis[$kpi->name]->direction = 'up';
            }
        }

        // достаём список торговых точек из базы
        $sql = "SELECT p.id AS planid, pl.name, pl.address, p.comment, p.positionid
                FROM {" . self::TABLE_GOALSETTING_PLAN . "} AS p,
                {" . self::TABLE_TRADE_OUTLETS . "} AS pl
                WHERE p.positionid = ?
                AND p.placeid = pl.id
                AND FROM_UNIXTIME(p.date, '%Y-%m-%d') = ?
                AND p.state = ?";
        $res_place_list = $DB->get_records_sql($sql, [$posid, date("Y-m-d", $time)
            ,self::STATE_AGREEMENT_SUCCESS]);

        // достаём из базы среднею стоимость
        $av_cost = self::get_pos_av_cost($current_user->posid, $time);
        foreach ($res_place_list as $place) {
            $place = self::get_place_stats($place, $time, $av_cost);
            $tp_list[] = $place;
// echo "<pre>";
// print_r($tp_list);exit;
        }

        return [self::sort_fields($kpi_list), $total_kpis, $tp_list];
    }

    /**
     * Получить список комментов для плана
     * @param int $phaseid
     * @return bool|int
     */
    public static function get_plan_comments($phaseid, $tpposid) {
        // достаём глобальный необходимый функционал
        global $DB;

        $phaseid = (int) $phaseid;
        $tpposid = (int) $tpposid;

        if ($phaseid <= 0 || $tpposid <= 0) return FALSE;

        return $DB->get_records('lm_settinggoals_plan_comments', array('phaseid' => $phaseid, 'tpposid' => $tpposid));
    }

    /**
     * ajax method для добавления нового коммента
     * @param int $phaseid
     * @param string $text
     * @return bool|int
     */
    public static function save_plan_comment($phaseid, $tpposid, $text) {
        // достаём глобальный необходимый функционал
        global $USER, $DB;

        $tpposid = (int) $tpposid;

        if ($tpposid <= 0) return FALSE;

        $posxrefid = lm_position::get_user_posixrefid($USER->id);

        $new_comment = new stdClass;
        $new_comment->phaseid   = (int) $phaseid;
        $new_comment->tpposid   = (int) $tpposid;
        $new_comment->posxrefid = (int) $posxrefid;
        $new_comment->text      = trim($text);

        return $DB->insert_record('lm_settinggoals_plan_comments', $new_comment);
    }

    /**
     * метод достаёт значение по умолчанию для данного кпи
     *
     * @param integer $kpiid   ид кпи
     * @param integer $time    время
     * @param integer $posid   ид позиции
     */
    protected static function get_default_kpi_value($kpiid, $time, $posid){
        // достаём глобальный необходимый функционал
        global $DB;
        $sql = "SELECT group_concat(pk.value) AS `list`
                FROM mdl_lm_settinggoals_plan AS p,
                mdl_lm_settinggoals_plan_kpi AS pk
                WHERE pk.planid = p.id
                AND p.positionid = ?
                AND FROM_UNIXTIME(p.date, '%Y-%m-%d') = ?
                AND pk.stage = 'plan'
                AND pk.kpiid = ?
                AND pk.value > 0
                GROUP BY p.id
                ORDER BY p.id DESC
                LIMIT 0, 1";
        $res = $DB->get_record_sql($sql, [$posid, date("Y-m-d", $time), $kpiid]);
        if (!$res) {
            return [];
        }
        return explode(",", $res->list);
    }

    /**
     * метод добавляет новые торговые точки в базу
     *
     * @param integer $posid    ид позиции торгового представителя
     * @param integer $outlets  список торговых точек
     * @param integer $time     время
     */
    public static function save_outlets($posid, $outlets, $time) {
        // достаём глобальный необходимый функционал
        global $DB;
        $add = [];
        $kpi_stat = self::get_kpi_stat($posid, $time, 'plan');

        foreach ($outlets as $outlet_id => $state) {
            if ($state != 'true')
                continue;

            $sql = "SELECT COUNT(*) AS count
                    FROM {" . self::TABLE_GOALSETTING_PLAN . "} AS p
                    WHERE FROM_UNIXTIME(p.date, '%Y-%m-%d') = ?
                    AND p.positionid = ?
                    AND p.placeid = ?";
            $res_count = $DB->get_record_sql(
                $sql,
                [
                    date("Y-m-d", $time),
                    $posid,
                    $outlet_id
                ]
            );
            if ($res_count->count > 0)
                continue;

            $plan = new stdClass;
            $plan->positionid = (int)$posid;
            $plan->placeid    = (int)$outlet_id;
            $plan->date       = time();
            $plan->state      = 1;
            $plan->comment    = '';
            $planid = $DB->insert_record('lm_settinggoals_plan', $plan);

            foreach ($kpi_stat as $kpi) {
                $values = [];
                // если кпи является поштучной то копируем с
                // последнего товара где есть этот кпи на сегодня
                if ($kpi->uom == 'шт') {
                    $values = self::get_default_kpi_value($kpi->id, $time, $posid);
                } else {
                    $values[] = 0;
                }
                $plan_kpi = new stdClass;
                $plan_kpi->planid = $planid;
                $plan_kpi->kpiid  = $kpi->id;
                $plan_kpi->comment = '';
                foreach ($values as $value) {
                    $plan_kpi->value = 0;
                    $plan_kpi->stage = 'plan';
                    $DB->insert_record(self::TABLE_GOALSETTING_PLAN_KPI, $plan_kpi);
                    $plan_kpi->value = $value;
                    $plan_kpi->stage = 'old';
                    $DB->insert_record(self::TABLE_GOALSETTING_PLAN_KPI, $plan_kpi);
                    $plan_kpi->stage = 'correct';
                    $DB->insert_record(self::TABLE_GOALSETTING_PLAN_KPI, $plan_kpi);
                }
            }
        }
    }

    public static function cleanup_db()
    {
        // достаём глобальный необходимый функционал
        global $DB;

        $count_kpi = true;
        while ($count_kpi) {
            $sql = "DELETE FROM mdl_lm_settinggoals_plan_kpi
                    WHERE planid in (SELECT id FROM mdl_lm_settinggoals_plan WHERE date < (unix_timestamp() - (60*60*24*7)))
                    LIMIT 100000";
            $DB->execute($sql);
            $count_kpi_sql = "SELECT COUNT(*) as count FROM mdl_lm_settinggoals_plan_kpi
                              WHERE planid in (SELECT id FROM mdl_lm_settinggoals_plan
                              WHERE date < (unix_timestamp() - (60*60*24*7)))";
            $count_kpi = $DB->get_record_sql($count_kpi_sql);
            $count_kpi = $count_kpi->count > 0;
        }

        $sql = "DELETE FROM mdl_lm_settinggoals_plan WHERE date < (unix_timestamp() - (60*60*24*7))";
        $DB->execute($sql);
    }

    /**
     * метод проверяет просроченные торговые представители
     */
    public static function check_deleys()
    {
        // достаём глобальный необходимый функционал
        global $DB;
        $sql = "SELECT *  FROM {" . self::TABLE_GOALSETTING_PHASE . "}
                WHERE deadline < ? AND status = 0 AND svposid > 0";
        $phase_list = $DB->get_records_sql($sql, [time()]);

        if (!$phase_list)
            return false;

        foreach ($phase_list as $phase) {
            $time = strtotime(date("d.m.Y", $phase->deadline) . ' 00:00:00');

            list($auto_list, $correct_list, $status, $user_list, $kpi_list, $pager_data)
                = self::get_sv_user_list($phase->svposid);

            foreach ($user_list as $user) {
                $sql = "SELECT * FROM {" . self::TABLE_GOALSETTING_PLAN . "}
                        WHERE positionid = ? AND FROM_UNIXTIME(date, '%Y-%m-%d') = ? AND state != ?";
                $plan = $DB->get_record_sql($sql, [$user->id, date("Y-m-d", $time), self::STATE_AGREEMENT_SUCCESS]);
                if (!$plan)
                    continue;

                $delay = $DB->get_records(self::TABLE_GOALSETTING_DELAY,
                    array('posid' => $user->id, 'date' => $time));

                if ($delay) {
                    continue;
                }

                $delay = new stdClass;
                $delay->posid = (int)$user->id;
                $delay->date    = (int)$time;
                $DB->insert_record(self::TABLE_GOALSETTING_DELAY, $delay);
            }
        }
    }

    /**
     * метод достаёт пост ид по имени
     *
     * @param integer $name  имя поста
     */
    public static function get_post_id_by_name($name) {
        // достаём глобальный необходимый функционал
        global $DB;
        // достать ид поста торгового представителя
        $sql = "SELECT `id` FROM {" . self::TABLE_POST . "} WHERE `name` = ? LIMIT 1";
        $res = $DB->get_record_sql($sql, [$name]);
        if (!$res)
            return false;
        return $res->id;
    }

    /**
     * метод экспортирует данные в чикаго
     */
    public static function export_data(){
        // достаём глобальный необходимый функционал
        global $DB, $CFG;
        $times = $CFG->block_lm_goalsetting_export_time;
        $times = explode(",", $times);
        foreach ($times as $time) {
            $time = strtotime(date("d.m.Y ") . $time . ':00');
            if ($time > time())
                continue;

            $export = $DB->get_records(
                self::TABLE_GOALSETTING_EXPORTS
                ,array('time' => $time)
            );
            if ($export) {
                return false;
            }

            $export = new stdClass;
            $export->time = (int)$time;
            $DB->insert_record(self::TABLE_GOALSETTING_EXPORTS, $export);
        }
    }

    /**
     * метод обновляет топ супервайзера за прошлый месяц
     */
    public static function update_top_sv_for_last_moth() {
        // достаём глобальный необходимый функционал
        global $DB;
        if (date('j') != 1)
            return false;

        $yesterday = strtotime('yesterday');
        $start     = strtotime(date('Y-m-01 00:00:00', $yesterday));
        $end       = strtotime(date('Y-m-t 23:59:59', $yesterday));

        $top_user =  $DB->get_records(
            self::TABLE_GOALSETTING_TOP_LIST,
            array('time' => $end)
        );

        if ($top_user)
            return false;

        $postid = self::get_post_id_by_name('СВ');

        $sql = "SELECT `p`.id "
             . "FROM {" . self::TABLE_POSITION . "} AS `p`, {"
             . self::TABLE_POSITION_XREF . "} AS `xref` "
             . "WHERE `p`.`postid` = ? AND `xref`.`posid` = `p`.`id` ";
        $sv_list = $DB->get_records_sql($sql, [$postid]);

        $now = null;
        foreach ($sv_list as $sv) {
            $sql = "SELECT  kpi.name, kpi.id, kpi.uom, SUM(IF(kpi.id in (" .
                    implode(",", self::$_dont_group_kpi) . "),
                    IF(pk.value != 0, 1, 0), pk.value)) AS value
                    FROM {" . self::TABLE_GOALSETTING_PLAN . "} AS p,
                    {" . self::TABLE_KPI . "} AS kpi,
                    {" . self::TABLE_GOALSETTING_PLAN_KPI . "} AS pk
                    WHERE pk.planid = p.id
                    AND p.positionid in (SELECT id FROM {" .
                        self::TABLE_POSITION . "} WHERE parentid = ?)
                    AND p.date >= ?
                    AND p.date <= ?
                    AND pk.stage = 'plan'
                    AND kpi.id = pk.kpiid
                    GROUP BY pk.kpiid
                    ";
            $kpi_stat = $DB->get_records_sql(
                $sql,
                [
                    $sv->id,
                    $start,
                    $end
                ]
            );
            if (!$kpi_stat)
                continue;

            if (!is_null($now)) {
                foreach ($now->kpis as $key => $value) {
                    if (!isset($kpi_stat[$key]))
                        continue 2;
                    if ($kpi_stat[$key] < $value)
                        continue 2;
                }
            }
            $now = $sv;
            $now->kpis = $kpi_stat;
        }

        if (is_null($now))
            return false;

        $top_user = new stdClass;
        $top_user->posid = (int)$now->id;
        $top_user->time = (int)$end;
        $DB->insert_record(self::TABLE_GOALSETTING_TOP_LIST, $top_user);


        $top =  $DB->get_records(
            self::TABLE_GOALSETTING_TOP,
            array('posid' => $now->id)
        );

        $sql = "UPDATE {" . self::TABLE_GOALSETTING_TOP . "} SET current = 0 WHERE current = 1";
        $DB->execute($sql);

        if (!$top) {
            $top_user = new stdClass;
            $top_user->posid = (int)$now->id;
            $top_user->count = 1;
            $top_user->current = 1;
            $top_user->next = 0;
            $DB->insert_record(self::TABLE_GOALSETTING_TOP, $top_user);
        } else {
            $sql = "UPDATE {" . self::TABLE_GOALSETTING_TOP . "} "
                 . "SET current = 1 "
                 . ", count = (SELECT COUNT(*) FROM {"
                    . self::TABLE_GOALSETTING_TOP_LIST . "} WHERE posid = ?) "
                 . "WHERE posid = ?";
            $DB->execute($sql, [$now->id,$now->id]);
        }
    }
}