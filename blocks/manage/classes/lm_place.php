<?php
class lm_place{
    static private $i = NULL;

    protected $city = "";
    protected $_update = array();

    /**
     * Уникальный идентификатор места, генерируется автоматически при создании
     *
     * @var int
     */
    public $id = 0;

    /**
     * Код ТТ (торговой точки)
     * @var string
     */
    public $code = '';

    /**
     * Тип места, возможные значения: класс, торговая точка (class, tt)
     * @var string
     */
    public $type = '';

    /**
     * Идентификатор партнера, которому принаджелит место (класс или торговая точка)
     *
     * @var string
     */
    public $partnerid = "";

    /**
     * Сырой адрес в произвольном текстовом формате, как пришло из выгрузки
     *
     * @var string
     */
    public $rawaddress = "";

    /**
     * Идентификатор города, в котором находится место
     *
     * @var int
     */
    public $cityid = 0;

    /**
     * Почтовый индекс
     * @var int
     */
    public $postcode = 0;

    /**
     * Вместимость. Указывается для классов, сколько может вместить людей
     *
     * @var int
     */
    public $capacity = 0;

    /**
     * Название станции метро (если есть)
     *
     * @var string
     */
    public $metro = "";

    /**
     * Название улицы, проспекта, переулка и т.п.
     * @var string
     */
    public $street = "";

    /**
     * Номер дома
     *
     * @var string
     */
    public $num = "";

    /**
     * Строение
     *
     * @var string
     */
    public $bld = "";

    /**
     * Корпус
     *
     * @var string
     */
    public $corp = "";

    /**
     * Этаж
     *
     * @var string
     */
    public $floor = "";

    /**
     * Название места
     *
     * @var string
     */
    public $name = "";

    /**
     * Свободный комментарий
     *
     * @var string
     */
    public $comment = "";
    public $comment2 = "";

    /**
     * Ид пользователя ответственного за ТТ (торговую точку)
     * @var int
     */
    public $respid = 0;

    /**
     * ИД пользователя, который является территориальным менеджером
     * @var int
     */
    public $tmid = 0;

    public $trainerid = 0;

    public $repid = 0;

    /**
     * ФИО контактного лица
     *
     * @var string
     */
    public $contactname = "";

    /**
     * Номер телефона контактного лица
     *
     * @var string
     */
    public $contactphone = "";

    /**
     * Электронная почта контактного лица
     *
     * @var string
     */
    public $contactemail = "";

    /**
     * Наличие флипчарта в классе. 0 - нет, 1 - есть
     *
     * @var int
     */
    public $flipchart = 0;

    /**
     * Наличие проектора в классе. 0 - нет, 1 - есть
     *
     * @var int
     */
    public $projector = 0;

    /**
     * Наличие wifi в классе. 0 - нет, 1 - есть.
     *
     * @var int
     */
    public $wifi = 0;

    /**
     * Наличие куллера в классе. 0 - нет, 1 - есть.
     *
     * @var int
     */
    public $cooler = 0;

    /**
     * @param $placeid
     * @return lm_place
     */
    static public function i($placeid){
        $place = 0;
        if($placeid && is_object($placeid)){
            $place = clone $placeid;
            $placeid = $place->id;
        }else if($placeid) {
            $place = $placeid;
        }

        if(!isset(self::$i[$placeid]) || !$placeid){
            self::$i[$placeid] = new lm_place($place);
        }

        return self::$i[$placeid];
    }

    private function __construct($placeid){
        global $DB;

        $place = null;

        if($placeid && is_object($placeid)){
            $place = $placeid;
        }else if($placeid) {
            $sql = "SELECT lp.*, lr.name as city
                           FROM {lm_place} lp
                           LEFT JOIN {lm_region} lr ON lp.cityid=lr.id
                           WHERE lp.id={$placeid}";

            $place = $DB->get_record_sql($sql);
        }

        if ($place) {
            foreach ($place as $field => $value) {
                $this->$field = $value;
            }
        }

        return $this;
    }

    /**
     * Находит территорию по коду
     *
     * @param $code
     * @return lm_place
     */
    public static function get_by_code($code){
        global $DB;

        $id = (int) $DB->get_field('lm_place', 'id', array('code'=>$code));
        return self::i($id);
    }

