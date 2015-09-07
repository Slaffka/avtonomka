<?php
/**
 * данный класс обрабатывает данные с интерфейса
 *
 * @author   Andrej Schartner <schartner@as-code.eu>
 */
class block_lm_bestpractices_renderer extends block_manage_renderer {

    /**
     * переменная содержит урл к блоку
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=lm_bestpractices';

    /**
     * переменная содержит название блока
     * @var string
     */
    public $pagename = 'Передовой опыт';

    /**
     * переменная содержит тип блока
     * @var string
     */
    public $type = 'lm_bestpractices';

    /**
     * переменная содержит лайоут блока
     * @var string
     */
    public $pagelayout = "";

    /**
     * переменная содержит права пользователя для блока
     * @var array
     */
    protected $acl = [];

    /**
     * инициализация страници и подготовка всех параметров
     */
    public function init_page()
    {
        // запускаем инициализацию от базового класса
        parent::init_page();

        // подгружаем необходимые скрипты для работы с блоком
        $this->page->requires->js('/blocks/manage/yui/base.js');
        $this->page->requires->js("/blocks/lm_bestpractices/js/ajax_form.js");
        $this->page->requires->js("/blocks/lm_bestpractices/js/bank_practices_search_block.js");
        $this->page->requires->js("/blocks/lm_bestpractices/js/bank_practices_create.js");
        $this->page->requires->js("/blocks/lm_bestpractices/js/best_practices_form.js");
        $this->page->requires->jquery_plugin('scroll', 'theme_tibibase');
        $this->initACL();
    }

    public function get_dataroot() {
        global $CFG;
        $dataroot = realpath($CFG->dataroot . '/filedir/');
        if (!is_dir($CFG->dataroot . '/filedir/')) {
            mkdir($CFG->dataroot . '/filedir/');
        }
        $dataroot .= "/lm_bestpractices";
        if (!is_dir($dataroot)) {
            mkdir($dataroot);
        }
        return $dataroot;
    }

    /**
     * инициализация прав данного пользователя
     */
    protected function initACL() {
        global $USER;

        $user_role = lm_bestpractices_practice_user_roles::get_by_user_id(
            $USER->id
        );
        $access = explode(',', $user_role->role->access);
        $this->acl = [
            'subpage' => [
                'index'         => in_array('index', $access), // для всех пользователей
                'my_practices'  => in_array('my_practices', $access),// только для участников
                'today_results' => in_array('today_results', $access),// для всех пользователей
                'hall_of_fame'  => in_array('hall_of_fame', $access),// для всех пользователей
                'council'       => in_array('council', $access),// для Членов Управляющего совета
                'moderate'      => in_array('moderate', $access),// для Модераторов
            ]
        ];
    }

    /**
     * Создание навигации
     */
    public function navigation(){
        $all = [
            'index' => [
                'code'   => 'index',
                'name'   => 'Банк практик',
                'url'    => $this->pageurl,
                'alerts' => null,
            ],
            'my_practices' => [
                'code'   => 'my_practices',
                'name'   => 'Мои практики',
                'url'    => $this->pageurl . '&subpage=my_practices',
                'alerts' => null,
            ],
            'today_results' => [
                'code'   => 'today_results',
                'name'   => 'Промежуточные итоги',
                'url'    => $this->pageurl . '&subpage=today_results',
                'alerts' => null,
            ],
            'hall_of_fame' => [
                'code'   => 'hall_of_fame',
                'name'   => 'Зал славы',
                'url'    => $this->pageurl . '&subpage=hall_of_fame',
                'alerts' => null,
            ],
            'council' => [
                'code'   => 'council',
                'name'   => 'Управляющий совет',
                'url'    => $this->pageurl . '&subpage=council',
                'alerts' => null,
            ],
            'moderate' => [
                'code'   => 'moderate',
                'name'   => 'Модерация',
                'url'    => $this->pageurl . '&subpage=moderate',
                'alerts' => null,
            ],
        ];

        $subparts = [];
        foreach ($this->acl['subpage'] as $key => $value) {
            if ($value != true || !isset($all[$key])) {
                continue;
            }
            $subparts[] = $all[$key];
        }

        return $this->subnav($subparts);
    }

