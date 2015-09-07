<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 7/9/2015
 * Time: 12:13 PM
 */

class lm_area_import {
    const AREA_TABLE = 'lm_area';
    const TO_TABLE = 'lm_trade_outlets';

    const TAG_AREA = 'area';
    const TAG_TRADE_OUTLET = 'to';

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
     * Возвращает кол-во импортированных территорий
     * @return int|FALSE
     */
    public function import() {
        $imported = array(
            'area' => 0,
            'trade-outlet'   => 0
        );

        while ($this->xml->next(self::TAG_AREA)) {
            $area = $this->xml->object();
            $areaid = $this->_saveArea($area);
            $imported['area']++;
            foreach ($area->tos->to as $to) {
                $to->areaid = $areaid;
                $this->_saveTo($to);
                $imported['trade-outlet']++;
            }
        }
        return $imported;
    }

    private function _saveArea($item) {
        global $DB;

        // ищем по коду
        $area = $DB->get_record(self::AREA_TABLE, array('code' => (string) $item['id']));

        // создаем новый
        if ( ! $area) $area = new stdClass();

        $area->code = (string) $item['id'];
        $area->name = (string) $item->name;

        if (isset($area->id)) $DB->update_record(self::AREA_TABLE, $area);
        else $area->id = $DB->insert_record(self::AREA_TABLE, $area);

        return $area->id;
    }

    private function _saveTo($item) {
        global $DB;

        // ищем по коду
        $trade_outlet = $DB->get_record(self::TO_TABLE, array('code' => (string) $item['id']));

        // создаем новый
        if ( ! $trade_outlet) $trade_outlet = new stdClass();

        $trade_outlet->code    = (string) $item['id'];
        $trade_outlet->areaid  =    (int) $item->areaid;
        $trade_outlet->name    = (string) $item->name;
        $trade_outlet->address = (string) $item->adr;

        if (isset($trade_outlet->id)) $DB->update_record(self::TO_TABLE, $trade_outlet);
        else $trade_outlet->id = $DB->insert_record(self::TO_TABLE, $trade_outlet);

        return $trade_outlet->id;
    }
}