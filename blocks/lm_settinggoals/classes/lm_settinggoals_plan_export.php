<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 7/9/2015
 * Time: 12:13 PM
 */

class lm_settinggoals_plan_export {
    const TABLE_PLAN         = 'lm_settinggoals_plan';
    const TABLE_PLAN_KPI     = 'lm_settinggoals_plan_kpi';
    const TABLE_KPI          = 'lm_kpi';
    const TABLE_TRADE_OUTLET = 'lm_trade_outlets';
    const TABLE_NOMENCLATURE = 'lm_nomenclature';

    const TAG_POSITIONS    = 'positions';
    const TAG_POSITION     = 'position';
    const TAG_TRADE_OUTLET = 'tt';
    const TAG_KPIS         = 'kpis';
    const TAG_KPI          = 'kpi';
    const TAG_COMMENT      = 'comment';
    const TAG_DATE         = 'date';

    const STATUS_ACCEPTED = 4;

    private $date = 0;

    /**
     * @var lm_xml_reader $xml
     */
    private $xml = NULL;

    /**
     * @var resource $file
     */
    private $file = NULL;

    /**
     * Справочник kpi
     * @var array
     */
    private $kpis;

    /**
     * @param $uri stored_file|string $file file to upload
     * @param bool $append
     * @param bool $use_indent
     * @throws Exeption
     */
    public function __construct($uri, $append = FALSE, $use_indent = FALSE) {
        $this->xml = new XMLWriter();
        if ($append && file_exists($uri)) {
            $this->file = fopen($uri, 'r+');
            if ( ! $this->file) throw new Exeption('Cant open file');
            if (fseek($this->file, -strlen('</'.self::TAG_POSITIONS.'>') - 1, SEEK_END) < 0) {
                throw new Exeption('File has wrong format');
            }
            $this->xml->openMemory();
        } else {
            touch($uri);
            $uri = realpath($uri);
            $this->xml->openUri($uri);
            $this->xml->startDocument('1.0', 'UTF-8');
        }

        if ($use_indent) {
            $this->xml->setIndent(true);
            $this->xml->setIndentString("\t");
        }
    }


    /**
     * Выгребает планы
     * @param $last_id
     * @param int $limit
     * @return array
     */
    private function _get_plans($positionid, $last_id, $limit = 10) {
        global $DB;

        $last_id = (int) $last_id;
        $limit   = (int) $limit;
        $plans_sql = '
            SELECT id, positionid, placeid, date
            FROM {'.self::TABLE_PLAN.'}
            WHERE
                id > :last_id
                '.($positionid > 0 ? 'AND positionid = :positionid':'').'
                AND state = :state
                AND FROM_UNIXTIME(date, "%Y-%m-%d") = :date
                /*AND date BETWEEN :date_from AND :date_to*/
            ORDER BY id, positionid
        ';

        $params = array(
            'last_id'   => $last_id,
            'state'     => self::STATUS_ACCEPTED,
            'date' =>  date("Y-m-d", $this->date),
            'date_from' => $this->date - $this->date%86400, //date('Y-m-d 00:00:00', $this->date),
            'date_to'   => $this->date - $this->date%86400 + 86399//date('Y-m-d 23:59:59', $this->date)
        );
        if ($positionid > 0) $params['positionid'] = $positionid;

        //echo $plans_sql;
        //print_r($params);
        $plans = $DB->get_records_sql(
            $plans_sql,
            $params,
            0,
            $limit
        );

        return $plans;
    }

