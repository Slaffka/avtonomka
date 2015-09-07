<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 7/9/2015
 * Time: 12:13 PM
 */

class lm_region_import {
    const TABLE = 'lm_region';

    const TAG_REGION = 'area';

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
     * Возвращает кол-во импортированных регионов
     * @return int|FALSE
     */
    public function import() {
        $i = 0;
        while ($this->xml->next(self::TAG_REGION)) {
            $item = $this->xml->object();
            $regionid = $this->_save($item);
            $i++;
            foreach ($item->cities->city as $item) {
                $item->parentid = $regionid;
                $this->_save($item);
                $i++;
            }
        }
        return $i;
    }

    private function _save($item) {
        // ищем по коду
        $region = lm_city::get_list(array('code' => (string) $item['id']), '', 0, 1)[0];

        // ищем по названию
        if ( ! $region) {
            $id = lm_city::recognize((string) $item->name);
            if ($id) $region = new lm_city($id);
        }

        // создаем новый
        if ( ! $region) $region = new lm_city;

        $region->code     = (string) $item['id'];
        $region->name     = (string) $item->name;
        $region->synonyms = (string) $item->synonyms;
        if ($item->parentid) $region->parentid = (int) $item->parentid;

        if ($region->id) $region->update();
        else $region->create((int) $item->parentid);

        return $region->id;
    }
}