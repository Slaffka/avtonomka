<?php

class lm_company
{
    static private $i = NULL;

    public $id = 0;
    public $name = NULL;
    /**
     * Синонимы используемые при импорте для сопоставления данных в БД и в импортируемом файле
     *
     * @var null
     */
    public $synonyms = NULL;
    public $type = 'partner';
    public $hide = 0;

    /**
     * @param $companyid
     * @return lm_company
     */
    static public function i($companyid){


        $company = 0;
        if($companyid && is_numeric($companyid)) {
            $company = $companyid;
        }else if($companyid && is_object($companyid)){
            $company = clone $companyid;
            $companyid = (integer) $companyid->id;
        }

        if(!isset(self::$i[$companyid]) || !$companyid){
            self::$i[$companyid] = new lm_company($company);
        }

        return self::$i[$companyid];
    }

    public function __construct($companyid){
        global $DB;

        $company = new StdClass();

        if($companyid && is_numeric($companyid)) {
            $company = $DB->get_record('lm_company', array('id' => $companyid));
        }else if($companyid && is_object($companyid)){
            $company = $companyid;
        }

        if($company) {
            foreach ($company as $field => $value) {
                $this->$field = $value;
            }
        }

        return $this;
    }

    /**
     * Создает новую компанию
     *
     * @return lm_company
     */
    public function create(){
        global $DB;

        if($id = $DB->insert_record('lm_company', $this)){
            return self::i($id);
        }

        return $this;
    }

    public function remove(){
        global $DB;

        $partners = $DB->get_records("lm_partner", array("companyid"=>$this->id));
        if($partners){
            foreach($partners as $partner){
                $partner = lm_partner::i($partner);
                $partner->remove();
            }
        }

        $DB->delete_records('lm_company', array('id'=>$this->id));

        unset($this);
    }

    /**
     * Обновляет информацию о компании в БД
     *
     * @return bool
     */
    public function update(){
        global $DB;

        if(!$this->id || !$this->name){
            return false;
        }

        return $DB->update_record('lm_company', $this);
    }

    /**
     * Изменить имя компании
     *
     * @param $name
     * @return $this
     */
    public function setName($name){
        if(!lm_companies::name_exists($name)) {
            $this->name = $name;
        }
        return $this;
    }

    public function synonyms(){
        $synonyms = trim($this->synonyms);
        if($synonyms) {
            $synonyms = explode(',', $this->synonyms);
        }

        return $synonyms;
    }

    public function setSynonyms(array $synonyms){
        $this->synonyms = $this->synonyms();

        foreach($synonyms as $synonym){
            $synonym = lm_companies::clean_name($synonym);
            if(!in_array($synonym, $this->synonyms) && !lm_companies::synonyms_exists($synonym)){
                $this->synonyms[] = $synonym;
            }
        }

        $this->synonyms = implode(',', $this->synonyms);

        return $this;
    }

    public function clear_synonyms(){
        $this->synonyms = NULL;

        return $this;
    }

    /**
     * Изменить тип компании (партнерская розница, собственная розница
     *
     * @param $type
     * @return $this
     */
    public function setType($type){
        $this->type = $type;
        return $this;
    }

    /**
     * Скрыть/заблокировать компанию
     *
     * @return $this
     */
    public function hide(){
        $this->hide = 1;
        return $this;
    }

    /**
     * Показать/разблокировать компанию
     *
     * @return $this
     */
    public function display(){
        $this->hide = 0;
        return $this;
    }

    /**
     * Возвращает массив с возможными типами партнера
     *
     * @return array
     */
    static public function get_types(){
        return array('partner'=>'Партнерская розница', 'own'=>'Собственная розница');
    }

    /**
     * Возвращает по коду название типа партнера
     *
     * @param $type
     * @return string
     */
    static public function get_type_name($type){
        if(!$type){
            $type = 'partner';
        }

        $types = self::get_types();
        if(isset($types[$type])){
            return $types[$type];
        }

        return '';
    }

    public function type_name(){
        return self::get_type_name($this->type);
    }
} 