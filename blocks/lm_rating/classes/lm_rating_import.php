<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 4/4/2015
 * Time: 3:16 PM
 */

defined('MOODLE_INTERNAL') || die();

class lm_rating_import {

    const ORG_TABLE          = 'lm_position';
    const METRIC_TABLE       = 'lm_rating_metric';
    const METRIC_VALUE_TABLE = 'lm_rating_metric_value';
    const PARAM_TABLE        = 'lm_rating_param';
    const PARAM_VALUE_TABLE  = 'lm_rating_param_value';

    /**
     * @var lm_rating_xml $xml
     */
    private $xml = NULL;

    /**
     * @var array code => id
     */
    public $metrics = array();
    public $params  = array();

    /**
     * @param stored_file|string $file file to upload
     */
    public function __construct($file) {
        $this->xml = new lm_rating_xml($file);
    }

    private function _load_references() {
        global $DB;
        $this->metrics = $DB->get_records(self::METRIC_TABLE, null, '', '`code`, `id`, `code`, `name`, `weight`');
        $this->params  = $DB->get_records(self::PARAM_TABLE,  null, '', '`code`, `id`, `metricid`, `code`, `name`');

    }


    private function _save_param($param) {
        global $DB;

        // save metric reference
        if (empty($this->params[$param->code])) {
            $new_param = new stdClass;
            $new_param->metricid =    (int) $param->metricid;
            $new_param->code     = (string) $param->code;
            $new_param->name     = (string) $param->name;
            $param->id = $DB->insert_record(self::PARAM_TABLE, $new_param);
            if ( ! $param->id) throw new Exception("Can't save param");
            $this->params[$param->code] = $param;
        } else {
            $param->id = $this->params[$param->code]->id;
        }

        // save param value
        $search_conds = array(
            'paramid'         => $param->id,
            'metric_value_id' => $param->metric_value_id
        );
        if ($id = (int) $DB->get_field(self::PARAM_VALUE_TABLE, 'id', $search_conds)) {
            return $id;
        } else {
            $new_value = new stdClass;
            $new_value->paramid          =   (int) $param->id;
            $new_value->metric_value_id  =   (int) $param->metric_value_id;
            $new_value->value            = (float) $param->value;
            return (int) $DB->insert_record(self::PARAM_VALUE_TABLE, $new_value);
        }
    }
    
    private function _find_metric_value($metric) {
        global $DB;
        $search_sql = '
            SELECT id
            FROM {'.self::METRIC_VALUE_TABLE.'}
            WHERE
                metricid = :metricid
                AND posid = :posid
                AND userid = :userid
                AND date BETWEEN :date_from AND :date_to
            LIMIT 1
        ';
        $date = strtotime($metric->date);
        $search_params = array(
            'metricid'  => (int) $metric->id,
            'posid'     => (int) $metric->posid,
            'userid'    => (int) $metric->userid,
            'date_from' => date('Y-m-01', $date),
            'date_to'   => date('Y-m-t', $date)
        );

        return (int) $DB->get_field_sql($search_sql, $search_params);
    }
    private function _save_metric($metric) {
        global $DB;

        // save metric reference
        if (empty($this->metrics[$metric->code])) {
            $new_metric = new stdClass;
            $new_metric->code   = (string) $metric->code;
            $new_metric->postid =    (int) $metric->postid;
            $new_metric->name   = (string) $metric->name;
            $new_metric->weight =  (float) $metric->weight;
            $metric->id = $DB->insert_record(self::METRIC_TABLE, $new_metric);
            if ( ! $metric->id) throw new Exception("Can't save metric");
            $this->metrics[$metric->code] = $metric;
        } else if ($metric->weight != $this->metrics[$metric->code]->weight) {
            //TODO: wtire to log about this error
            $metric->id = $this->metrics[$metric->code]->id;
            //$this->metrics[$metric->code]->weight = $metric->weight;
            //$DB->set_field(self::METRIC_TABLE, 'weight', $metric->weight, array('id' => $metric->id));
        } else {
            $metric->id = $this->metrics[$metric->code]->id;
        }

        // save metric value
        $new_value = new stdClass;
        $new_value->metricid =    (int) $metric->id;
        $new_value->posid    =    (int) $metric->posid;
        $new_value->userid   =    (int) $metric->userid;
        $new_value->date     = (string) $metric->date;
        $new_value->value    =  (float) $metric->rate;
        $new_value->bal      =  (float) $metric->bal;
        if ($new_value->id = $this->_find_metric_value($metric)) {
            $DB->update_record(self::METRIC_VALUE_TABLE, $new_value);
            return $new_value->id;
        } else {
            return $DB->insert_record(self::METRIC_VALUE_TABLE, $new_value);
        }
    }

    /**
     * @return FALSE
     */
    public function import() {
        $this->_load_references();

        $imported = array(
            'metric'       => 0,
            'param'        => 0
        );

        while($employee = $this->xml->get_next_employee()) {
            $org = lm_position::by_code($employee->idposition);
            if ($org) {
                foreach ($employee->metrics as $metric_value) {
                    $metric_value->postid = $org->postid;
                    $metric_value->posid  = $org->id;
                    $metric_value->userid = $org->userid;
                    try {
                        $metric_value_id = $this->_save_metric($metric_value);
                        $imported['metric']++;
                    } catch (dml_exception $e) {
                        var_dump($e->getMessage());
                        break;
                        //TODO: wtire to log about this error
                    }
                    foreach ($metric_value->params as $param) {
                        $param->metricid        = $this->metrics[$metric_value->code]->id;
                        $param->metric_value_id = $metric_value_id;
                        try {
                            $this->_save_param($param);
                            $imported['param']++;
                            if ($org->userid) {
                                // увеличим в разы время импорта:
                                lm_notification::add('lm_rating:update', TRUE, $org->userid);
                                // НЕХИЛО ТАК увеличим в время импорта:
                                foreach ($org->get_my_team() as $member) lm_notification::add('lm_myteam:rating', TRUE, $member->id);
                            }
                        } catch (dml_exception $e) {
                            var_dump($e->getMessage());
                            break;
                            //TODO: wtire to log about this error
                        }
                    }
                }
            } else {
                //TODO: wtire to log about this error
                //throw new Exception('There is no org with code '.$employee['idposition'])
            }
        }
        return $imported;
    }
}