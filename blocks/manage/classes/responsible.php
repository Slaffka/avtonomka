<?php
class block_manage_responsible{
    static private $i = NULL;

    public $userid = 0;

    /**
     * @param $activityid
     * @return block_manage_responsible
     */
    static public function i($userid=0){
        global $USER;
        if(!$userid){
            $userid = $USER->id;
        }

        if(!isset(self::$i[$userid])){
            self::$i[$userid] = new block_manage_responsible($userid);
        }

        return self::$i[$userid];
    }

    public function __construct($userid){
        $this->userid = $userid;
    }

    /**
     * Возвращает массив партнеров, у которых ответственный $this->userid
     *
     * @return array
     */
    public function my_partners_menu(){
        global $DB;

        $sql = "SELECT lp.id, CONCAT_WS('', lc.name, ' (', lp.name, ')') as name
                      FROM {lm_partner} lp
                      JOIN {lm_place} lpl ON lpl.partnerid=lp.id
                      LEFT JOIN {lm_company} lc ON lp.companyid=lc.id
                      WHERE lpl.respid={$this->userid}
                      GROUP BY lp.id";

        return $DB->get_records_sql_menu($sql);
    }
}