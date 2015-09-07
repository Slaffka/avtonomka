<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 4/16/2015
 * Time: 10:02 PM
 */

class lm_segment {
    private static $i = NULL;
    protected $_update = array();
    protected static $segment_cache = array();

    public $id = 0;
    public $code = 0;
    public $name = NULL;

    /**
     * @param $segmentid
     * @return lm_segment
     */
    static public function i($segmentid){
        $segment = 0;
        if($segmentid && is_object($segmentid)){
            $segment = clone $segmentid;
            $segmentid = $segment->id;
        }else if($segmentid) {
            $segment = $segmentid;
        }

        if(!isset(self::$i[$segmentid]) || !$segmentid){
            self::$i[$segmentid] = new lm_segment($segment);
        }

        return self::$i[$segmentid];
    }

    public function __construct($segmentid){
        global $DB;

        $segment = null;

        if($segmentid && is_object($segmentid)){
            $segment = $segmentid;
        }else if($segmentid) {
            $sql = "SELECT * FROM {lm_segment} lp WHERE lp.id={$segmentid}";
            $segment = $DB->get_record_sql($sql);
        }

        if ($segment) {
            foreach ($segment as $field => $value) {
                $this->$field = $value;
            }
        }

        return $this;
    }

    public function create(){
        global $DB;

        $placeid = 0;
        if( !empty($this->_update) ) {
            $dataobj = new StdClass();
            foreach ($this->_update as $field) {
                $dataobj->$field = $this->$field;
            }

            $placeid = (int) $DB->insert_record('lm_segment', $dataobj);
        }

        return self::i($placeid);
    }

    public function update(){
        global $DB;

        if($this->_update) {
            $dataobj = new StdClass();
            $dataobj->id = $this->id;

            foreach ($this->_update as $field) {
                $dataobj->$field = $this->$field;
            }

            return $DB->update_record('lm_segment', $dataobj);
        }

        return false;
    }

    /**
     * Устанавливает новое значение для поля
     *
     * @param $fieldname
     * @param $value
     * @return $this
     */
    public function set($fieldname, $value){
        if( property_exists($this, $fieldname) ){
            $this->$fieldname = $value;
            $this->_update[] = $fieldname;
        }

        return $this;
    }

    public function set_code($code){
        $this->set('code', $code);
        return $this;
    }

    public function set_name($name){
        $this->set('name', $name);
        return $this;
    }

    public static function get_by_code($code){
        global $DB;

        $id = (int) $DB->get_field('lm_segment', 'id', array('code'=>$code));
        return self::i($id);
    }

    public function get_id(){
        return $this->id;
    }

    public function get_name(){
        return $this->name;
    }


    public static function segments(){
        global $DB;

        return $DB->get_records('lm_segment', array(), 'name ASC');
    }

    public static function segment_menu($key='id', $value='name'){
        global $DB;

        if( empty(self::$segment_cache[$key.$value]) ){
            self::$segment_cache[$key.$value] = $DB->get_records_menu('lm_segment', array(), '', "{$key}, {$value}");
        }

        return self::$segment_cache[$key.$value];
    }
}