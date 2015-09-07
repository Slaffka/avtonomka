<?php

class block_manage_profile_renderer extends block_manage_renderer
{
    /**
     * Базовая ссылка страницы
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=profile';

    /**
     * Название страницы, может использоваться в навигации
     * @var string
     */
    public $pagename = 'Мой профиль';

    /**
     * Показывать ли кнопку возврата в навигационном меню
     * См. метод lm_subnav(), который формирует навигацию
     * @var bool
     */
    public $subnavback = false;

    public $type = 'manage_profile';

    /**
     * Тип слоя. Определяет расположение header, footer, контейнеры для контента и т.п.
     * Смотри в теме папку layout и файл с соотв. названием.
     * @var string
     */
    public $pagelayout = "grid";

    /**
     * Если это свойство содержит название блока, то в качестве содержимого страницы будет результат
     * метода details_content() того блока. Обратите внимание, что этот метод есть только у блоков, которые
     * наследуются от lm_profile_block
     * @var string
     */
    public $details = '';

    /**
     * На этой странице администратор может настраивать положение и набор блоков для ролей.
     * Например, пользователь с ролью ТП может видеть одни блоки, а СВ совсем другие блоки.
     * @var bool
     */
    public $rolecustomblocks = true;

    /**
     * TODO: Колян, эту штукенцию нужно убрать. Фигово когда после ресайза страница рейтингов перезагружается и меняется кол-во строк. Все-же это должен делать JS ;)
     * @var int
     */
    public $hg = 600;

    public function init_page()
    {
        if($this->details = optional_param('details', '', PARAM_TEXT)) {
            $this->pagelayout = "base";
        }

        parent::init_page();

        $this->page->requires->js('/blocks/manage/yui/base.js');
    }

    private function _get_block_names($region) {
        $result = array();
        /* Не лучшее решение, так как подключатся блоки на страницах где они не нужны,
         * однако, если нужны, то не будет лишнего запроса
         */
        $blocks = $this->page->blocks->get_blocks_for_region('side-pre');
        foreach ($blocks as $block) {
            $result[] = $block->blockname;
        }
        return $result;
    }

    public function navigation(){
        global $USER;

        $profile_notifications = lm_notification::get_count($this->_get_block_names('side-pre'), TRUE);
        $myteam_notifications  = lm_notification::get_count('lm_myteam', TRUE);
        $subpages = array(
            array(
                'code'   => 'index',
                'name'   => 'Мой профиль',
                'url'    => '/blocks/manage?_p=profile',
                'alerts' => $profile_notifications
            ),
             /*'evolution' => 'Развитие',
            'calendar' => 'Календарь событий',*/
            array(
                'name'   => 'Моя команда',
                'url'    => '/blocks/manage?_p=lm_myteam',
                'alerts' => $myteam_notifications
            )
        );
        $this->pageurl .= '&id=' . $USER->id;

        return $this->subnav($subpages);
    }

    public function require_access(){
        return true; //Имеют доступ все авторизованные пользователи
    }

    public function main_content()
    {
        if($this->details) {
            /**
             * @var $block lm_profile_block
             */
            if($block = block_instance($this->details)){
                return $block->details_content();
            }else{
                return "";
            }
        }else{


            $this->tpl->roleselector = FALSE;
            if($this->page->user_is_editing()) {
                $roles = lm_post::post_menu();

                $url = new moodle_url($this->page->url);
                $url->remove_params("role");
                $url->param("role", "__");
                $attrs = array('data-redirecturl'=>$url->out_as_local_url(FALSE));

                $this->tpl->roleselector = html_writer::select($roles, 'role ', $this->editinpost, 'Выберите роль для настройки блоков...', $attrs);
            }

            return $this->fetch('profile/index.tpl');
        }
    }

