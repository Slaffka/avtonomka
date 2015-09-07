<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 5/5/2015
 * Time: 1:33 PM
 *
 * Базовый класс уведомлений.
 * Для создания нового уведомления необходимо создать дочерний класс {block_name}_notification_{event}
 * где block_name - название блока (например lm_kpi),
 *     event - событие, для которого создается уведомление (например incoming_message);
 * и определить переопределить функцию get_message, а так же функции get_type,  get_url, _update_data если необходимо.
 * Если дочерний(е) класс(ы) описан(ы) в файле /blocks/{block_name}/classes/lm_notification.php, то
 * он(они) подключатся автоматически
 */

class lm_notification {

    const TABLE_NAME = 'lm_notification';
    const BLOCK_EVENT_SEPARATOR = ':';

    const TYPE_SUCCESS = 'success';
    const TYPE_INFO    = 'info';
    const TYPE_WARNING = 'warning';
    const TYPE_DANGER  = 'danger';

    static private $i = array();
    static private $blocks = array();

    /**
     * @var int
     */
    public $id = 0;
    
    /**
     * @var int
     */
    public $blockid = 0;

    /**
     * @var string
     */
    public $event = '';

    /**
     * @var int
     */
    public $instanceid = 0;

    /**
     * @var int
     */
    public $userid = 0;

    /**
     * @var boolean
     */
    public $alert = 0;

    /**
     * @var int
     */
    public $timestamp = 0;

    /**
     * @var mixed
     */
    public $data;

    private static function _load_class($class_name) {
        global $CFG;

        list($block) = explode('_notification_', $class_name);
        $path = "{$CFG->dirroot}/blocks/{$block}/classes/lm_notification.php";
        if (file_exists($path)) {
            require_once($path);
        }

        if ( ! class_exists($class_name)) $class_name = get_class();

        return $class_name;
    }

    /**
     * Возвращает id блока по имени блока
     * @param $name
     * @return bool
     * @internal param int $id
     */
    private static function _get_block_id($name) {
        global $DB;

        if (!is_string($name) || empty($name)) return FALSE;

        if ( ! isset(self::$blocks[$name])) {
            self::$blocks[$name] = (int) $DB->get_field('block', 'id', array('name' => $name));
        }
        return self::$blocks[$name];
    }

    /**
     * Возвращает название блока по id
     * @param int $id
     * @return bool
     */
    private static function _get_block_name($id) {
        global $DB;

        if ( ! is_number($id) || empty($id)) return FALSE;

        $name = array_search($id, self::$blocks);
        if ( ! $name) {
            $name = $DB->get_field('block', 'name', array('id' => $id));
            if ($name) self::$blocks[$name] = $id;
        }
        return $name;
    }

    /**
     * @param int $eventcode полное название события blockname:notificationname или blockname:notificationname:instanceid, например lm_message:new
     * @param int $userid (optional)
     * @return lm_notification
     */
    static public function i($eventcode, $userid, $alert=FALSE) {
        global $USER;

        if (empty($userid)) $userid = $USER->id;

        if(!isset(self::$i[$eventcode][$userid]) && !empty($eventcode) && $userid > 0) {
            $blockid    = 0;
            $event      = '';
            $instanceid = 0;
            $blockname  = explode(':', $eventcode);
            if(!empty($blockname[0])) $blockid    = (int) self::_get_block_id($blockname[0]);
            if(!empty($blockname[1])) $event      = $blockname[1];
            if(!empty($blockname[2])) $instanceid = $blockname[2];

            self::$i[$eventcode][$userid] = new lm_notification($blockid, $event, $instanceid, $alert, $userid);
        }

        return self::$i[$eventcode][$userid];
    }

    /**
     * Создает экземпляр уведомления из stdClass или из бд
     * 3 варианта создания:
     *     без аргументов - просто пустой объект
     *     stdClass со свойствами - заполненый объект
     *     с аргументами: id блока, событие[, пользователь]] - заполнится из бд
     *
     * @param int|stdClass $blockid
     * @param string $event
     * @param int $instanceid
     * @param bool $alert
     * @param int $userid (optional)
     */
    protected function __construct($blockid = 0, $event = '', $instanceid = 0, $alert = FALSE, $userid = NULL) {
        global $DB, $USER;

        if (empty($userid)) $userid = $USER->id;

        $this->blockid      = (int) $blockid;
        $this->event        = $event;
        $this->instanceid   = (int) $instanceid;
        $this->alert        = (bool) $alert;
        $this->userid       = $userid;

        if ($blockid instanceof stdClass) {
            $this->_from_raw($blockid);
        } elseif($blockid && !empty($event) && $userid) {
            $where = array(
                'blockid'    => $this->blockid,
                'event'      => $this->event,
                'instanceid' => $this->instanceid,
                'userid'     => $this->userid
            );
            if ($raw_notify = $DB->get_record(self::TABLE_NAME, $where)) {
                $this->_from_raw($raw_notify);
            }
        }
    }