    /**
     * Возвращает кол-во экспортированных корректировок
     * @param $date
     * @return FALSE|int
     */
    public function export($date, $positionid = NULL) {
        global $DB;

        $this->date = is_numeric($date) ? (int) $date : strtotime($date);

        $positionid = (int) $positionid;

        if (empty($this->file)) {
            $this->xml->startElement(self::TAG_POSITIONS);

            $this->xml->writeAttribute('date', date('Y-m-d', $this->date));
        }

        $this->kpis = $DB->get_records(self::TABLE_KPI, null, '', '`id`, `code`, `name`, `postid`, `uom`');

        $i = 0;

        $last_id = 0;
        $position = null;
        $outlet   = null;
        // обрабатываем пачками по 10 штук
        while ($plans = $this->_get_plans($positionid, $last_id, 10)) {
            foreach ($plans as $plan) {
                // если позиция сменилась
                if ( ! isset($position) || $position->id !== (int) $plan->positionid) {
                    // закрываем предыдущий position
                    if (isset($position)) {
                        $this->xml->fullEndElement();
                        if (is_resource($this->file)) {
                            fwrite($this->file, $this->xml->outputMemory());
                        } else {
                            $this->xml->flush();
                        }
                    }

                    // запоминаем и заполняем текущий position
                    $position = new stdClass();
                    $position->id = (int) $plan->positionid;
                    $position->positioncode = lm_position::get_code($plan->positionid);

                    $this->xml->startElement(self::TAG_POSITION);
                    $this->xml->writeAttribute('id', $position->positioncode);
                }

                // пишем данные по торговым точкам
                if ($position->positioncode) {
                    $outlet_code = $DB->get_field(self::TABLE_TRADE_OUTLET, 'code', array('id' => (int) $plan->placeid));
                    // если торговая точка сущестывует пишем планы по ней
                    if ($outlet_code) {
                        $this->xml->startElement(self::TAG_TRADE_OUTLET);
                        $this->xml->writeAttribute('id', $outlet_code);

                        // список kpi и их значения
                        $this->write_kpi($plan->id);

                        $this->xml->fullEndElement();
                    }
                }

                $last_id = $plan->id;
            }
        }

        // закрываем position
        if (isset($position)) $this->xml->fullEndElement();

        if (is_resource($this->file)) {
            fwrite($this->file, $this->xml->outputMemory());
            fwrite($this->file, '</'.self::TAG_POSITIONS.'>');
            fclose($this->file);
        } else {
            // закрываем positions
            $this->xml->fullEndElement();
        }

        return $i;
    }

    private function write_kpi($plan_id) {
        global $DB;

        $plan_id = (int) $plan_id;

        //$this->xml->startElement(self::TAG_KPIS);
        $this->xml->startElement(self::TAG_COMMENT);

        $plan_kpi_sql = '
            SELECT
                kpiid,
                GROUP_CONCAT(TRIM(TRAILING "." FROM TRIM(TRAILING "0" FROM value)) SEPARATOR ",") AS value,
                comment
            FROM {'.self::TABLE_PLAN_KPI.'}
            WHERE
                planid = :planid
                AND stage = :stage
            GROUP BY kpiid
        ';
        $plan_kpi = $DB->get_records_sql(
            $plan_kpi_sql,
            array(
                'planid' => $plan_id,
                'stage' => 'correct'
            )
        );

        $comment = array();
        foreach ($plan_kpi as $kpi_value) {
            if ($this->kpis[$kpi_value->kpiid]) {
                if ( $this->kpis[$kpi_value->kpiid]->code === '10.4') {
                    $nomenclature = $DB->get_records_select_menu(
                        self::TABLE_NOMENCLATURE,
                        'code IN ('.$kpi_value->value.')',
                        null,
                        '',
                        'id, name'
                    );
                    $name = 'Позиции для выполнения '.$this->kpis[$kpi_value->kpiid]->name;
                    $value = '';
                    foreach($nomenclature as $item) $value .= "\n\t- {$item}";
                } else {
                    $name = $this->kpis[$kpi_value->kpiid]->name;
                    $value = round($kpi_value->value, 1).$this->kpis[$kpi_value->kpiid]->uom;
                }
                $comment[] = $name.':'.$value;
                /*
                $this->xml->startElement(self::TAG_KPI);
                $this->xml->writeAttribute('id', $this->kpis[$kpi_value->kpiid]->code);
                $this->xml->text($kpi_value->value);
                $this->xml->fullEndElement();
                */
            }
        }
        $this->xml->text(implode("\n", $comment));

        $this->xml->fullEndElement();
    }
}