    /**
     * Создает новую локацию и возвращает экземпляр класса lm_place в случае успеха
     *
     * @return lm_place
     */
    public function create(){
        global $DB;

        $placeid = 0;

        if( !empty($this->_update) ) {
            $dataobj = new StdClass();
            foreach ($this->_update as $field) {
                $dataobj->$field = $this->$field;
            }

            $placeid = (int) $DB->insert_record('lm_place', $dataobj);
        }


        return self::i($placeid);
    }



    /**
     * Возвращает ссылку на локацию
     *
     * @return string
     */
    public function link(){
        global $CFG;
        return $CFG->wwwroot.'/blocks/manage/?_p=places&id='.$this->id;
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

    /**
     * Устанавливает новые значения для полей из $dataobject
     *
     * @param $dataobject
     */
    public function set_group($dataobject){
        if(is_object($dataobject)){
            foreach($dataobject as $field=>$value){
                $this->set($field, $value);
            }
        }

        return $this;
    }

    public function set_code($code){
        $this->set('code', $code);
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function set_name($name){
        $this->set('name', $name);
        return $this;
    }

    public function set_city($cityid){
        $this->set('cityid', $cityid);

        return $this;
    }

    public function set_partnerid($partnerid){
        $this->set('partnerid', $partnerid);

        return $this;
    }

    public function set_type($type){
        $this->set('type', $type);

        return $this;
    }

    public function get_id(){
        return $this->id;
    }

    /**
     * Обновляет измененные поля
     *
     * @return bool
     */
    public function update(){
        global $DB;

        // Изменить тренера может любой сотрудник
        if(!in_array('trainerid', $this->_update) && !$this->has_capability_edit()){
            return false;
        }

        if($this->_update) {
            $dataobj = new StdClass();
            $dataobj->id = $this->id;

            foreach ($this->_update as $field) {
                $dataobj->$field = $this->$field;
            }

            return $DB->update_record('lm_place', $dataobj);
        }

        return false;
    }

    /**
     * Удаляет текущее место проведения без возможности восстановления!
     *
     * @return bool
     */
    public function remove(){
        global $DB;

        if(!$this->has_capability_edit()){
            return false;
        }

        return $DB->delete_records('lm_place', array('id'=>$this->id));
    }

    /**
     * Возвращает название этой аудитории
     *
     * @return string
     */
    public function name(){
        return $this->name;
    }

    /**
     * Возвращает полное название этой аудитории
     *
     * @return string
     */
    public function fullname(){
        if($this->type == "class"){
            return $this->city.' - '.$this->name;
        }else if($this->type == "tt"){
            return $this->code.' ('.$this->city.' '.$this->name.')';
        }

        return "";
    }


    /**
     * Возвращает название города для текущего места
     *
     * @return mixed|string
     */
    public function city_name(){
        global $DB;

        if($city = $DB->get_field('lm_region', 'name', array('id'=>$this->cityid))){
            return $city;
        }

        return '';
    }


    /**
     * Возвращает информацию об оборудовании через запятую в виде "Флипчарт, Проектор, Куллер"
     *
     * @return string
     * @throws coding_exception
     */
    public function get_equipment_info(){
        $out = '';
        $equipments = array();
        foreach(self::get_equipment_list() as $code=>$name){
            $equipments[$code] = $this->$code;
        }

        foreach($equipments as $strcode=>$equip){
            if($equip){
                if($out){
                    $out .= ', ';
                }
                $out .= get_string($strcode, 'block_manage');
            }
        }

        return $out;
    }

    /**
     * Возвращает информацию об оборудовании для использования в плагине x-editable
     *
     * @return string
     */
    public function equip_info_json(){
        $out = '';
        foreach(self::get_equipment_list() as $code=>$name){
            if($this->$code) {
                if ($out) {
                    $out .= ", ";
                }
                $out .= "'" . $code . "'";
            }
        }

        return '['.$out.']';
    }

    /**
     * Возвращает список возможного оборудования
     *
     * @return array
     * @throws coding_exception
     */
    public static function get_equipment_list(){
        return array('flipchart'=>get_string('flipchart', 'block_manage'),
                            'projector'=>get_string('projector', 'block_manage'),
                            'wifi'=>get_string('wifi', 'block_manage'),
                            'cooler'=>get_string('cooler', 'block_manage')
        );
    }

    /**
     * Типы локаций
     *
     * @return array
     */
    public static function get_types(){
        return array('class'=>'Класс', 'tt'=>'Торговая точка');
    }

    public function appoint_manager($userid, $type){
        global $DB;

        $dataobj = new StdClass();
        $dataobj->id = $this->id;
        $dataobj->$type = $this->$type = $userid;

        return $DB->update_record('lm_place', $dataobj);
    }

    /**
     * Может ли текущий пользователь редактировать информацию об этой аудитории?
     *
     * @return bool
     */
    public function has_capability_edit(){
        if(has_capability('block/manage:editplaces', context_system::instance())){
            return true;
        }

        return false;
    }


    /**
     * Назначает пользователя $userid территориальным менеджером
     *
     * @param $userid
     * @return bool
     */
    public function appoint_tm($userid){
        global $DB;
        $dataobj = (object) array('id'=>$this->id, 'tmid'=>$userid);
        return $DB->update_record('lm_place', $dataobj);
    }

    public function get_tm(){
        global $DB;
        return $DB->get_record('user', array('id'=>$this->tmid));
    }

    /**
     * Возвращает ссылку на ТМ
     * @return string
     */
    public function get_tm_link(){
        global $CFG;
        if($tm = $this->get_tm()){
            return html_writer::link($CFG->wwwroot.'/user/view.php?id='.$tm->id, fullname($tm), array("target"=>"_blank"));
        }

        return "";
    }

    /**
     * Назначает пользователя $userid тренером партнера
     *
     * @param $userid
     * @return bool
     */
    public function appoint_trainer($userid){
        global $DB;
        $dataobj = (object) array('id'=>$this->id, 'trainerid'=>$userid);
        return $DB->update_record('lm_place', $dataobj);
    }

    public function get_trainer(){
        global $DB;
        return $DB->get_record('user', array('id'=>$this->trainerid));
    }

    /**
     * Назначает пользователя $userid контактным лицом партнера $this->id
     *
     * @param $userid
     */
    public function appoint_rep($userid){
        global $DB, $CFG;
        $previoususer = $this->repid;

        $context = context_system::instance();

        $dataobj = new StdClass();
        $dataobj->id = $this->id;
        $dataobj->repid = $userid;
        if($DB->update_record('lm_place', $dataobj)){
            $this->repid = $userid;

            // Назначаем нового пользователя на роль "Контактное лицо партнера"
            role_assign($CFG->block_manage_reproleid, $userid, $context->id);
        }

        if($previoususer){
            // Снимаем предыдущего юзера с роли "Контактное лицо партнера"
            // TODO: проверку, назначен ли $previoususer у другого партнера контактным лицом
            role_unassign($CFG->block_manage_reproleid, $previoususer, $context->id);
        }
    }

    /**
     * Возвращает информацию о назначенном контактном лице
     *
     * @return StdClass
     */
    public function get_rep(){
        global $DB;
        return $DB->get_record('user', array('id'=>$this->repid));
    }

    /**
     * Назначает ответственного для ТТ
     */
    public function appoint_resp($userid){
        global $DB;

        $dataobj = new StdClass();
        $dataobj->id = $this->id;
        $dataobj->respid = $userid;
        if($DB->update_record('lm_place', $dataobj)){
            $this->respid = $userid;
        }
    }

    /**
     * Возвращает информацию об ответственном лице
     *
     * @return StdClass
     */
    public function get_resp(){
        global $DB;
        return $DB->get_record('user', array('id'=>$this->respid));
    }

    /**
     * Возвращает ссылку на ответстенного
     * @return string
     */
    public function get_resp_link(){
        global $CFG;
        if($resp = $this->get_resp()){
            return html_writer::link($CFG->wwwroot.'/user/view.php?id='.$resp->id, fullname($resp), array("target"=>"_blank"));
        }

        return "";
    }
}