    /**
     * Функция генерирует главный контент блока
     */
    public function main_content() {
        global $USER;

        // достаём тпл обьект
        $tpl = $this->tpl;
        // прописываем путь к тпл блока
        $tpl_path = $this->getTplPath();

        $tpl->page_url = $this->pageurl;

        // проверка прав на посещение данной страници
        if (!isset($this->acl['subpage'][$this->subpage])
            || !$this->acl['subpage'][$this->subpage]
            ) {
            return $this->fetch($tpl_path . 'no_rights.tpl');
        }

        switch ($this->subpage) {
            case 'my_practices':
                list (
                    $tpl->list,
                    $pager_data
                ) = lm_bestpractices_practice::get_list(
                    ['authorid' => $USER->id],
                    ['id' => 'ASC'],
                    1,
                    15
                );
                $tpl_path .= 'subpages/my_practices.tpl';
                break;
            case 'today_results':
                echo "today_results";exit;
                break;

            case 'hall_of_fame':
                echo "hall_of_fame";exit;
                break;
            case 'council':
                echo "council";exit;
                break;
            case 'moderate':
                // Голосование за лучшую практику

                $tpl_path .= 'subpages/moderate.tpl';
                break;
            case 'download':
                echo "-";exit;
                break;
            default:
                $tpl->types = lm_bestpractices_practice_types::get_list();
                $tpl->positions = lm_bestpractices_practice_roles::get_list();
                $tpl->areas = self::get_area_filter_data();
                list (
                    $tpl->list,
                    $pager_data
                ) = lm_bestpractices_practice::get_bank_data(
                    [],
                    ['id' => 'ASC'],
                    1,
                    15
                );
                $tpl_path .= 'subpages/bank_practices.tpl';
                break;
        }
        // подготовка пагинатора
        if (isset($pager_data)) {
            // передаём пагинатор в тпл
            $tpl->pager = $this->get_pager_object($pager_data, $tpl->page_url);
        }
        return $this->fetch($tpl_path);
    }

    protected function get_pager_object($pager_data, $page_url) {
        // создаём новый обьект пагинатора
        $pager = new StdClass();

        // количество страниц
        $pager->count = isset($pager_data['count']) ? $pager_data['count'] : 1;

        // актуальная страница
        $pager->current = isset($pager_data['current']) ? $pager_data['current'] : 1;

        // урл для страниц (без указания страници)
        $pager->url = $page_url;
        return $pager;
    }

    protected function getTplPath() {
        return "/blocks/" . $this->type . "/tpl/";
    }

    protected function get_practice_info($id, $back_url, $back_detailpage = '', $historyid = '') {
        // достаём тпл обьект
        $tpl = $this->tpl;

        // прописываем путь к тпл блока
        $tpl_path = $this->getTplPath();

        $tpl->back_detailpage = $back_detailpage;

        // достаём практику из базы
        $historyid *= 1;
        $tpl->practice = null;
        if ($historyid > 0) {
            $history = lm_bestpractices_practice_history::get_by_id($historyid);
            if ($history->id > 0) {
                $tpl->practice = $history->practice;
            }
        }
        if (!$tpl->practice) {
            $tpl->practice = lm_bestpractices_practice::get_by_id($id);
        }

        // ставит адрис для возврощения обратно
        $tpl->back_url = $back_url;

        // ставим тпл для показа
        $tpl_path .= 'subpages/practices_detail_page.tpl';

        // создаём результат для поиска
        $result = $this->fetch($tpl_path);
        // $result .= '<pre>' .  print_r($_POST['filter'],true) . '</pre>';
        echo json_encode(['error' => false, 'view' => $result]);
    }

