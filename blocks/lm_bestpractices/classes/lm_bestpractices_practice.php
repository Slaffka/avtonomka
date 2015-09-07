<?php
/**
 * данный класс обрабатывает оброщение к базе таблици практик
 *
 * @author   Andrej Schartner <schartner@as-code.eu>
 */
class lm_bestpractices_practice extends lm_bestpractices_model {

    protected static $table = 'lm_bestpractices_practice';

    const STATE_NEW = 0;
    const STATE_ACCEPTED = 1;
    const STATE_REJECTED = 2;

    /**
     * метод инициализирует данный класс
     *
     * @param array $filter  список филтров для филтрации резултата
     * @param array $filter  номер актуальной страници
     */
    public function __construct($data = null) {
        $this->properties = [
            'id'               => null,
            'parentid'         => null,
            'authorid'         => null,
            'regionid'         => null,
            'parentuserid'     => null,
            'name'             => null,
            'goal'             => null,
            'description'      => null,
            'comment'          => null,
            'resourcesfinance' => null,
            'resourcesother'   => null,
            'datestart'        => null,
            'datefinish'       => null,
            'profit'           => null,
            'state'            => null,
            'embedded'         => null,
            'respects'         => null,
            'created'          => null,
        ];
        if ($data) {
            $this->fromObject($data);
        }
    }

    protected $file_list = null;
    protected $history_list = null;

    public function getPeriod() {
        return date("d.m.Y", $this->datestart) . ' до ' . date("d.m.Y",$this->datefinish);
    }

    public function getTypeList() {
        return lm_bestpractices_practice_type::get_type_list_by_practice_id(
            $this->id
        );
    }

    public function getTypeStr() {
        $str = "";
        foreach ($this->typeList as $type) {
            $str .= "<br />" . $type->type->name;
        }
        return ltrim($str, "<br />");
    }

    public function getParent()
    {
        if ($this->parentid > 0) {
            return self::get_by_id($this->parentid);
        }
        return null;
    }

    public function getAuthorName() {
        if ($this->parentid > 0) {
            return $this->parent->authorName;
        }
        return lm_user::i($this->authorid)->link();
    }

    public function getIntroduceOtherCount() {
        if ($this->parentid > 0) {
            return $this->parent->introduceOtherCount;
        }
        return self::get_introduced_count($this->id);
    }

    public function getIntroduceOtherProfit() {
        if ($this->parentid > 0) {
            return $this->parent->introduceOtherProfit;
        }
        return self::get_introduced_profit($this->id);
    }

    public function setDatestart($value) {
        $this->properties['datestart'] = date("Y-m-d", strtotime($value));
    }

    public function setDatefinish($value) {
        $this->properties['datefinish'] = date("Y-m-d", strtotime($value));
    }

    public function getIsFavorite() {
        global $USER;
        $model = lm_bestpractices_practice_favorite::get_by_user_and_practice(
            $USER->id,
            $this->id
        );
        return $model->id > 0;
    }

    public function add_to_favorite($userid) {
        $model = new lm_bestpractices_practice_favorite();
        $model->userid = $userid;
        $model->practiceid = $this->id;
        $model->created = time();
        $model->save();
    }

    public function remove_from_favorite($userid) {
        $model = lm_bestpractices_practice_favorite::get_by_user_and_practice(
            $userid,
            $this->id
        );
        $model->delete();
    }

    public function getFileList() {
        if (!is_null($this->file_list)) {
            return $this->file_list;
        }
        $this->file_list = lm_bestpractices_practice_file::get_list_by_practiceid($this->id);
        error_log(print_r($this->file_list,true));
        return $this->file_list;
    }

    protected function get_file_list_by_type($type) {
        $files = [];
        $type_id = lm_bestpractices_practice_file::get_type_id($type);
        foreach ($this->fileList as $file) {
            if ($file->type != $type_id) {
                continue;
            }
            $files[] = $file;
        }
        return $files;
    }

    public function getPdfFiles() {
        return $this->get_file_list_by_type('pdf');
    }

    public function getExcelFiles() {
        return $this->get_file_list_by_type('excel');
    }

