<?php

class lm_rating {
    protected $posid = 0;
    protected $userid = 0;

    public static function i($posid, $userid){
        return new lm_rating($posid, $userid);
    }

    public static function me(){
        global $USER;

        $pos = lm_position::i();
        return new lm_rating($pos->id, $USER->id);
    }

    public function __construct($posid, $userid){
        $this->posid = $posid;
        $this->userid = $userid;
    }

    /**
     * Возвращает номер места в рейтинге в команде и общее кол-во мест
     *
     * @return object - св-во point - номер места, св-во total - общее кол-во мест
     */
    public function inteam(){
        return $this->get_point('team');
    }

    /**
     * Возвращает номер места в рейтинге по городу и общее кол-во мест
     *
     * @return object - св-во point - номер места, св-во total - общее кол-во мест
     */
    public function incity(){
        return $this->get_point('city');
    }

    /**
     * Возвращает номер места в рейтинге по региону и общее кол-во мест
     *
     * @return object - св-во point - номер места, св-во total - общее кол-во мест
     */
    public function inregion(){
        return $this->get_point('region');
    }

    /**
     * Возвращает номер места в рейтинге по стране и общее кол-во мест
     *
     * @return object - св-во point - номер места, св-во total - общее кол-во мест
     */
    public function incountry(){
        return $this->get_point('country');
    }

    /**
     * Возвращает номер места в рейтинге для данного сотрудника в команде/городе/регионе/стране и общее кол-во мест
     *
     * @param string $type - может принимать значения city/region/country
     * @return object - св-во point - номер места, св-во total - общее кол-во мест
     */
    protected function get_point($type=''){
        global $DB, $CFG;

        $join = "";
        $where = array();
        $pos = lm_position::i($this->userid);

        $date = $DB->get_field_sql("SELECT date FROM {lm_rating_metric_value} WHERE 1 GROUP BY date ORDER BY date DESC LIMIT 1");
        $date = explode("-", $date);
        $days = cal_days_in_month(CAL_GREGORIAN, $date[1], $date[0]);
        $where[] = "v.date BETWEEN '{$date[0]}-{$date[1]}-01' AND '{$date[0]}-{$date[1]}-{$days}'";

        switch ($type)
        {
            case 'team':
                $where[] = "p.parentid = {$pos->parentid}";
                break;

            case 'city':
                $where[] = "p.cityid = {$pos->cityid}";
                $where[] = "p.postid = {$pos->postid}";

                break;

            case 'region':
                $where[] = "p.postid = {$pos->postid}";

                if ( $region = $DB->get_field_select("lm_region", "parentid", "id = {$pos->cityid}") ) {
                    // TODO: город равен 0 - вероятно это ошибка, ее записывать в лог
                    $where[] = "r.parentid = {$region}";
                }else{
                    return (object) array('point'=>NULL, 'total'=>NULL);
                }

                $join = "LEFT JOIN {$CFG->prefix}lm_region as r ON r.id = p.cityid";
                break;

            case 'country':
                $where[] = "p.postid = {$pos->postid}";
                break;
        }

        return $this->query_point($where, $join);
    }


    /**
     *
     * @param $where
     * @param string $join
     * @return object
     */
    public function query_point($where, $join=''){
        global $CFG, $DB;

        $where = implode(" AND ", $where);

        $sql = "SELECT
                  v.posid, v.userid,
                  SUM(m.weight*v.bal) as avg

            FROM
                {$CFG->prefix}lm_rating_metric_value as v
                LEFT JOIN {$CFG->prefix}lm_rating_metric as m ON m.id = v.metricid
                LEFT JOIN {$CFG->prefix}lm_position as p ON p.id = v.posid AND p.cityid != 0
                LEFT JOIN {$CFG->prefix}lm_position_xref as px ON px.posid = p.id AND px.archive = 0
                LEFT JOIN {$CFG->prefix}user as u ON u.id = v.userid
                LEFT JOIN {$CFG->prefix}lm_user as lu ON lu.userid = v.userid
                {$join}
            WHERE
                 {$where} AND v.userid != 0
            GROUP BY v.posid
            ORDER BY
                 avg DESC,
                 m.weight DESC,
                 v.bal DESC,
                 u.lastname DESC,
                 v.userid DESC";

        $users = $DB->get_records_sql($sql);
        $point = 0;
        if ( !empty($users) ) {
            foreach ($users as $key => $user) {
                $point++;
                if ($user->userid == $this->userid) {
                    break;
                }
            }
        }

        return (object) array('point'=>$point, 'total'=>count($users));
    }


    /**
     * Возвращает средневзвешенный бал для данного сотрудника по месяцу
     *
     * @param $year
     * @param $month
     * @return mixed
     * @throws dml_missing_record_exception
     * @throws dml_multiple_records_exception
     */
    public function avg_by_month($year, $month){
        global $DB, $CFG;

        $month = $year.'-'.$month;
        $where = "v.date LIKE '{$month}%' ";

        $sql = "
            SELECT
                v.posid, v.userid as uid, v.date as `date`,
                SUM(m.weight*v.bal) as avg
            FROM
               {$CFG->prefix}lm_rating_metric_value as v
                LEFT JOIN {$CFG->prefix}lm_rating_metric as m ON m.id = v.metricid
                LEFT JOIN {$CFG->prefix}lm_position as p ON p.id = v.posid
                LEFT JOIN {$CFG->prefix}lm_position_xref as px ON px.posid = p.id AND px.archive  = 0
                LEFT JOIN {$CFG->prefix}user as u ON u.id = v.userid
            WHERE
                v.userid = {$this->userid} AND {$where}
            GROUP BY v.posid
            ORDER BY
                 avg DESC,
                 m.weight DESC,
                 v.bal DESC,
                 u.lastname DESC,
                 v.userid DESC
        ";

        $user = $DB->get_record_sql($sql);
        return $user->avg;
    }

    /**
     * Возвращает информацию о средневзвешенных баллах для данного сотрудника за последние 6 месяцев
     * @param $count
     * @return array - первый элемент массива - список месяцев, второй элемент массива - список средневзвешенных баллов
     */
    public function avg_by_last_months($count=6){
        $avg = array();
        $months = array();
        for($i=1; $i<=$count; $i++){
            list($year, $month) = calc_date($count-$i+1);
            $avg[] = $this->avg_by_month($year, $month);
            $months[] = date('Y.m', strtotime($year.'-'.$month));
        }

        return array($months, $avg);
    }


    public function avg_by_previous_month(){
        $avg = array();

        list($year, $month) = calc_date(7);
        $avg = $this->avg_by_month($year, $month);

        return $avg ;
    }

} 