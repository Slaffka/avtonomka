<?php
/**
 * данный класс обрабатывает данные с интерфейса
 *
 * @author   Andrej Schartner <schartner@as-code.eu>
 */
class block_lm_settinggoals_renderer extends block_manage_renderer {

    /**
     * переменная содержит урл к блоку
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=lm_settinggoals';

    /**
     * переменная содержит название блока
     * @var string
     */
    public $pagename = 'Целеполагание';

    /**
     * переменная содержит тип блока
     * @var string
     */
    public $type = 'lm_settinggoals';

    /**
     * переменная содержит лайоут блока
     * @var string
     */
    public $pagelayout = "";

    /**
     * переменная содержит ид страници деталей
     * @var int
     */
    public $showdetails = 0;

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
        global $USER, $OUTPUT, $CFG, $redirect;

        // достаём параметры из урл
        $this->showdetails = optional_param('showdetails', 0, PARAM_INT);
        $this->tpid        = optional_param('tpid',        0, PARAM_INT);
        $this->tptime      = optional_param('tptime',      0, PARAM_INT);
        $this->svid        = optional_param('svid',        0, PARAM_INT);
        $this->posid       = optional_param('posid',       0, PARAM_INT);
        $this->svtime      = optional_param('svtime',      0, PARAM_INT);
        $this->do_action   = optional_param('do_action',  '', PARAM_ALPHANUM);
        $this->search      = optional_param('search',     '', PARAM_CLEANHTML);


        // запускаем инициализацию от базового класса
        parent::init_page();

        // подгружаем необходимые скрипты для работы с блоком
        $this->page->requires->js('/blocks/manage/yui/base.js');
        $this->page->requires->js("/blocks/lm_settinggoals/js/base.js");
        $this->page->requires->jquery_plugin('scroll', 'theme_tibibase');
        $this->page->requires->js("/blocks/lm_settinggoals/js/comments.js");
        $this->page->requires->js("/blocks/lm_settinggoals/js/outlets_list.js");
        $this->initACL();

        $is_tp  = isset($this->acl['post']['is_tp']) && $this->acl['post']['is_tp'];
        $is_sv  = isset($this->acl['post']['is_sv']) && $this->acl['post']['is_sv'];

        $this->svtime = $this->svtime > 0 ? $this->svtime : mktime(0, 0, 0, date("n"), date("j"), date("Y"));
        $this->tptime = $this->tptime > 0 ? $this->tptime : mktime(0, 0, 0, date("n"), date("j"), date("Y"));