    public function ajax_get_params_by_metric($p)
    {
        global $DB;

        $tpl = $this->tpl;
        $values = array();
        $tpl->titles = array();
        $sql = "SELECT
                  rpv.value, rp.name
                FROM
                  {lm_rating_param} rp
                JOIN
                  {lm_rating_param_value} rpv
                    ON
                      rpv.paramid = rp.id
                WHERE rpv.metric_value_id = {$p->metric_value_id}";
        $params = $DB->get_records_sql($sql);
        foreach ( $params as $param ) {
            $values[] = $param->value;
            $tpl->titles[] = $param->name;
        }

        $tpl->titles[] = "%";
        $tpl->titles[] = "Балл за показатель";
        $tpl->titles[] = "Балл с учетом веса параметра";

        $sql = "SELECT
                  mv.*, m.weight, m.name
                FROM
                  {lm_rating_metric_value} mv
                JOIN
                  {lm_rating_metric} m
                    ON m.id = mv.metricid
                WHERE
                  mv.userid = {$p->user_id} AND mv.id = {$p->metric_value_id}";
        $metric = $DB->get_record_sql($sql);

        $values[] = round($metric->value, 2);
        $values[] = $metric->bal;
        $values[] = $metric->bal * $metric->weight;
        $tpl->values = $values;
        $tpl->count_titles = count($tpl->titles);
        $a = new StdClass();
        $a->text = $this->fetch('rating/onemetric.tpl');
        $weight = $metric->weight*100;
        $a->title = $metric->name." Вес: {$weight} %";

        return $a;
    }


    public function ajax_get_kpi_by_month($p){
        global $USER;

        $a = new StdClass();
        $a->series = [];
        $a->xaxis = [];

        if ($p->userid) {
            $userids = explode(',', $p->userid);
        } else {
            $userids = array($USER->id);
        }

        $months = array("Янв", "Фев", "Март", "Апр", "Май", "Июнь", "Июль", "Авг", "Сент", "Окт", "Ноя", "Дек");

        $series = new StdClass();
        $series->type = 'column';
        $series->data = [];
        foreach($userids as $userid) if ((int) $userid) {
            if ($posid = lm_position::i((int) $userid)->get_id()) {
                $kpilist = new lm_kpi_list($posid, $userid);
                if ($items = $kpilist->kpi_history($p->kpiid)) {
                    foreach ($items as $no => $item) {
                        $a->xaxis[$item->date] = $months[date("n", strtotime($item->date)) - 1];
                        if ($series->data[$item->date]) {
                            $series->data[$item->date][0] += $item->plan;
                            $series->data[$item->date][1] += $item->fact;
                        } else {
                            $series->data[$item->date] = array($item->plan, $item->fact);
                        }
                    }
                }
            }
        }
        $a->series[] = $series;

        // Если кол-во месяцев больше 3х, то можем построить линию тренда по методу скользящей средней
        $count = count($series->data);
        if ($count >= 3) {
            // Сбросим ключи массива, чтобы они начиналис с нуля и шли по порядку
            $items = array_values($series->data);

            // Считаем скользящую среднюю за по всем месяцам, кроме первого и последнего
            $m = array();
            for ($i = 0; $i < $count; $i++) {
                if (isset($items[$i]) && isset($items[$i + 1]) && isset($items[$i + 2])) {
                    $m[$i + 1] = ($items[$i][1] + $items[$i + 1][1] + $items[$i + 2][1]) / 3;
                }
            }
            // Прогноз на следующий месяц
            //$predict = $m[count($m)-1] + 1/3 * ($items[$count-1]->fact - $items[$count-2]->fact);
            $predict = $m[count($m)] + 1 / 3 * ($items[$count - 1]->fact - $items[$count - 2]->fact);

            $start = $count >= 6 ? $count - 6 : 0;

            $series = new StdClass();
            $series->type = 'line';
            $series->data = [[$start, $items[$start][1]], [($count - 1), $predict]];
            $a->series[] = $series;
        }

        foreach ($a->series as &$s) $s->data = array_values($s->data);
        $a->xaxis = array_values($a->xaxis);

        return $a;
    }

}