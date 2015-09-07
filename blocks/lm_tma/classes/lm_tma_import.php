<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 7/9/2015
 * Time: 12:13 PM
 */

class lm_tma_import {
    const TABLE_TMA           = 'lm_tma';
    const TABLE_TMA_AREA      = 'lm_tma_area';
    const TABLE_AREA          = 'lm_area';
    const TABLE_TRADE_OUTLETS = 'lm_trade_outlets';

    const TAG_TMA = 'promo';
    const TAG_POSITION = 'position';

    /**
     * @var lm_xml_reader $xml
     */
    private $xml = NULL;

    /**
     * @param stored_file|string $file file to upload
     */
    public function __construct($file) {
        $this->xml = new lm_xml_reader($file);
    }

    /**
     * Возвращает кол-во импортированных акций
     * @return int|FALSE
     */
    public function import() {
        global $DB;

        $i = 0;
        while ($this->xml->next(self::TAG_TMA)) {
            $item = $this->xml->object();
            $tmaid = $this->_save_tma($item);
            $this->_save_tma_areas($tmaid, $item->areas->areaid);
            $i++;
        }

        return $i;
    }
    
    private function _save_tma($item) {
        global $DB;

        $tma = $DB->get_record(self::TABLE_TMA, array('code' => (string) $item['id']));

        if ( ! $tma) $tma = new stdClass();

        $tma->code   = trim($item['id']);
        $tma->start  = date('Y-m-d', strtotime($item->datestart));
        $tma->end    = date('Y-m-d', strtotime($item->dateend));
        $tma->title  = trim($item->name);
        $tma->reward = 0;
        $tma->descr  = trim($item->descr);

        if (isset($tma->id)) $DB->update_record(self::TABLE_TMA, $tma);
        else $tma->id = $DB->insert_record(self::TABLE_TMA, $tma);

        return $tma->id;
    }

    private function _save_tma_areas($tmaid, $items) {
        global $DB;

        // отдельные массивы с кодами зон и торговых точек
        $area_codes = array();
        $to_codes = array();
        foreach ($items as $item) {
            if ($item['type'] == 'area') $area_codes[] = (string) $item;
            elseif ($item['type'] == 'to') $to_codes[] = (string) $item;
        }

        // массив запросов для выборки нужных зон и тт
        $selects = array();

        // запрос для выборки всех зон
        if ( ! empty($area_codes)) {
            $selects[] = '
                SELECT ' . $tmaid . ', id, 0 FROM {' . self::TABLE_AREA . '}
                WHERE code IN ("' . implode('","', $area_codes) . '")
            ';
        }

        // запрос для выборки всех тт
        if ( ! empty($to_codes)) {
            $selects[] = '
                SELECT '.$tmaid.', areaid, id FROM {'.self::TABLE_TRADE_OUTLETS.'}
                WHERE code IN ("'.implode('","', $to_codes).'")
            ';
        }

        // запрос для добавления связей tma c зонами и торговыми точками
        $sql = 'INSERT IGNORE INTO {'.self::TABLE_TMA_AREA.'} (tmaid, areaid, toid)'.implode('UNION', $selects);

        $DB->execute($sql);
    }
}