<?php

class lm_city
{
    static private $i = NULL;

    public $id = 0;
    public $code = '';
    public $name = "";
    /**
     * Синонимы используемые при импорте для сопоставления данных в БД и в импортируемом файле
     *
     * @var null
     */
    public $synonyms = "";
    public $translitname = "";

    /**
     * Идентификатор региона
     *
     * @var int
     */
    public $parentid = 0;

    /**
     * Кэш распознанных городов по имени
     *
     * @var array
     */
    private static $recognized = array();

    /**
     * @param $cityid
     * @return lm_city
     */
    static public function i($cityid=0){
        if(!isset(self::$i[$cityid]) || !$cityid){
            self::$i[$cityid] = new lm_city($cityid);
        }

        return self::$i[$cityid];
    }

    public function __construct($cityid = FALSE){
        global $DB;

        if($cityid) {
            if ($city = $DB->get_record('lm_region', array('id' => $cityid))) {
                foreach ($city as $field => $value) {
                    $this->$field = $value;
                }
            }
        }

        return $this;
    }

    /**
     * @return bool|lm_city
     */
    public function get_parent() {
        if ($this->parentid) return self::i($this->parentid);
        else return FALSE;
    }

    /**
     * Создает новый город
     *
     * @return lm_city
     */
    public function create($parentid = NULL){
        global $CFG, $DB;

        $this->parentid = $parentid ? $parentid : $CFG->lm_defaultregion;

        $this->translitname = ru2lat($this->name);
        if ($this->id = $DB->insert_record('lm_region', $this)) {
            return self::i($this->id);
        }

        return $this;
    }

    /**
     * Обновляет информацию о компании в БД
     *
     * @return bool
     */
    public function update(){
        global $DB;

        if(!$this->id){
            return false;
        }

        return $DB->update_record('lm_region', $this);
    }

    /**
     * Возвращает идентификатор города
     *
     * @return int
     */
    public function get_id(){
        return $this->id;
    }

    /**
     * Возвращает идентификатор города
     *
     * @return int
     */
    public static function get_id_by_code($code){
        global $DB;
        return $DB->get_field('lm_region', 'id', array('code' => $code));
    }

    /**
     * Изменить имя компании
     *
     * @param $name
     * @return $this
     */
    public function setName($name){
        $this->name = $name;
        return $this;
    }

    public function setSynonyms(array $synonyms){
        $this->synonyms = trim($this->synonyms);
        if($this->synonyms) {
            $this->synonyms = explode(',', $this->synonyms);
        }

        if($this->synonyms){
            foreach($synonyms as $synonym){
                if(!in_array($synonym, $this->synonyms)){
                    $this->synonyms[] = $synonym;
                }
            }
        }else{
            $this->synonyms = $synonyms;
        }

        $this->synonyms = implode(',', $this->synonyms);

        return $this;
    }

    public static function get_menu($conditions = array(), $sort = '', $fields = '*', $limitfrom = 0, $limitnum = 0) {
        global $DB;

        return $DB->get_records_menu('lm_region', $conditions, $sort, $fields, $limitfrom, $limitnum);
    }


    /**
     * @param array $conditions
     * @param string $sort
     * @param int $limitfrom
     * @param int $limitnum
     * @return self[]
     */
    public static function get_list($conditions = array(), $sort = '', $limitfrom = 0, $limitnum = 0) {
        global $DB;

        $rawCities = $DB->get_records('lm_region', $conditions, $sort, '*', $limitfrom, $limitnum);
        $cities = array();
        foreach ($rawCities as $rawCity) {
            $city = new self;
            foreach ($rawCity as $field => $value) {
                $city->$field = $value;
            }
            $cities[] = $city;
        }

        return $cities;
    }

    /**
     * Определяет идентификатор города по названию
     *
     * @param $name
     * @return mixed
     */
    public static function recognize($name, $cacheifnotfound=true)
    {
        global $DB;

        if (isset(self::$recognized[$name])) {
            return self::$recognized[$name];
        }

        $sql = "SELECT DISTINCT(id)
                  FROM {lm_region}
                  WHERE (synonyms LIKE '{$name}' OR synonyms LIKE '{$name},' OR synonyms LIKE ',{$name},' OR synonyms LIKE ',{$name}')
                  OR name LIKE '{$name}' OR translitname LIKE '{$name}'";

        $city = $DB->get_records_sql($sql);
        if (!$city) {
            if(!$cacheifnotfound) {
                return false;
            }

            self::$recognized[$name] = false;
        } else if (count($city) > 1) {
            self::$recognized[$name] = NULL;
        } else {
            $city = array_pop($city);
            self::$recognized[$name] = $city->id;
        }

        return self::$recognized[$name];
    }
} 