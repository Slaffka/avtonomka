<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 7/9/2015
 * Time: 12:13 PM
 */

class lm_nomenclature_import {
    const TABLE = 'lm_nomenclature';

    const TAG_NOMENCLATURE = 'item';

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
     * Возвращает кол-во импортированных товаров
     * @return int|FALSE
     */
    public function import() {
        $items = array();

        $packet_size = 100;
        $i = 0;
        while ($this->xml->next(self::TAG_NOMENCLATURE)) {
            $item = $this->xml->object();
            $items[] = $item;
            if (++$i % $packet_size === 0) {
                $this->_write($items);
                $items = array();
            }
        }
        if ( ! empty($items)) {
            $this->_write($items);
        }

        return $i;
    }

    private function _write($items) {
        global $DB;

        foreach ($items as &$item) {
            // need to prepare string before inserting
            //$item->name = mysqli_escape_string($item->name);
            $item = "('{$item['id']}', '{$item->name}')";
        }
        $sql = '
            INSERT INTO {'.self::TABLE.'} (code, name)
            VALUES '.implode(',',$items).'
            ON DUPLICATE KEY UPDATE name = VALUES(name)
        ';
        $DB->execute($sql);
    }
}