    public function getOtherFiles() {
        return $this->get_file_list_by_type('other');
    }

    public function getPhotoFiles() {
        return $this->get_file_list_by_type('photo');
    }

    public function getModerateCount() {
        return count($this->history) + 1;
    }

    public function getHistory() {
        if (!is_null($this->history_list)) {
            return $this->history_list;
        }
        $this->history_list = lm_bestpractices_practice_history::get_list_by_practice_id(
            $this->id
        );
        error_log(print_r($this->history_list,true));
        return $this->history_list;
    }


    public function getStateStr () {
        switch ($this->state) {
            case self::STATE_ACCEPTED:
                return "Принята";
                break;
            case self::STATE_REJECTED:
                return "Отклонена";
                break;
            case self::STATE_NEW:
            default:
                return "Новая";
                break;
        }
    }

    public function moderate($do_action, $comment) {
        if ($do_action == 'accept') {
            $this->state = self::STATE_ACCEPTED;
        } else if ($do_action == 'reject') {
            $this->state = self::STATE_REJECTED;
        } else {
            return false;
        }
        $this->save();
        $obj = new lm_bestpractices_practice_history();
        $obj->practiceid = $this->id;
        $obj->date = time();
        $obj->state = $this->state;
        $obj->comment = $comment;
        $obj->data = serialize($this->toArray());
        $obj->save();
        return true;
    }


    /**
     * метод достаёт список для бака практик
     *
     * @param array   $filter        список фильтров для фильтрации результата
     * @param array   $order         сортировка результата
     * @param integer $current_page  номер актуальной страници
     * @param integer $per_page      количество записей на одной странице
     */
    public static function get_bank_data(array $filter, array $order, $current_page, $per_page) {
        if (!isset($filter['state'])) {
            $filter['state'] = self::STATE_ACCEPTED;
        }
        return self::get_list($filter, $order, $current_page, $per_page);
    }

    public static function get_favorite_list(array $filter, array $order, $current_page, $per_page) {
        $new_filter = [];
        foreach ($filter as $key => $value) {
            switch ($key) {
                case 'userid':
                    if (!empty($value)) {
                        $new_filter['favoriteuserid'] = $value;
                    }
                    break;
                default:
                    break;
            }
        }
        return self::get_list($new_filter, $order, $current_page, $per_page);
    }

    public static function get_introduced_list(array $filter, array $order, $current_page, $per_page) {
        $new_filter = [];
        foreach ($filter as $key => $value) {
            switch ($key) {
                default:
                    if (!empty($value)) {
                        $new_filter[$key] = $value;
                    }
                    break;
            }
        }
        return self::get_list($new_filter, $order, $current_page, $per_page);
    }

    public static function get_new_practice_list(array $filter, array $order, $current_page, $per_page) {
        $new_filter = ['state' => self::STATE_NEW];
        foreach ($filter as $key => $value) {
            switch ($key) {
                default:
                    if (!empty($value)) {
                        $new_filter[$key] = $value;
                    }
                    break;
            }
        }
        return self::get_list($new_filter, $order, $current_page, $per_page);
    }

