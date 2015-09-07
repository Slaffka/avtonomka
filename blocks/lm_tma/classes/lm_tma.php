<?php

require_once($CFG->dirroot.'/user/lib.php');

class lm_tma extends stdClass
{
    private static $i = NULL;

    public $reward = 0;
    public $start = "";
    public $end = "";
    public $descr = "";
    public $title = "";
    public $fact = 0;
    public $plan = 0;
    public $posxrefid = 0;
    public $areaid = 0;

    /**
     * @param $id
     * @return lm_tma|NULL
     */
    public static function i($id)
    {
        if (!isset(self::$i[$id]) || !$id) {
            self::$i[$id] = new lm_tma($id);
        }

        return self::$i[$id];
    }

    public function __construct($id)
    {
        global $DB;

        $sql = "SELECT lt.*, lta.areaid
                FROM {lm_tma} lt
                JOIN {lm_tma_area} lta ON lta.tmaid = {$id}
                WHERE lt.id = {$id}";
        if ( $tma = $DB->get_record_sql($sql)) {
            foreach ($tma as $field => $value) {
                $this->$field = $value;
            }
        } else {
            return NULL;
        }
    }

    /**
     * Получить первых 50 пользователей по зоне, на которую распрастранена Акция
     * @param string $q
     * @return array
     */
    public function get_list_users($q = "")
    {
        global $DB;
        $like = "";
        if ( $q ) {
            $like = "(u.firstname LIKE '%{$q}%' OR u.lastname LIKE '%{$q}%') AND";
        }
        $sql = "SELECT u.*
            FROM {lm_tma_area} lta
            JOIN {lm_position} lp
              ON lp.areaid = lta.areaid AND lta.tmaid = {$this->id}
            JOIN {lm_position_xref} lpx
             ON lpx.posid = lp.id
            JOIN {user} u
              ON lpx.userid = u.id
            WHERE {$like} u.deleted=0 AND u.id != 1
                 ORDER BY u.lastname ASC
				 LIMIT 0, 50";
        $users = $DB->get_records_sql($sql);

        return $users;
    }

    /**
     * Получить первые 50 ТТ для данной акции
     * @param string $q
     * @return array
     */
    public function get_list_all_tt($q = "")
    {
        global $DB;
        $like = "";
        if ( $q ) {
            $like = "lto.name LIKE '%{$q}%' AND ";
        }
        $sql = "SELECT lto.*
                FROM {lm_tma_area} lta
                JOIN {lm_trade_outlets} lto ON lta.toid = lto.id  OR (lto.areaid = lta.areaid AND lta.toid = 0)
                WHERE {$like} tmaid = {$this->id}
                ORDER BY toid ASC LIMIT 0, 50";
        $tts = $DB->get_records_sql($sql);

        return $tts;
    }

    public static function tma_for_user($userid=0, $search_string="")
    {
        global $DB, $USER;
        if ( !$userid ) {
            $userid = $USER->id;
        }
        $pos = lm_position::i($userid);
        $time = date("Y-m-d", time());
        $wheredate = "lt.start <= '{$time}' AND lt.end > '{$time}'";
        $where = "";
        if ( $search_string ) {
            $where = " AND title LIKE '%{$search_string}%'";
        }
        $sql = "SELECT DISTINCT(lt.id), lt.*, ltr.fact, ltr.plan
                    FROM `mdl_lm_tma` lt
                    JOIN `mdl_lm_tma_area` lta ON lta.tmaid = lt.id AND lta.areaid = {$pos->areaid}
                    LEFT JOIN `mdl_lm_tma_results` ltr ON ltr.tmaid = lt.id AND ltr.posxrefid = {$pos->posxrefid}
                    WHERE {$wheredate} {$where}";

        if ( $tmas = $DB->get_records_sql($sql) ) {
            return $tmas;
        }
        return false;
    }

}