    /**
     * @return string
     */
    public function get_type() {
        return self::TYPE_INFO;
    }

    /**
     * @return string
     */
    public function get_url() {
        return '';
    }

    /**
     * @return string
     */
    public function get_text() {
        return '';
    }

    public function get_data(){
        return $this->data;
    }

    /**
     * @param string|array $event
     * @param bool (optional) $alert
     * @param int (optional) $userid
     * @return array
     * @throws Exception
     */
    private static function _event_to_where($event = '', $alert = NULL, $userid = NULL) {
        global $USER;

        $events = (array) $event;

        if (is_null($userid)) $userid = $USER->id;
        $event_search = FALSE;

        foreach ($events as $event) if ( ! empty($event)) {

            list($block_name, $event, $instanceid) = explode(self::BLOCK_EVENT_SEPARATOR, $event);

            // поиск по блоку
            if ( ! empty($block_name)) {
                $blockid = self::_get_block_id($block_name);
                if ($blockid) {
                    if ($event_search) $event_search .= ' OR (blockid = '.$blockid;
                    else $event_search = '((blockid = '.$blockid;
                    // поиск по событию
                    if ( ! empty($event)) $event_search .= ' AND event = "'.$event.'"';
                    // поиск по сущности
                    if ( ! empty($instanceid)) $event_search .= ' AND instanceid = "'.(int)$instanceid.'"';
                    $event_search .= ')';
                } else {
                    trigger_error('There is no such block "'.$block_name.'"', E_USER_WARNING);
                }
            }
        }
        if ($event_search) $result = $event_search.')';
        else $result = 'TRUE';

        // поиск по alert
        if (  ! is_null($alert)) $result .= ' AND alert = '. ( !! $alert ? 'TRUE' : 'FALSE');

        // поиск по пользователю
        if ($userid > 0) $result .= ' AND userid = '.$userid;

        return $result;
    }

    private function _from_raw($raw_notify) {
        foreach ($raw_notify as $field => $value) {
            switch ($field) {
                case 'id':
                case 'blockid':
                case 'instanceid':
                case 'userid':
                case 'timestamp':
                    $this->$field = (int) $value;
                    break;
                case 'alert':
                    $this->$field = (bool) $value;
                    break;
                case 'data':
                    $this->$field = empty($value) ? NULL : json_decode($value);
                    break;
                default:
                    $this->$field = $value;
            }
        }
    }

    /**
     * Возвращает список уведомлений удовлетворяющих условию
     *
     * @param string|array $event (optional) строка/массив срок для поиска вида:
     *                            'block_name' - найдет все события этого блока
     *                            'block_name:event' - найдет конкретное событие блока
     *                            'block_name:event' - найдет конкретное событие блока
     *                            'block_name:event:instance_id' - найдет конкретное событие блока для сущности
     * @param bool $alert (optional)
     * @param int $userid (optional)
     * @return lm_notification[]
     * @throws Exception
     */
    static public function get_list($event = '', $alert = NULL, $userid = NULL) {
        global $DB;

        $where = self::_event_to_where($event, $alert, $userid);
        $result = array();
        try {
            $notifications = $DB->get_records_select(self::TABLE_NAME, $where, null, 'id DESC');
        } catch(dml_exception $e) {
            $notifications = FALSE;
        }
        if ($notifications) {
            foreach ($notifications as $raw_notify) {

                $class_name = self::_get_block_name($raw_notify->blockid)."_notification_{$raw_notify->event}";
                $class_name = self::_load_class($class_name);


                /**
                 * @var $notification lm_notification
                 */
                $notification = new $class_name($raw_notify);
                $result[] = $notification;
            }
        }

        return $result;
    }