    /**
     *
     */
    public function ajax_bank_practices($p)
    {
        // достаём тпл обьект
        $tpl = $this->tpl;

        // прописываем путь к тпл блока
        $tpl_path = $this->getTplPath();

        // прописываем адрес к данной страничке
        $tpl->page_url = $this->pageurl . '&subpage=index';

        if ($_POST['detailpage'] == 'show_practice_info') {
            return $this->get_practice_info(
                $_POST['practiceid'],
                $tpl->page_url
            );
        }

        if (isset($_POST['order_field'])) {
            foreach ($_POST['order_field'] as $key => $value) {
                if (isset($_POST['order_direction'][$key])) {
                    $_POST['order'] = [
                        $value => strtoupper($_POST['order_direction'][$key]),
                    ];
                }
            }
        }

        // передаём сортировку в тпл
        if (isset($_POST['order'])) {
            $tpl->order = $_POST['order'];
        }

        // показ последних дней
        if (isset($_POST['last_days']) && $_POST['last_days'] > 0) {
            if (!isset($_POST['filter'])) {
                $_POST['filter'] = [];
            }
            $_POST['filter']['last_days'] = $_POST['last_days'];
            $last_days = true;
        }

        // достаём данные из базы для показа
        list (
            $tpl->list,
            $pager_data
        ) = lm_bestpractices_practice::get_bank_data(
            isset($_POST['filter']) ? $_POST['filter'] : [],
            isset($_POST['order']) ? $_POST['order'] : ['id' => 'ASC'],
            isset($_POST['page']) ? $_POST['page'] : 1,
            isset($_POST['per_page']) ? $_POST['per_page'] : 15
        );

        // ставим тпл для показа
        if (isset($_POST['filter']['last_days'])) {
            $tpl_path .= 'subpages/bank_practices_table_last_days.tpl';
        } else {
            $tpl_path .= 'subpages/bank_practices_table.tpl';
        }

        // подготовка пагинатора
        if (isset($pager_data)) {
            // передаём пагинатор в тпл
            $tpl->pager = $this->get_pager_object($pager_data, $tpl->page_url);
        }

        // создаём результат для поиска
        $result = $this->fetch($tpl_path);
        // $result .= '<pre>' .  print_r($_POST['filter'],true) . '</pre>';
        echo json_encode(['error' => false, 'view' => $result]);
    }

    /**
     *
     */
    public function ajax_moderate($p)
    {
       global $USER;

        // достаём тпл обьект
        $tpl = $this->tpl;

        // прописываем путь к тпл блока
        $tpl_path = $this->getTplPath();

        // прописываем адрес к данной страничке
        $tpl->page_url = $this->pageurl . '&subpage=my_practices';

        if ($_POST['detailpage'] == 'show_practice_info') {
            return $this->get_practice_info(
                $_POST['practiceid'],
                $tpl->page_url,
                $_POST['back_detailpage'],
                $_POST['historyid']
            );
        }

        // парсим subpage и запускаем функционал для разный видов
        $subpage = isset($_POST['subpage']) ? $_POST['subpage'] : 'vote';
        if (isset($_POST['detailpage']) && !empty($_POST['detailpage'])) {
            $subpage = $_POST['detailpage'];
        }
        $do_action_result = null;
        switch ($subpage) {
            case 'new_practice':
                list (
                    $tpl->list,
                    $pager_data
                ) = lm_bestpractices_practice::get_new_practice_list(
                    isset($_POST['filter']) ? $_POST['filter'] : [],
                    isset($_POST['order']) ? $_POST['order'] : ['id' => 'ASC'],
                    isset($_POST['page']) ? $_POST['page'] : 1,
                    isset($_POST['per_page']) ? $_POST['per_page'] : 15
                );
                break;
            case 'show_practice_agreement':
                // достаём практику из базы
                $tpl->practice = lm_bestpractices_practice::get_by_id(
                    isset($_POST['practiceid']) ? $_POST['practiceid'] : 0
                );
                if (isset($_POST['do_action'])) {
                    $do_action_result = $tpl->practice->moderate(
                        $_POST['do_action'],
                        $_POST['comment']
                    );
                }
                break;
            case 'new_introduced_practice':

                break;
            case 'winner':

                break;
            case 'complaints':

                break;
            default:
            error_log($subpage);
                $subpage = 'vote';

                break;
        }

        // ставим тпл для показа
        $tpl_path .= 'subpages/moderate_' . $subpage . '.tpl';

        // подготовка пагинатора
        if (isset($pager_data)) {
            // передаём пагинатор в тпл
            $tpl->pager = $this->get_pager_object($pager_data, $tpl->page_url);
        }

        // создаём результат для поиска
        $result = $this->fetch($tpl_path);

        // $result .= '<pre>' .  print_r($_POST,true).  print_r($_FILES,true) . '</pre>';
        echo json_encode(
            [
                'error' => false,
                'view' => $result,
                'list'=>$tpl->list,
                'do_action_result' => $do_action_result,
            ]
        );

    }

