<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 7/9/2015
 * Time: 12:13 PM
 */

class lm_tma_results_import {
    const RESULTS_TABLE = 'lm_tma_results';
    const TMA_TABLE = 'lm_tma';

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
     * Возвращает кол-во импортированных товаров
     * @return int|FALSE
     */
    public function import() {
        global $DB;
        $items = array();

        $i = 0;
        while ($this->xml->next(self::TAG_POSITION)) {
            $item = $this->xml->object();
            if ($position = lm_position::by_code((string) $item['id'])) {
                foreach ($item->promo as $promo) {
                    if ($promoid = $DB->get_field(self::TMA_TABLE, 'id', array('code' => (string) $promo['id']))) {
                        $result = new stdClass();
                        $result->tmaid     =   (int) $promoid;
                        $result->posxrefid =   (int) $position->posxrefid;
                        $result->plan      = (float) $promo->plan;
                        $result->fact      = (float) $promo->fact;

                        $items[] = $result;
                    }
                }
                $i += $this->_write($items);
                $items = array();
            }
        }

        return $i;
    }

    private function _write($items) {
        global $DB;

        foreach ($items as &$item) {
            //TODO: need to prepare string before inserting
            //$item->name = mysqli_escape_string($item->name);
            $item = "('{$item->tmaid}', '{$item->posxrefid}', /*'{$item->plan}',*/ '{$item->fact}')";
        }
        $sql = '
            INSERT INTO {'.self::RESULTS_TABLE.'} (tmaid, posxrefid, /*plan,*/ fact)
            VALUES '.implode(',',$items).'
            ON DUPLICATE KEY UPDATE /*plan = VALUES(plan),*/ fact = VALUES(fact)
        ';
        $DB->execute($sql);

        return count($item);
    }
}