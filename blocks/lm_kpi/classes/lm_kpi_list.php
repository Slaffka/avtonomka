<?php

class lm_kpi_list {
    protected $posid = 0;
    protected $userid = 0;

    public function __construct($posid, $userid) {
        $this->posid = $posid;
        $this->userid = $userid;
    }

    /**
     * Возвращает актуальные значения KPI для данного сотрудника
     *
     * @return array|bool
     * @throws dml_missing_record_exception
     */
    public function get_latest(){
        global $DB;

        $params = array('posid'=>$this->posid, 'userid'=>$this->userid);
        $maxdate = $DB->get_field_select('lm_kpi_value', 'MAX(date)', 'posid=? AND userid=?', $params);

        // Если в БД нет информации за текущий месяц
        if( date("n", strtotime($maxdate)) != date("n") ){
            return false;
        }

        if($maxdate) {
            $sql = "SELECT lk.id, lk.name, lkv.plan, lkv.fact, lkv.predict, lkv.dailyplan, lkv.dailyplan_to_fit, lk.uom
                      FROM {lm_kpi_value} lkv
                      JOIN {lm_kpi} lk ON lk.id=lkv.kpiid
                      JOIN {lm_position} lp ON lp.id=lkv.posid AND lp.postid=lk.postid
                      WHERE lkv.posid=? AND lkv.userid=? AND lkv.date=? AND lk.active=1
                      ";

            return $DB->get_records_sql($sql, array('posid' => $this->posid, 'userid' => $this->userid, 'date' => $maxdate));
        }else{
            return false;
        }
    }

    /**
     * Возвращает  набор KPI, который есть у текущей должности
     *
     * @return array
     */
    public function items_by_pos(){
        global $DB;

        $sql = "SELECT lk.id, lk.name, 0 as plan, 0 as fact, 0 as predict, 0 as dailyplan, 0 as dailyplan_to_fit
                     FROM {lm_kpi} lk
                     JOIN {lm_position} lp ON lp.postid=lk.postid
                     WHERE lp.id={$this->posid} AND lk.active";

        return $DB->get_records_sql($sql);
    }

    /**
     * Возвращает информацию об определенном kpi сотрудника в историческом периоде (не включая текущий месяц)
     *
     * @param $kpiid
     * @return array
     */
    public function kpi_history($kpiid){
        global $DB;

        $sql = "SELECT id, plan, fact, date
                     FROM {lm_kpi_value}
                     WHERE kpiid=? AND userid=? AND posid=? AND date < ?
                     ORDER BY date ASC";

        $params = array('kpiid'=>$kpiid, 'userid'=>$this->userid, 'posid'=>$this->posid, 'date'=>date('Y-m'));
        return $DB->get_records_sql($sql, $params);
    }
}