    /**
     *
     */
    public function ajax_my_practices_create($p)
    {
        global $USER;

        // достаём тпл обьект
        $tpl = $this->tpl;

        // прописываем путь к тпл блока
        $tpl_path = $this->getTplPath();

        // прописываем адрес к данной страничке
        $tpl->page_url = $this->pageurl . '&subpage=my_practices';

        $model = new lm_bestpractices_practice();
        $model->authorid = $USER->id;
        $pos = lm_position::i($USER->id);

        $model->regionid = $pos->areaid;
        $chief = $pos->get_my_chief();

        if (isset($_POST['practice_parentid'])) {
            $parentmodel = lm_bestpractices_practice::get_by_id(
                $_POST['practice_parentid']
            );
            $model->description = $parentmodel->description;
            $model->name = $parentmodel->name;
            $model->goal = $parentmodel->goal;
            $model->parentid = $parentmodel->id;
        } else {
            $model->description = isset($_POST['practice_description']) ? $_POST['practice_description'] : '';
            $model->name = isset($_POST['practice_name']) ? $_POST['practice_name'] : '';
            $model->goal = isset($_POST['practice_goal']) ? $_POST['practice_goal'] : '';
            $model->parentid = 0;
        }
        $model->parentuserid = !$chief->id ? 0 : $chief->id;
        $model->comment = isset($_POST['practice_comments']) ? $_POST['practice_comments'] : '';
        $model->resourcesfinance = isset($_POST['practice_resourcesfinance']) ? $_POST['practice_resourcesfinance'] : '';
        $model->resourcesother = isset($_POST['practice_resourcesother']) ? $_POST['practice_resourcesother'] : '';
        $model->datestart = isset($_POST['practice_from']) ? $_POST['practice_from'] : '';
        $model->datefinish = isset($_POST['practice_to']) ? $_POST['practice_to'] : '';
        $model->profit = 0;
        $model->state = lm_bestpractices_practice::STATE_NEW;
        $model->embedded = 0;
        $model->respects = 0;
        $model->created = time();

try {
        if (!$model->save()) {
            echo json_encode(['error' => true, 'errors' => $model->get_errors()]);
            return;
        }

        if (isset($_POST['practice_tt'])) {
            foreach ($_POST['practice_tt'] as $tt) {
                $to_model = new lm_bestpractices_practice_trade_outlet();
                $to_model->practiceid = $model->id;
                $to_model->outletid = $tt;
                $to_model->save();
            }
        }

        foreach (['pdf', 'excel', 'photo', 'other'] as $type) {
            if (!isset($_FILES[$type]['name'])) {
                continue;
            }
            foreach ($_FILES[$type]['name'] as $key => $name) {
                $new_file = new lm_bestpractices_practice_file();
                $new_file->type = $type;
                $new_file->practiceid = $model->id;
                $new_file->path = $this->get_dataroot() . '/' . $model->id . '/';
                $new_file->filename = $name;
                $new_file->contenttype = $_FILES[$type]['type'][$key];
                if (!is_dir($new_file->path)) {
                    mkdir($new_file->path);
                }
                if (move_uploaded_file($_FILES[$type]["tmp_name"][$key],
                    $new_file->path . $new_file->filename)) {
                    $new_file->save();
                }
            }
        }
} catch (Exception $e) {
error_log(print_r($e,true));
}

        // создаём результат
        $tpl_path .= 'subpages/my_practices_create_ok.tpl';
        $result = $this->fetch($tpl_path);

        echo json_encode(['error' => false, 'view' => $result]);
    }

