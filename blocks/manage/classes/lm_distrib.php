<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 4/16/2015
 * Time: 10:02 PM
 */

class lm_distrib {
    private static $i = NULL;
    protected $_update = array();
    protected static $distrib_cache = array();

    public $id = 0;
    public $code = 0;
    public $name = NULL;

    /**
     * @param $distribid
     * @return lm_distrib
     */
    static public function i($distribid){
        $distrib = 0;
        if($distribid && is_object($distribid)){
            $distrib = clone $distribid;
            $distribid = $distrib->id;
        }else if($distribid) {
            $distrib = $distribid;
        }

        if(!isset(self::$i[$distribid]) || !$distribid){
            self::$i[$distribid] = new lm_distrib($distrib);
        }

        return self::$i[$distribid];
    }

    public function __construct($distribid){
        global $DB;

        $distrib = null;

        if($distribid && is_object($distribid)){
            $distrib = $distribid;
        }else if($distribid) {
            $sql = "SELECT * FROM {lm_distrib} lp WHERE lp.id={$distribid}";
            $distrib = $DB->get_record_sql($sql);
        }

        if ($distrib) {
            foreach ($distrib as $field => $value) {
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

            $placeid = (int) $DB->insert_record('lm_distrib', $dataobj);
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

            return $DB->update_record('lm_distrib', $dataobj);
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

        $id = (int) $DB->get_field('lm_distrib', 'id', array('code'=>$code));
        return self::i($id);
    }

    public function get_id(){
        return $this->id;
    }

    public function get_name(){
        return $this->name;
    }


    public static function distribs(){
        global $DB;

        return $DB->get_records('lm_distrib', array(), 'name ASC');
    }

    public static function distrib_menu($key='id', $value='name'){
        global $DB;

        if( empty(self::$distrib_cache[$key.$value]) ){
            self::$distrib_cache[$key.$value] = $DB->get_records_menu('lm_distrib', array(), '', "{$key}, {$value}");
        }

        return self::$distrib_cache[$key.$value];
    }
}