    /**
     * Возвращает кол-во уведомлений удовлетворяющих условию
     *
     * @param string|array $event (optional) строка/массив срок для поиска вида:
     *                            'block_name' - найдет все события этого блока
     *                            'block_name:event' - найдет конкретное событие блока
     *                            'block_name:event:instanceid' - найдет конкретное событие блока для сущности
     * @param bool $alert (optional)
     * @param int $userid (optional)
     * @return int
     * @throws Exception
     */
    static public function get_count($event, $alert = NULL, $userid = NULL) {
        global $DB;

        $where = self::_event_to_where($event, $alert, $userid);
        try {
            $result = (int) $DB->count_records_select(self::TABLE_NAME, $where);
        } catch (dml_exception $e) {
            $result = 0;
        }
        return $result;
    }

    /**
     * Создает новое уведомление
     *
     * @return lm_notification|FALSE
     */
    private function create() {
        global $DB;

        if ($this->blockid < 1 || empty($this->event)) return FALSE;

        $this->timestamp = time();

        if (isset($this->data)) $this->data = json_encode($this->data);
        $this->id = (int) $DB->insert_record(self::TABLE_NAME, $this);
        if (isset($this->data)) $this->data = json_decode($this->data);

        if($this->id) {
            $event =
                self::_get_block_name($this->blockid) . self::BLOCK_EVENT_SEPARATOR
                . $this->event . self::BLOCK_EVENT_SEPARATOR
                . $this->instanceid;
            self::$i[$event][$this->userid] = $this;
        }

        return $this;
    }

    /**
     * Изменяет custom данные уведомления (вызывается в методе add)
     * переопределите метод, если необходимо
     * Например, для инкремента кол-ва входящих сообщений при добавлении нового
     * @param mixed $data
     * @return mixed
     */
    protected function _update_data($data) {
        return $data;
    }

    /**
     * Создает новое уведомление
     *
     * @param string $event событие в формате 'block:event[:instanceid]'
     * @param bool $alert
     * @param integer $userid (optional) id пользователя, которому создается уведомление.
     *                                   По умолчанию текущий пользователь
     * @param $data stdClass
     * @return lm_notification
     */
    public static function add($event, $alert = FALSE, $userid = 0, $data = NULL) {
        global $USER;

        @list($block_name, $event, $instanceid) = explode(self::BLOCK_EVENT_SEPARATOR, $event);

        if (empty($block_name) || empty($event)) return FALSE;

        $blockid = self::_get_block_id($block_name);

        if ($blockid < 1) return FALSE;

        if (empty($userid)) $userid = (int) $USER->id;

        $class_name = "{$block_name}_notification_{$event}";
        $class_name = self::_load_class($class_name);

        /**
         * @var $notification lm_notification
         */
        $notification = new $class_name($blockid, $event, $instanceid, $alert, $userid);
        $notification->data = $notification->_update_data($data);

        if ($notification->id) $notification->update();
        else $notification->create();

        return $notification;
    }

    /**
     * Обновляет информацию в БД
     *
     * @param bool $update_timestamp (optional)
     * @return bool
     */
    public function update($update_timestamp = true){
        global $DB;

        if( ! $this->id){
            return FALSE;
        }

        if ($update_timestamp) {
            $this->timestamp = time();
        }

        if (isset($this->data)) $this->data = json_encode($this->data);
        $result = $DB->update_record(self::TABLE_NAME, $this);
        if (isset($this->data)) $this->data = json_decode($this->data);

        return $result;
    }

    /**
     * Удаляет информацию в БД
     *
     * @return bool
     */
    public function remove() {
        global $DB;

        if( ! $this->id){
            return FALSE;
        }

        return $DB->delete_records(self::TABLE_NAME, array('id' => $this->id));
    }

    /**
     * Удаляет сообщение в блоке
     *
     * @param string $event событие в формате 'block:event'
     * @param int $userid (optional) id пользователя. По умолчанию id текущего пользователя
     * @return bool
     */
    public static function delete($event, $userid = NULL) {
        global $DB;

        $where = self::_event_to_where($event, $userid);

        return $DB->delete_records_select(self::TABLE_NAME, $where);
    }

    /**
     * Возвращает идентификатор уведомления
     *
     * @return int
     */
    public function get_id(){
        return $this->id;
    }

}


class manage_notification_verifyphoto extends lm_notification {

    public function get_text() {
        $fullname = "";
        if( !empty($this->data->userid) ){
            $fullname = lm_user::i($this->data->userid)->fullname();
        }

        return "Подтвердите фото {$fullname}";
    }

    public function get_url() {
        return '/blocks/manage/?_p=profile';
    }

    public function get_type() {
        return self::TYPE_DANGER;
    }
}