    public static function get_list(array $filter, array $order, $current_page, $per_page) {
        error_log(print_r([$filter, $order, $current_page, $per_page],true));
        global $DB;

        // парсим параметры
        $current_page = $current_page > 0 ? $current_page : 1;
        $per_page     = $per_page > 0 ? $per_page : 15;

        // парсим филтры
        $tables = [];
        $where = [];
        $params = [];

        foreach ($filter as $key => $value) {
            switch ($key) {
                case 'last_days':
                    if (!empty($value) && in_array($value, [7, 30, 90])) {
                        $where[] = " p.created >= ?";
                        $params[] = time() - (60*60*24*$value);
                    }
                    break;
                case 'search_term':
                    if (!empty($value)) {
                        $where[] = " (p.name like ? OR p.description like ?)";
                        $params[] = "%" . $value . "%";
                        $params[] = "%" . $value . "%";
                    }
                    break;
                case 'search_profit_from':
                    if (!empty($value)) {
                        $where[] = " p.profit >= ?";
                        $params[] = $value;
                    }
                    break;
                case 'search_profit_to':
                    if (!empty($value)) {
                        $where[] = " p.profit <= ?";
                        $params[] = $value;
                    }
                    break;
                case 'introduced':
                    if (!empty($value)) {
                        $where[] = " p.parentid >= ?";
                        $params[] = 1;
                    }
                    break;
                case 'search_data_from':
                    if (!empty($value)) {
                        $value = date("Y-m-d", strtotime($value));
                        $where[] = " p.datestart >= ?";
                        $params[] = $value;
                    }
                    break;
                case 'search_data_do':
                    if (!empty($value)) {
                        $value = date("Y-m-d", strtotime($value));
                        $where[] = " p.datestart <= ?";
                        $params[] = $value;
                    }
                    break;
                case 'type':
                    if (is_array($value) && !empty($value)) {
                        $tables["lm_bestpractices_practice_type"] = " JOIN {lm_bestpractices_practice_type} AS pt ON p.id = pt.practiceid ";
                        $where[] = " pt.typeid in (?)";
                        $params[] = implode(",", $value);
                    }
                    break;
                case 'favoriteuserid':
                    if (!empty($value)) {
                        $tables["lm_bestpractices_practice_type"] = " JOIN {lm_bestpractices_practice_favorite} AS pf ON p.id = pf.practiceid ";
                        $where[] = " pf.userid = ?";
                        $params[] = $value;
                    }
                    break;
                case 'position':
                    break;
                case 'area':
                    if (!empty($value)) {
                        $where[] = " p.regionid in (?)";
                        $params[] = implode(",", $value);
                    }
                    break;
                case 'authorid':
                case 'state':
                    $value *= 1;
                    $where[] = " p." . $key . " = ?";
                    $params[] = $value;
                    break;
                default:
                    break;
            }
        }

        // парсим сортировку
        $order_by = "";
        foreach ($order as $key => $value) {
            $value = strtoupper($value) == "ASC" ? "ASC" : "DESC";
            switch ($key) {
                case 'id':
                case 'respects':
                case 'profit':
                    $order_by = " ORDER BY " . $key . " " . $value;
                    break;

                default:
                    break;
            }
        }

        // echo "<pre>";
        // print_r($filter);
        // echo "</pre>";
        // exit;

        $sql = " FROM {" . self::$table . "} AS p "
             . implode(" ", $tables);

        if (!empty($where)) {
            $sql .= "WHERE " . implode(" AND ", $where);
        }
try {
        // считаем количество записей в базе
        $res_count = $DB->get_record_sql(
            "SELECT COUNT(DISTINCT p.id) AS count " . $sql,
            $params
        );

        $pager = [
            'count' => ceil($res_count->count / $per_page),
            'current' => $current_page
        ];

        // достаём пользователя из базы
        $res = $DB->get_records_sql(
            "SELECT " . self::get_fields_for_db('p') . $sql . $order_by .
            " LIMIT " . ($current_page * $per_page - $per_page) . ", " . $per_page,
            $params
        );
error_log(print_r( ["SELECT " . self::get_fields_for_db('p') . $sql . $order_by .
            " LIMIT " . ($current_page * $per_page - $per_page) . ", " . $per_page,
            $params], this));
} catch (Exception $e) {

    echo "<pre>";
    print_r($e);
    echo "</pre>";
    exit;
}
        $list = self::get_models_by_result($res);
        return [$list, $pager];
    }

    public static function get_introduced_count($id) {
        global $DB;
        $sql = "SELECT COUNT(*) AS count FROM {" . self::$table . "}
                WHERE parentid = ?";
        $res = $DB->get_record_sql($sql, [$id]);
        if ($res) {
            return $res->count;
        }
        return 0;
    }
    public static function get_introduced_profit($id) {
        global $DB;
        $sql = "SELECT SUM(profit) AS sum FROM {" . self::$table . "}
                WHERE parentid = ? GROUP BY parentid";
        $res = $DB->get_record_sql($sql, [$id]);
        if ($res) {
            return $res->sum;
        }
        return 0;

    }

}
