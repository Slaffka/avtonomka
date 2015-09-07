<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 7/9/2015
 * Time: 12:13 PM
 */

class lm_settinggoals_plan_import {
    const TABLE_PLAN              = 'lm_settinggoals_plan';
    const TABLE_PLAN_KPI          = 'lm_settinggoals_plan_kpi';
    const TABLE_PLAN_AVEGAGEKGCOST  = 'lm_settinggoals_averagekgcost';
    const TABLE_KPI               = 'lm_kpi';
    const TABLE_TRADE_OUTLET      = 'lm_trade_outlets';

    const TAG_POSITIONS     = 'positions';
    const TAG_POSITION      = 'position';
    const TAG_TRADE_OUTLET  = 'tt';
    const TAG_KPI           = 'kpi';
    const TAG_AVEGAGEKGCOST = 'averagekgcost';

    private static $kpis;

    /**
     * @var lm_xml_reader $xml
     */
    private $xml = NULL;

    private $stages = array('plan', 'correct', 'fact');

    /**
     * Этап планирования, к которому относятся данные
     * @var string(plan|correct|fact)
     */
    private $stage;

    /**
     * @param stored_file|string $file file to upload
     */
    public function __construct($file) {
        $this->xml = new lm_xml_reader($file);
    }

    /**
     * Возвращает кол-во импортированных товаров
     * @param string $stage
     * @return FALSE|int
     * @throws Exception
     */
    public function import($stage = 'plan') {
        global $DB;

        if (in_array($stage, $this->stages)) $this->stage = $stage;
        else throw new Exception("Stage must be one of 'plan', 'correct', 'fact'");

        $date = null;

        $plan_ids = array();

        self::$kpis = $DB->get_records(self::TABLE_KPI, null, '', '`code`, `id`, `code`, `name`, `postid`');

        $i = 0;

        while ($tag = $this->xml->next()) {
            switch ($tag) {
                case self::TAG_POSITIONS:
                    $attrs = $this->xml->attrs();
                    if (empty($attrs['date'])) return FALSE;

                    $date = strtotime($attrs['date']);
                    break;

                case self::TAG_POSITION:
                    if ( ! $date) $this->xml->skip();

                    $attrs = $this->xml->attrs();
                    if ( ! $attrs['id']) $this->xml->skip();

                    $position = lm_position::by_code((string) $attrs['id']);
                    if ( ! $position) $this->xml->skip();

                    $plan_ids = array();

                    break;

                case self::TAG_TRADE_OUTLET:
                    if ( ! $position) return FALSE;

                    $outlet_data = $this->xml->object();
                    $outlet = $DB->get_record(self::TABLE_TRADE_OUTLET, array('code' => (string) $outlet_data['id']));
                    if ($outlet) {
                        if (!isset($position->id)) {
                            throw new Exception('Wrong structure of xml');
                        }
                        $plan = new stdClass();
                        $plan->positionid = (int) $position->id;
                        $plan->placeid    = (int) $outlet->id; // outletid
                        $plan->date       = $date; //date('Y-m-d H:i:s', $date);
                        $plan->id = $this->_savePlan($plan);

                        $plan_ids[] = $plan->id;

                        $values = array();
                        foreach ($outlet_data->kpis->kpi as $kpi) {
                            if (self::$kpis[(string) $kpi['id']]) {
                                $kpi_values = explode(',', (string) $kpi);
                                foreach ($kpi_values as $kpi_value) {
                                    if (is_numeric($kpi_value)) {
                                        $value = new stdClass();
                                        $value->planid = $plan->id;
                                        $value->kpiid = self::$kpis[(string)$kpi['id']]->id;
                                        $value->value = $kpi_value;

                                        $values[] = $value;
                                    }
                                }
                            }
                        }
                        $i += $this->_saveValues($values);
                    }

                    break;

                case self::TAG_AVEGAGEKGCOST:
                    if ( ! $position) return FALSE;
                    $value = $this->xml->value();
                    if (is_numeric($value)) {
                        $sql = "
                            INSERT INTO {".self::TABLE_PLAN_AVEGAGEKGCOST."} (positionid, date, value)
                            VALUES (:positionid, :date, :value)
                            ON DUPLICATE KEY UPDATE value = VALUES(value)
                        ";
                        $DB->execute($sql, array('positionid' => $position->id, 'date' => $date, 'value' => $value));

                        if ( ! is_null($plan) && $plan->id) {
                            $this->_update_earnings($plan_ids, $value);
                        }
                    }
                    break;
            }
        }

        return $i;
    }

    /**
     * Костыль для пересчета выручки относительно тоннажа, т.к. к нам в выручке приходят нули.
     * @param int[] $plan_ids
     * @param float $averagekgcoast
     */
    private function _update_earnings($plan_ids, $averagekgcoast) {
        global $DB;
        if ( ! empty($plan_ids) && isset(self::$kpis['10.1']) && isset(self::$kpis['10.2'])) {
            $DB->execute('
                UPDATE
                    {'.self::TABLE_PLAN_KPI.'} AS earn
                    JOIN {'.self::TABLE_PLAN_KPI.'} AS tonnage
                        ON tonnage.planid = earn.planid
                        AND tonnage.kpiid = :tonnageid
                        AND tonnage.stage = earn.stage
                SET earn.value = :averagekgcoast*tonnage.value
                WHERE
                    earn.planid in ('.implode(',', $plan_ids).')
                    AND earn.kpiid = :earningsid
                    AND earn.stage = :stage
            ', array(
                'averagekgcoast' => $averagekgcoast,
                'stage'          => $this->stage,
                'earningsid'     => self::$kpis['10.1']->id,
                'tonnageid'      => self::$kpis['10.2']->id
            ));
        }
    }

    private function _savePlan($item) {
        global $DB;

        // ищем существующюю (нам полюбому id нужен)
        $plan = $DB->get_record(self::TABLE_PLAN, (array) $item);

        // создаем новый
        if ( ! $plan) {
            $plan = $item;
            $plan->id = $DB->insert_record(self::TABLE_PLAN, $plan);
        }

        return $plan->id;
    }

    private function _saveValues($values) {
        global $DB;

        if (empty($values)) return 0;

        $DB->execute(
            'DELETE FROM {'.self::TABLE_PLAN_KPI.'} WHERE planid = ? AND stage = ?',
            array($values[0]->planid, $this->stage)
        );

        if (count($values)) {
            foreach ($values as &$value) {
                // need to prepare string before inserting
                //$item->name = mysqli_escape_string($item->name);
                $value = "('{$value->planid}', '{$value->kpiid}', '{$this->stage}', '{$value->value}')";
            }
            $sql = '
                INSERT INTO {' . self::TABLE_PLAN_KPI . '} (planid, kpiid, stage, value)
                VALUES ' . implode(',', $values) . '
                /*ON DUPLICATE KEY UPDATE value = VALUES(value)*/
            ';
            $DB->execute($sql);
        }

        return count($values);
    }
}