        $page_url = (string)$this->pageurl;
        if ($is_tp) {
            // обработка действий торгового представителя
            switch ($this->do_action) {
                // запускаем корректировку
                case 'startcorrect':
                    lm_settinggoals::tp_start_correct(
                        $this->acl['post']['posid'],
                        $this->tptime,
                        false
                    );
                    redirect($page_url, '', 0);
                    break;
                // запускаем корректировку повторно
                case 'startcorrectagain':
                    lm_settinggoals::tp_start_correct(
                        $this->acl['post']['posid'],
                        $this->tptime,
                        true
                    );
                    redirect($page_url, '', 0);
                    break;
                default:
                    break;
            }

        } elseif ($is_sv) {
            // достаём актуальный этап
            $phase = lm_settinggoals::get_last_phase(
                $this->acl['post']['sv_posid'],
                $this->do_action == 'svstarttimer'
            );

            // обработка действий супервайзера
            switch ($this->do_action) {
                // запускаем план для всех торговых представителей в команде супервайзера
                case 'startplan':
                    // запускаем только если ещё не запущен этап
                    if ($phase->phase == 0) {

                        // достаём время для первого этапа из конфигурации
                        $mins = $CFG->block_lm_goalsetting_deadline_2 ? $CFG->block_lm_goalsetting_deadline_2 : 5;
                        $mins *= 60;

                        // запускаем невый этап
                        $phase = lm_settinggoals::set_new_phase(
                            1,
                            time()+$mins,
                            $this->acl['post']['sv_posid']
                        );
                    }
                    redirect($page_url, '', 0);
                    break;
                // супервайзер принял корректировку
                case 'acceptcorrection':
                    lm_settinggoals::new_correction_state_sv(
                        $this->posid,
                        $this->svtime,
                        lm_settinggoals::STATE_AGREEMENT_SUCCESS
                    );
                    // экспорт в чикаго
                    $fileName = 'goalsettingcorrect';
                    $path = realpath($CFG->dataroot.$CFG->lm_chikagosync_path);
                    $filePath = $path.'/'.date('Y-m-d', $this->svtime).'_'.$fileName.'.xml';
                    (new lm_settinggoals_plan_export($filePath, TRUE, TRUE))->export($this->svtime, $this->posid);
                    redirect($page_url, '', 0);
                    break;
                // супервайзер отправил план обратно на корректировку
                case 'rejectcorrection':
                    lm_settinggoals::new_correction_state_sv(
                        $this->posid,
                        $this->svtime,
                        lm_settinggoals::STATE_AGREEMENT_REJECT
                    );
                    redirect($page_url, '', 0);
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * инициализация прав данного пользователя
     */
    protected function initACL() {
        global $USER;
        $this->acl = lm_settinggoals::get_user_acl($USER->id);
    }

    /**
     * Создание навигации
     */
    public function navigation(){
        $subparts = array(
            array(
                'code' => 'index',
                'name' => 'Статус на сегодня',
                'url'  => $this->pageurl,
                'alerts' => null,
            ),
            array(
                'code' => 'today_plan',
                'name' => 'Сегодняшние цели',
                'url'  => $this->pageurl . '&subpage=today_plan',
                'alerts' => null,
            ),
            array(
                'code' => 'today',
                'name' => 'Итоги дня',
                'url'  => $this->pageurl . '&subpage=today',
                'alerts' => null,
            ),
        );
        return $this->subnav($subparts);
    }

    /**
     * Функция генерирует главный контент блока
     */
    public function main_content(){
        // достаём глобальный необходимый функционал
        global $USER, $OUTPUT, $CFG, $redirect;

        // достаём тпл обьект
        $tpl = $this->tpl;

        // прописываем путь к тпл блока
        $tpl_path = "/blocks/" . $this->type . "/tpl/";

        // ставим актуальный адрес данной страници для тпл
        $tpl->page_url = (string)$this->pageurl;

        // проверка прав на посещение данной страници
        if (!isset($this->acl['subpage'][$this->subpage])
            || !$this->acl['subpage'][$this->subpage]
            ) {
            return $this->fetch($tpl_path . 'no_rights.tpl');
        }

        // прописываем для тпл разные параметры
        $tpl->is_tp  = isset($this->acl['post']['is_tp']) && $this->acl['post']['is_tp'];
        $tpl->is_sv  = isset($this->acl['post']['is_sv']) && $this->acl['post']['is_sv'];
        $tpl->is_mod = isset($this->acl['post']['is_mod']) && $this->acl['post']['is_mod'];
        $tpl->userid = isset($this->acl['post']['userid']) ? $this->acl['post']['userid'] : $USER->id;
        $tpl->sv_posid = $this->acl['post']['sv_posid'];

        // достаём актуальный этап
        $tpl->phase = lm_settinggoals::get_last_phase(
            $this->acl['post']['sv_posid'],
            ($tpl->is_sv && $this->do_action == 'svstarttimer')
        );

        // проставляем актуальный таймер
        $deadline = $tpl->phase->deadline;

        // разные странички
        switch ($this->subpage) {
            case 'cleanup_db':
                lm_settinggoals::cleanup_db();
                return "старые записи были удалены";
                break;
            // достаём информацию для показа страници: "Статус на сегодня"
            case 'index':
            default:
                // показывать ли детали о супервайзере?
                if ($this->showdetails > 0) {
                    // адрес для путя назад к "Статус на сегодня"
                    $tpl->back_page_url = $tpl->page_url;

                    // дописываем к путю актуальной страници ид
                    $tpl->page_url .= "&showdetails=" . $this->showdetails;

                    // подготовка списка торговых представителей данного супервайзера
                    list(
                        $tpl->total_kpis,
                        $tpl->tp_list,
                        $tpl->kpi_list,
                        $pager_data
                    ) = lm_settinggoals::get_superviser_tp_list(
                        $this->showdetails,
                        $this->pagenum
                    );

                    // ставим тпл для загрузки
                    $tpl_path .= "today_status_details.tpl";
                } else {
                    // подготовка списка супервайзеров
                    list(
                        $tpl->superviser_list,
                        $tpl->kpi_list,
                        $pager_data
                    ) = lm_settinggoals::get_superviser_list(
                        $this->acl['post']['sv_posid'],
                        $this->pagenum
                    );

                    // ставим тпл для загрузки
                    $tpl_path .= "today_status.tpl";
                }
                break;
            // достаём информацию для показа страници: "Статус на сегодня"
            case 'today_plan':

                // досаём ид торгового представителя и передаём в тпл
                $this->tpid = $this->tpid > 0 ? $this->tpid : $tpl->userid;
                $tpl->tpid  = $this->tpid;

                // проверяем зашёл ли на данную страницу торговый представитель
                if ($tpl->is_tp) {
                    // проверяем статус актуального этапа
                    if ($tpl->phase->phase != 1) {
                        $tpl_path .= "today_plan_phase_error.tpl";
                        break;
                    }

                    // прописываем необходимые параметры о дате для показа
                    $this->tptime = $this->tptime > 0 ? $this->tptime : mktime(0, 0, 0, date("n"), date("j"), date("Y"));
                    $tpl->tptime = $this->tptime;
                    $tpl->plan_date = time();
                    $tpl->curr_time = $this->tptime;

                    // добавляем в адрес данной страници ид торгового представителя и дату для показа
                    $tpl->page_url .= "&tpid=" . $this->tpid;
                    $tpl->page_url .= "&tptime=" . $this->tptime;

                    // обработка действий торгового представителя
                    switch ($this->do_action) {
                        // показываем выбор торговых точек для добавления
                        case 'setoutlets':
                            // ставим урл для выборки торговых точек
                            $tpl->page_url .= "&do_action=setoutlets";
                            list(
                                $tpl->posid,
                                $tpl->time,
                                $tpl->outlet_list,
                                $tpl->search,
                                $pager_data
                            ) = lm_settinggoals::get_tp_outlet_list(
                                $this->acl['post']['posid'],
                                $this->tptime,
                                $this->search,
                                $this->pagenum
                            );

                            // передаём ид торгового представителя в тпл
                            $tpl->tpid = $this->tpid;

                            // передаём количество торговых точек в тпл
                            $tpl->outlets = ceil(count($tpl->outlet_list)/2) - 1;

                            // ставим тпл для загрузки
                            $tpl_path .= "outlet_list.tpl";

                            // если параметер для поиска передан, добавляем его в урл
                            if (!empty($tpl->search)) {
                                $tpl->page_url .= "&search=" . $tpl->search;
                            }
                            // прерываем обработку switch блока который находится над актуальным блоком
                            break 2;
                        default:
                            break;
                    }

                    // достаём список торговых точек которые находятся в плане у актуального торгового представителя
                    list(
                        $tpl->auto_list,
                        $tpl->correct_list,
                        $tpl->status,
                        $tpl->place_list,
                        $tpl->kpi_list,
                        $tpl->no_plan,
                        $pager_data
                    ) = lm_settinggoals::get_tp_place_list(
                        $this->tpid,
                        $this->tptime,
                        $this->pagenum
                    );

                    // достаём и передаём информацию о комментариях от супервайзера для актуального
                    // торгового представителя
                    $tpl->comments = lm_settinggoals::get_plan_comments($tpl->phase->id, $this->acl['post']['posid']);
                    $tpl->show_comments = !empty($tpl->comments);
                    $tpl->may_comment = false;

                    // ставим тпл для загрузки
                    $tpl_path .= "today_plan.tpl";
                }
                // проверяем зашёл ли на данную страницу супервайзер
                else if ($tpl->is_sv) {

                    // ставим ид супервайзера и время для показа
                    $this->svid   = $this->svid > 0 ? $this->svid : $tpl->userid;
                    $this->svtime = $this->svtime > 0 ? $this->svtime : mktime(0, 0, 0, date("n"), date("j"), date("Y"));
                    $tpl->curr_time = $this->svtime;

                    // супервайзер находится в просмотре корректировки и комментирование
                    if ($this->do_action === 'commentcorrection') {
                        // достаём список торговых точек торгового представителя
                        list(
                            $tpl->auto_list,
                            $tpl->correct_list,
                            $tpl->status,
                            $tpl->place_list,
                            $tpl->kpi_list,
                            $no_plan,
                            $pager_data
                            ) = lm_settinggoals::get_tp_place_list(
                            $this->tpid,
                            $this->svtime,
                            $this->pagenum,
                            8,
                            lm_settinggoals::STATE_AGREEMENT
                        );

                        // достаём ид позиции торгового представителя
                        $tppos = lm_position::i((int) $this->tpid);
                        $tpl->posid = $tppos->id;

                        // подгружаем комментарии оставленные раньше
                        $tpl->comments = lm_settinggoals::get_plan_comments($tpl->phase->id, $tppos->id);
                        $tpl->show_comments = true;
                        $tpl->may_comment = true;

                        $tpl->page_url .= '&tpid=' . $this->tpid . '&do_action=commentcorrection';

                        // ставим тпл для загрузки
                        $tpl_path .= "today_plan.tpl";
                    } else {
                        // достаём список торговых представителей для актуального супервайзера
                        list(
                            $tpl->auto_list,
                            $tpl->correct_list,
                            $tpl->status,
                            $tpl->user_list,
                            $tpl->kpi_list,
                            $pager_data
                            ) = lm_settinggoals::get_sv_user_list(
                            $this->svid,
                            $this->svtime,
                            $this->pagenum
                        );
                            // echo "<pre>";
                            // print_r($tpl->user_list);exit;

                        // ставим тпл для загрузки
                        $tpl_path .= "today_plan_sv.tpl";
                    }
                } else {
                    // не верная страница, перекидываем пользователя на стартовую блока
                    redirect($this->pageurl);
                }
                break;
            // достаём информацию для показа страници: "Итоги дня"
            case 'today':
                // достаём дату для отображения итогов дня
                $this->svtime = $this->svtime > 0 ? $this->svtime : mktime(0, 0, 0, date("n"), date("j"), date("Y"));

                // показывать ли детали о торговом представителе?
                if ($this->showdetails > 0) {
                    // ставим обратный адрес
                    $tpl->back_page_url = $tpl->page_url;
                    $tpl->page_url .= "&showdetails=" . $this->showdetails;

                    // достаём информацию для показа информации о торговом представителе
                    list(
                        $tpl->kpi_list,
                        $tpl->total_kpis,
                        $tpl->tp_list
                    ) = lm_settinggoals::get_daily_result_details(
                        $this->showdetails,
                        $this->svtime
                    );

                    // ставим тпл для загрузки
                    $tpl_path .= "today_details.tpl";
                } else {
                    // достаём информацию для показа о торговых представителях
                    list(
                        $tpl->kpi_list,
                        $tpl->auto_list,
                        $tpl->fact_list,
                        $tpl->user_list
                    ) = lm_settinggoals::get_daily_result(
                        $this->acl['post']['sv_posid'],
                        $this->svtime
                    );

                    // ставим время след. обновления с чикаго
                    $next_update = strtotime(date('Y-m-d H:00:00',time()+60*60)) - time();
                    if ($next_update) {
                        $tpl->next_update_m = floor($next_update / 60);
                        $tpl->next_update_s = $next_update % 60;
                    }

                    // ставим тпл для загрузки
                    $tpl_path .= "today.tpl";
                }
                break;
        }

        // подготовка данных для таймера
        if ($deadline > 0) {
            // ставим остаток времени для таймера
            $tpl->timeleft = $deadline - time();

            // превращаем негативное значение в позитивное
            $tmp_time = $tpl->timeleft < 0 ? ($tpl->timeleft * -1) : $tpl->timeleft;

            // высчитываем остаток часов
            $tpl->timeleft_h = floor($tmp_time / 60 / 60);
            if ($tpl->timeleft_h == 0) {
                // часов нету

                // ставим вместо часов минуты
                $tpl->timeleft_h = floor($tmp_time / 60);

                // ставим вместо минут секунды
                $tpl->timeleft_m = $tmp_time % 60;
            } else {
                // высчитываем количество часов
                $tmp_time -= $tpl->timeleft_h * 60 * 60;

                // высчитываем количество минут
                $tpl->timeleft_m = floor($tmp_time / 60);
            }
            $tpl->timeleft_h = $tpl->timeleft_h < 10 ? ('0' . $tpl->timeleft_h) : $tpl->timeleft_h;
            $tpl->timeleft_m = $tpl->timeleft_m < 10 ? ('0' . $tpl->timeleft_m) : $tpl->timeleft_m;
        }

        // подготовка пагинатора
        if (isset($pager_data)) {
            // создаём новый обьект пагинатора
            $pager = new StdClass();

            // количество страниц
            $pager->count = isset($pager_data['count']) ? $pager_data['count'] : 1;

            // актуальная страница
            $pager->current = isset($pager_data['current']) ? $pager_data['current'] : 1;

            // урл для страниц (без указания страници)
            $pager->url = $tpl->page_url;

            // передаём пагинатор в тпл
            $tpl->pager = $pager;
        }
        return $this->fetch($tpl_path);
    }

    /**
     * обработка модальных диалогов
     *
     * @param object $params параметры для отображения модальных диалогов
     */
    public function ajax_modal($params) {
        // достаём глобальный необходимый функционал
        global $CFG;

        // достаём тпл обьект
        $tpl = $this->tpl;

        // прописываем путь к тпл блока
        $tpl_path = "/blocks/" . $this->type . "/tpl/";

        // ставим актуальный адрес данной страници для тпл
        $tpl->page_url = (string)$this->pageurl;

        // обработка действий супервайзера
        switch ($params->action) {
            // запускаем план для всех торговых представителей в команде супервайзера
            case 'startplan':
                // ставим тпл для загрузки
                $tpl_path .= 'modal/startplan.tpl';
                break;
            // отправка корректировку на соглашение супервайзеру
            case 'send_agreement':
                // достаём информацию о корректировке и отсылаем её если нету помех
                list(
                    $tpl->break,
                    $tpl->kpi_names,
                    $tpl->list
                ) = lm_settinggoals::send_agreement(
                    $params->time,
                    $params->positionid,
                    isset($params->force) && $params->force == 1
                );

                // ставим тпл для загрузки
                $tpl_path .= 'modal/send_agreement.tpl';
                break;
            // показать комментарий с чикаго полностью
            case 'show_comment':
                // достаём информацию о плане
                $tpl->plan = lm_settinggoals::get_plan_data(
                    $params->planid
                );

                // ставим тпл для загрузки
                $tpl_path .= 'modal/showcomment.tpl';
                break;
            // показываем диалог для обработки кпи (ММЛ)
            case 'edit_kpi_count':
                // достаём список товаров для отгрузки
                $tpl->kpi_data = lm_settinggoals::get_kpi_data_list(
                    $params->kpiid,
                    $params->planid
                );

                // ставим тпл для загрузки
                $tpl_path .= 'modal/edit_kpi_count.tpl';
                break;
            // обработка простых кпи
            case 'edit_kpi':
                // достаём актуальные значения и значение по умолчанию
                $tpl->kpi_data = lm_settinggoals::get_kpi_data(
                    $params->kpiid,
                    $params->planid
                );

                // ставим тпл для загрузки
                $tpl_path .= 'modal/edit_kpi.tpl';
                break;
            // запуск первого этапа
            case 'svstarttimer':
                $tpl->time_first_phase = $CFG->block_lm_goalsetting_deadline_1;

                // ставим тпл для загрузки
                $tpl_path .= 'modal/svstarttimer.tpl';
                break;
            // отображения актуального статуса у торгового представителя
            case 'tp_status':
                // передаём параметры в тпл
                $tpl->tpid   = $params->tpid;
                $tpl->tptime = $params->tptime;
                $tpl->status = $params->status;

                // достать даные о этапе супервайзера
                $tpl->phase = lm_settinggoals::get_last_phase(
                    $this->acl['post']['sv_posid'],
                    false
                );

                // достать все комментарии для текущего торгового представителя
                $comments = lm_settinggoals::get_plan_comments(
                    $tpl->phase->id,
                    $this->acl['post']['posid']
                );
                $tpl->comment_count = count($comments);

                // ставим тпл для загрузки
                $tpl_path .= 'modal/tp_status.tpl';
                break;
            default:
                // ставим тпл для загрузки
                $tpl_path .= 'modal/error.tpl';
                break;
        }
        return $this->fetch($tpl_path);
    }

    /**
     * сохранение обработки одного кпи из модального окна
     *
     * @param object $params параметры для отображения модальных диалогов
     */
    public function ajax_save_edit_kpi_value($params)
    {
        // сохранение нового значения для кпи
        lm_settinggoals::save_edit_kpi_value(
            $params->kpiid,
            $params->planid,
            $params->value
        );
    }

    /**
     * переключает кпи (ммл)
     *
     * @param object $params параметры для обработки
     */
    public function ajax_toggle_kpi_count($params)
    {
        lm_settinggoals::toggle_kpi_count(
            $params->kpiid,
            $params->planid
        );
    }

    /**
     * Сохранение комментариев
     *
     * @param object $params параметры для обработки
     */
    public function ajax_comment_save($p) {
        if ((int) $p->phaseid <= 0 || (int) $p->tpid <= 0 || empty($p->text)) return FALSE;

        $tppos = lm_position::i((int) $p->tpid);

        if ( ! $tppos) return FALSE;
        return lm_settinggoals::save_plan_comment((int) $p->phaseid, $tppos->id, $p->text);
    }

    /**
     * добавление новый торговых точек к торговому представителю
     *
     * @param object $params параметры для обработки
     */
    public function ajax_save_outlets($p) {
        return lm_settinggoals::save_outlets($p->posid, $p->outlets, $p->tptime);
    }

    /**
     * получение торговых точек для торгового представителя
     * не отображаются то которые у него уже есть
     * используется для аякс запросов
     *
     * @param object $params параметры для обработки
     */
    public function ajax_outlets_list($p)
    {
        // достаём глобальный необходимый функционал
        global $USER;

        $result = "";
        $tpl = $this->tpl;
        $tpl->page_url = $this->pageurl . '&subpage=today_plan';

        if ( !empty($p->tpid) && $USER->id == $p->tpid && !empty($p->tptime) ) {
            $posid = lm_position::i($USER->id)->get_id();
            $tpl->page_url .= "&do_action=setoutlets";
            list(
                $tpl->posid,
                $tpl->time,
                $tpl->outlet_list,
                $tpl->search,
                $pager_data
                ) = lm_settinggoals::get_tp_outlet_list(
                $posid,
                $p->tptime,
                $p->search,
                $this->pagenum
            );
            $tpl->tptime = $p->tptime;
            $tpl->tpid = $p->tpid;
            $tpl->search = $p->search;
            $tpl->outlets = ceil(count($tpl->outlet_list)/2) - 1;
            $tpl_path = "/blocks/" . $this->type . "/tpl/tt_list.tpl";
            if (!empty($tpl->search)) {
                $tpl->page_url .= "&search=" . $tpl->search;
            }

            // подготовка пагинатора
            if (isset($pager_data)) {
                // создаём новый обьект пагинатора
                $pager = new StdClass();

                // количество страниц
                $pager->count = isset($pager_data['count']) ? $pager_data['count'] : 1;

                // актуальная страница
                $pager->current = isset($pager_data['current']) ? $pager_data['current'] : 1;

                // урл для страниц (без указания страници)
                $pager->url = $tpl->page_url;

                // передаём пагинатор в тпл
                $tpl->pager = $pager;
            }

            $result = $this->fetch($tpl_path);
        }
        echo json_encode($result);
    }

    /**
     *
     * @param object $params параметры для обработки
     */
    public function ajax_get_sv_state($p)
    {
        global $USER;
        list(
            $auto_list,
            $correct_list,
            $status,
            $user_list,
            $kpi_list,
            $pager_data
            ) = lm_settinggoals::get_sv_user_list(
            $USER->id,
            mktime(0, 0, 0, date("n"), date("j"), date("Y")),
            0
        );
        $result = [];

        // достаём тпл обьект
        $tpl = $this->tpl;

        // прописываем путь к тпл блока
        $tpl_path = "/blocks/" . $this->type . "/tpl/today_plan_sv_status.tpl";


        $tpl->page_url = $this->pageurl . '&subpage=today_plan';


        foreach ($user_list as $user) {
            $tpl->user = $user;
            $result[$user->id] = $this->fetch($tpl_path);
        };
        return json_encode($result);
    }

    /**
     *
     * @param object $params параметры для обработки
     */
    public function ajax_get_tp_state($p)
    {
        global $USER;


        // достаём список торговых точек которые находятся в плане у актуального торгового представителя
        list(
            $auto_list,
            $correct_list,
            $status,
            $place_list,
            $kpi_list,
            $no_plan,
            $pager_data
        ) = lm_settinggoals::get_tp_place_list(
            $USER->id,
            mktime(0, 0, 0, date("n"), date("j"), date("Y")),
            0
        );
        $result = ['status' => $status];

        return json_encode($result);
    }
}