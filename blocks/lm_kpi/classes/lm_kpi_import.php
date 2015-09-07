<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 4/4/2015
 * Time: 3:16 PM
 */

defined('MOODLE_INTERNAL') || die();

class lm_kpi_import {

    const ORG_TABLE       = 'lm_position';
    const KPI_TABLE       = 'lm_kpi';
    const KPI_VALUE_TABLE = 'lm_kpi_value';

    /**
     * @var lm_kpi_xml $xml
     */
    private $xml = NULL;

    /**
     * @var array code => id
     */
    public $kpis = array();

    /**
     * @param stored_file|string $file file to upload
     */
    public function __construct($file) {
        $this->xml = new lm_kpi_xml($file);
    }

    private function _load_references() {
        global $DB;
        $this->kpis = $DB->get_records(self::KPI_TABLE, null, '', '`code`, `id`, `code`, `name`, `postid`');
        foreach($this->kpis as $kpi) {
            $kpi->active = FALSE;
        }
    }


    private function _find_kpi($kpi) {
        global $DB;
        $search_sql = '
            SELECT id
            FROM {'.self::KPI_VALUE_TABLE.'}
            WHERE
                kpiid = :kpiid
                AND posid = :posid
                AND userid = :userid
                AND date BETWEEN :date_from AND :date_to
            LIMIT 1
        ';
        $date = strtotime($kpi->date);
        $search_params = array(
            'kpiid'     => (int) $kpi->id,
            'posid'     => (int) $kpi->posid,
            'userid'    => (int) $kpi->userid,
            'date_from' => date('Y-m-01', $date),
            'date_to'   => date('Y-m-t', $date)
        );

        return (int) $DB->get_field_sql($search_sql, $search_params);
    }

    private function _save_kpi($kpi) {
        global $DB;

        // save kpi reference
        if (empty($this->kpis[$kpi->code])) {
            $new_kpi = new stdClass;
            $new_kpi->code   = (string) $kpi->code;
            $new_kpi->postid =    (int) $kpi->postid;
            $new_kpi->name   = (string) $kpi->name;
            $new_kpi->active = TRUE;
            $new_kpi->id = $DB->insert_record(self::KPI_TABLE, $new_kpi);
            if ( ! $new_kpi->id) throw new Exception("Can't save kpi");
            $this->kpis[$kpi->code] = $new_kpi;
        }
        $kpi->id = $this->kpis[$kpi->code]->id;

        if ($kpi->postid != $this->kpis[$kpi->code]->postid) {
            //TODO: Write about this to log
            //$this->kpis[$kpi->code]->postid = $kpi->postid;
            //$DB->set_field(self::KPI_TABLE, 'postid', $kpi->postid, array('id' => $kpi->id));
        }

        if ($kpi->name != $this->kpis[$kpi->code]->name) {
            $this->kpis[$kpi->code]->name = $kpi->name;
            $DB->set_field(self::KPI_TABLE, 'name', $kpi->name, array('id' => $kpi->id));
        }

        $this->kpis[$kpi->code]->active = TRUE;

        // save kpi value
        $new_value = new stdClass;
        $new_value->kpiid            =    (int) $kpi->id;
        $new_value->posid            =    (int) $kpi->posid;
        $new_value->userid           =    (int) $kpi->userid;
        $new_value->date             = (string) $kpi->date;
        $new_value->plan             =  (float) $kpi->plan;
        $new_value->fact             =  (float) $kpi->fact;
        $new_value->predict          =  (float) $kpi->predict;
        $new_value->dailyplan        =  (float) $kpi->dailyplan;
        $new_value->dailyplan_to_fit =  (float) $kpi->dailyplan_to_fit;
        if ($new_value->id = $this->_find_kpi($kpi)) {
            $DB->update_record(self::KPI_VALUE_TABLE, $new_value);
            return $new_value->id;
        } else {
            return $DB->insert_record(self::KPI_VALUE_TABLE, $new_value);
        }
    }

    private function update_kpi_activity() {
        global $DB;
        $active_kpis = array();
        foreach ($this->kpis as $kpi) {
            if ($kpi->active) $active_kpis[] = $kpi->id;
        }
        $sql = '
            UPDATE {'.self::KPI_TABLE.'} SET active = '.
            (empty($active_kpis) ? 'FALSE' : 'id IN ('.implode(',', $active_kpis).')');

        $DB->execute($sql);
    }

    /**
     * @return int|FALSE
     */
    public function import() {
        $this->_load_references();

        $imported = 0;

        while($employee = $this->xml->get_next_employee()) {
            $org = lm_position::by_code($employee->idposition);
            if ($org) {
                foreach ($employee->kpis as $kpi_value) {
                    $kpi_value->postid = $org->postid;
                    $kpi_value->posid  = $org->id;
                    $kpi_value->userid = $org->userid;
                    try {
                        $this->_save_kpi($kpi_value);
                        $imported++;
                        if ($org->userid) {
                            // увеличим в разы время импорта:
                            lm_notification::add('lm_kpi:update', TRUE, $org->userid);
                            // НЕХИЛО ТАК увеличим в время импорта:
                            foreach ($org->get_my_team() as $member) lm_notification::add('lm_myteam:kpi', TRUE, $member->id);
                        }
                    } catch (dml_exception $e) {
                        var_dump($e->getMessage());
                        return FALSE;
                        //TODO: wtire to log about this error
                    }
                }
            } else {
                //TODO: wtire to log about this error
                //throw new Exception('There is no org with code '.$employee['idposition'])
            }
        }

        $this->update_kpi_activity();

        return $imported;
    }
}