    /**
     *
     */
    public function ajax_my_practices($p)
    {
        global $USER;

        // достаём тпл обьект
        $tpl = $this->tpl;

        // прописываем путь к тпл блока
        $tpl_path = $this->getTplPath();

        // прописываем адрес к данной страничке
        $tpl->page_url = $this->pageurl . '&subpage=my_practices';

        if ($_POST['detailpage'] == 'show_practice_info') {
            return $this->get_practice_info($_POST['practiceid'], $tpl->page_url);
        }

        // парсим subpage и запускаем функционал для разный видов
        $subpage = isset($_POST['subpage']) ? $_POST['subpage'] : 'created';
        switch ($subpage) {
            case 'introduced':
                list (
                    $tpl->list,
                    $pager_data
                ) = lm_bestpractices_practice::get_introduced_list(
                    ['authorid' => $USER->id, 'introduced' => 1],
                    isset($_POST['order']) ? $_POST['order'] : ['id' => 'ASC'],
                    isset($_POST['page']) ? $_POST['page'] : 1,
                    isset($_POST['per_page']) ? $_POST['per_page'] : 15
                );

                break;
            case 'foreign_create_practice':
                $tpl->tt = self::get_user_tt_data($USER->id);
                $tpl->practice = lm_bestpractices_practice::get_by_id($p->practiceid);

                break;
            case 'favorites':
                list (
                    $tpl->list,
                    $pager_data
                ) = lm_bestpractices_practice::get_favorite_list(
                    ['userid' => $USER->id],
                    isset($_POST['order']) ? $_POST['order'] : ['id' => 'ASC'],
                    isset($_POST['page']) ? $_POST['page'] : 1,
                    isset($_POST['per_page']) ? $_POST['per_page'] : 15
                );
                break;
            case 'create':
                $tpl->types = lm_bestpractices_practice_types::get_list();
                $tpl->tt = self::get_user_tt_data($USER->id);
                break;
            default:
                $subpage = 'created';
                list (
                    $tpl->list,
                    $pager_data
                ) = lm_bestpractices_practice::get_list(
                    ['authorid' => $USER->id],
                    isset($_POST['order']) ? $_POST['order'] : ['id' => 'ASC'],
                    isset($_POST['page']) ? $_POST['page'] : 1,
                    isset($_POST['per_page']) ? $_POST['per_page'] : 15
                );
                break;
        }

        // ставим тпл для показа
        $tpl_path .= 'subpages/my_practices_' . $subpage . '.tpl';

        // подготовка пагинатора
        if (isset($pager_data)) {
            // передаём пагинатор в тпл
            $tpl->pager = $this->get_pager_object($pager_data, $tpl->page_url);
        }

        // создаём результат для поиска
        $result = $this->fetch($tpl_path);

        // $result .= '<pre>' .  print_r($_POST,true).  print_r($_FILES,true) . '</pre>';
        echo json_encode(['error' => false, 'view' => $result,'list'=>$tpl->list]);
    }

    /**
     *
     */
    public function ajax_favorite_remove($p) {
        global $USER;
        $practice = lm_bestpractices_practice::get_by_id($p->id);
        $practice->remove_from_favorite($USER->id);
        return json_encode([]);
    }

    /**
     *
     */
    public function ajax_favorite_add($p) {
        global $USER;
        $practice = lm_bestpractices_practice::get_by_id($p->id);
        $practice->add_to_favorite($USER->id);
        return json_encode([]);
    }

    public static function get_area_filter_data() {
        global $DB;
        $sql = "SELECT id, name FROM {lm_region} WHERE name != ''";
        return $DB->get_records_sql($sql);
    }

    public static function get_user_tt_data($userid) {
        global $USER, $DB;
        $sql = "SELECT tt.id, tt.name FROM {lm_position} AS p,
                    {lm_position_xref} AS px,
                    {lm_trade_outlets} AS tt
                    WHERE px.posid = p.id
                    AND px.userid = ?
                    AND p.areaid = tt.areaid";
        return $DB->get_records_sql($sql, [$USER->id]);
    }

}