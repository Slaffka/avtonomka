<?php
/**
 * данный класс обрабатывает обший функционал оброщение к базе
 *
 * @author   Andrej Schartner <schartner@as-code.eu>
 */
abstract class lm_bestpractices_model {
    protected $properties = [];
    protected static $table = null;

    public function get_propertie_list() {
        return array_keys($this->properties);
    }

    public function fromObject($data)
    {
        if (!is_object($data)) {
            return $this;
        }
        foreach ((array)$data as $key => $val)
        {
            $this->{$key} = $val;
        }
        return $this;
    }

    public function __set($prop, $value)
    {
        $func = 'set' . ucfirst($prop);

        if( method_exists($this, $func) ) {
            return $this->$func($value);
        }

        if( !array_key_exists($prop, $this->properties) ) {
            return false;
        }

        if(isset($value)) {
            $this->properties[$prop] = $value;
            return true;
        }
    }

    public function __get($prop)
    {
        $func = 'get' . ucfirst($prop);
        if( method_exists($this, $func) ) {
            return $this->$func();
        } else {
            if( isset($this->properties[$prop]) ) {
                return $this->properties[$prop];
            }
        }
        return null;
    }

    public static function get_fields_for_db($pfx = null) {
        $class = get_called_class();

        $list = (new $class)->get_propertie_list();
        if ($pfx) {
            foreach ($list as $key => $value) {
                $list[$key] = $pfx . '.' . $value;
            }
        }
        if (is_array($list) && !empty($list)) {
            return implode(", ", $list);
        }
        return "*";
    }

    public static function get_by_id($id)
    {
        $class = get_called_class();
        if (!$class::$table) {
            return false;
        }
        global $DB;
        $sql = "SELECT * FROM {" . $class::$table . "} WHERE id = ?";
        $row = $DB->get_record_sql($sql, [$id]);
        return self::get_model_by_row($row);
    }

    public static function get_model_by_row($row) {
        $class = get_called_class();
        return new $class($row);
    }

    public static function get_models_by_result($res) {
        $list = [];
        foreach ($res as $row) {
            $list[] = self::get_model_by_row($row);
        }
        return $list;
    }

    public function toArray() {
        $res = [];
        foreach ($this->properties as $prop => $value) {
            $res[$prop] = $value;
        }
        return $res;
    }

    public function save() {
        global $DB;
        $class = get_called_class();
        $entry = new StdClass();
        foreach ($this->properties as $key => $value) {
            if ($key == 'id' && is_null($value)) {
                continue;
            }
            $entry->{$key} = $value;
        }
        if (is_null($this->id)) {
            $this->id = $DB->insert_record($class::$table, $entry);
            if ($this->id) {
                return true;
            }
        } else {
            return $DB->update_record($class::$table, $entry);
        }
        return false;
    }

    public function delete() {
        global $DB;
        $class = get_called_class();
        if (!$this->id) {
            return true;
        }
        $sql = "DELETE FROM {" . $class::$table . "} WHERE id = ?";
        $DB->execute($sql, [$this->id]);
    }

    public function get_errors() {

        return [];
    }



}