<?php

class lm_post
{
    private static $i = NULL;
    protected $_update = array();
    protected static $roles_cache = array();

    public $id = 0;
    public $code = 0;
    public $name = NULL;
    public $roleid = 0;
    public $parent = 0;
    public $evolution_stages_enabled = 0;

    /**
     * @param $postid
     * @return lm_post
     */
    static public function i($postid){
        $post = 0;
        if($postid && is_object($postid)){
            $post = clone $postid;
            $postid = $post->id;
        }else if($postid) {
            $post = $postid;
        }

        if(!isset(self::$i[$postid]) || !$postid){
            self::$i[$postid] = new lm_post($post);
        }

        return self::$i[$postid];
    }

    public function __construct($postid){
        global $DB;

        $post = null;

        if($postid && is_object($postid)){
            $post = $postid;
        }else if($postid) {
            $sql = "SELECT * FROM {lm_post} lp WHERE lp.id={$postid}";
            $post = $DB->get_record_sql($sql);
        }

        if ($post) {
            foreach ($post as $field => $value) {
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

            $placeid = (int) $DB->insert_record('lm_post', $dataobj);
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

            return $DB->update_record('lm_post', $dataobj);
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

    public function set_evolution_stages($isenabled){
        $this->set('evolution_stages_enabled', $isenabled);
        return $this;
    }

    public function is_evolution_stages_enabled(){
        if($this->evolution_stages_enabled){
            return true;
        }

        return false;
    }

    public static function get_by_code($code){
        global $DB;

        $id = (int) $DB->get_field('lm_post', 'id', array('code'=>$code));
        return self::i($id);
    }

    public function get_id(){
        return $this->id;
    }

    public function get_name(){
        return $this->name;
    }


    public static function posts(){
        global $DB;

        return $DB->get_records('lm_post', array(), 'name ASC');
    }

    public static function post_menu($key='id', $value='name'){
        global $DB;

        if( empty(self::$roles_cache[$key.$value]) ){
            self::$roles_cache[$key.$value] = $DB->get_records_menu('lm_post', array(), '', "{$key}, {$value}");
        }

        return self::$roles_cache[$key.$value];
    }


    public function switch_mode()
    {
        if($this->evolution_stages_enabled){
            $this->set_evolution_stages(0);
        }else{
            $this->set_evolution_stages(1);
        }

        return $this->update();
    }
}